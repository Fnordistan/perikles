<?php
 /**
  *------
  * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
  * Perikles implementation : © <David Edelstein> <david.edelstein@gmail.com>
  * 
  * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
  * See http://en.boardgamearena.com/#!doc/Studio for more information.
  * -----
  * 
  * perikles.game.php
  *
  * This is the main file for your game logic.
  *
  * In this PHP file, you are going to defines the rules of the game.
  *
  */


require_once( APP_GAMEMODULE_PATH.'module/table/table.game.php' );

require_once( 'modules/PeriklesCities.class.php' );
require_once( 'modules/PeriklesLocations.class.php' );
require_once( 'modules/PeriklesBattles.class.php' );
require_once( 'modules/PeriklesSpecial.class.php' );

//  MARTIN WALLACE'S ERRATA ON BGG: https://boardgamegeek.com/thread/1109420/collection-all-martin-wallace-errata-clarification

define("INFLUENCE", "influence");
define("CANDIDATE", "candidate");
define("ASSASSIN", "assassin");
define("DECK", "deck");
define("DISCARD", "discard");
define("BOARD", "board");
define("UNCLAIMED", "unclaimed");
define("HOPLITE", "hoplite");
define("TRIREME", "trireme");
define("PERSIA", "persia");
define("ALPHA", "\u{003B1}");
define("BETA", "\u{003B2}");
define("ATTACKER", 0);
define("DEFENDER", 2);
define("MAIN", 1);
define("ALLY", 2);
// Special tiles
define("PERIKLES", "perikles");
define("PERSIANFLEET", "persianfleet");
define("SLAVEREVOLT", "slaverevolt");
define("BRASIDAS", "brasidas");
define("THESSALANIANALLIES", "thessalanianallies");
define("ALKIBIADES", "alkibiades");
define("PHORMIO", "phormio");
define("PLAGUE", "plague");

define("ATTACKER_TOKENS", "attacker_tokens");
define("DEFENDER_TOKENS", "defender_tokens");
define("CONTROLLED_PERSIANS", "_persia_"); // used to flag Persian units that will go to a player board

define("DEADPOOL", "deadpool");

class Perikles extends Table
{
	function __construct( )
	{
        parent::__construct();
        

        self::initGameStateLabels( array( 
            "argos_leader" => 10,
            "argos_a" => 11,
            "argos_b" => 12,
            "athens_leader" => 13,
            "athens_a" => 14,
            "athens_b" => 15,
            "corinth_leader" => 16,
            "corinth_a" => 17,
            "corinth_b" => 18,
            "megara_leader" => 19,
            "megara_a" => 20,
            "megara_b" => 21,
            "sparta_leader" => 22,
            "sparta_a" => 23,
            "sparta_b" => 24,
            "thebes_leader" => 25,
            "thebes_a" => 26,
            "thebes_b" => 27,
            "argos_defeats" => 30,
            "athens_defeats" => 31,
            "corinth_defeats" => 32,
            "megara_defeats" => 33,
            "sparta_defeats" => 34,
            "thebes_defeats" => 35,

            "active_battle" => 40,
            "battle_round" => 41, // 0,1
            "battle_loser" => 42,
            ATTACKER_TOKENS => 43, // battle tokens won by attacker so far in current battle
            DEFENDER_TOKENS => 44, // battle tokens won by defender so far in current battle

            "influence_phase" => 50,
            "last_influence_slot" => 51, // keep track of where to put next Influence tile
            "commit_phase" => 52,
            "spartan_choice" => 53, // who Sparta picked to go first in military phase
            "deadpool_picked" => 54, // how many players have been checked for deadpool?

            BRASIDAS => 60, // Brasides activated for next battle
            PHORMIO => 61, // Phormio activated for next battle
        ) );

        $this->Cities = new PeriklesCities($this);
        $this->Locations = new PeriklesLocations($this);
        $this->Battles = new PeriklesBattles($this);
        $this->SpecialTiles = new PeriklesSpecial($this);

        $this->influence_tiles = self::getNew("module.common.deck");
        $this->influence_tiles->init("INFLUENCE");
        $this->location_tiles = self::getNew("module.common.deck");
        $this->location_tiles->init("LOCATION");

    }
	
    protected function getGameName( )
    {
		// Used for translations and stuff. Please do not modify.
        return "perikles";
    }	

    /*
        setupNewGame:
    */
    protected function setupNewGame( $players, $options = array() )
    {    
        $gameinfos = self::getGameinfos();
        $default_colors = $gameinfos['player_colors'];
 
        // Create players
        $sql = "INSERT INTO player (player_id, player_color, player_canal, player_name, player_avatar) VALUES ";
        $values = array();
        foreach( $players as $player_id => $player )
        {
            $color = array_shift( $default_colors );
            $values[] = "('".$player_id."','$color','".$player['player_canal']."','".addslashes( $player['player_name'] )."','".addslashes( $player['player_avatar'] )."')";
            foreach($this->Cities->cities() as $cn) {
                $statues = $cn."_statues";
                self::initStat( 'player', $statues, 0, $player_id);
            }
        }
        self::initStat('table', 'turns_number', 0);

        $sql .= implode(',', $values );
        self::DbQuery( $sql );
        self::reattributeColorsBasedOnPreferences( $players, $gameinfos['player_colors'] );
        self::reloadPlayersBasicInfos();

        /************ Start the game initialization *****/

        // Init global values with their initial values
        $city_states = ["leader", "a", "b", "defeats"];
        foreach($this->Cities->cities() as $cn) {
            foreach ($city_states as $lbl) {
                self::setGameStateInitialValue( $cn."_".$lbl, 0 );
            }
        }
        self::setGameStateInitialValue("last_influence_slot", 0);
        self::setGameStateInitialValue("deadpool_picked", 0);
        self::setGameStateInitialValue("spartan_choice", 0);
        self::setGameStateInitialValue(ATTACKER_TOKENS, 0);
        self::setGameStateInitialValue(DEFENDER_TOKENS, 0);
        self::setGameStateInitialValue("active_battle", 0);
        self::setGameStateInitialValue("battle_round", 0);
        self::setGameStateInitialValue("battle_loser", -1);
        self::setGameStateInitialValue(BRASIDAS, 0);
        self::setGameStateInitialValue(PHORMIO, 0);
        // when we are in the Influence Phase and influence special tiles can be used. Start with Influence, ends with candidate nominations.
        self::setGameStateInitialValue("influence_phase", 1);
        // when we are in the committing phase. Start with first commit, end with battle phase.
        self::setGameStateInitialValue("commit_phase", 0);

        $this->Cities->setupNewGame();

        $this->setupInfluenceTiles();

        $this->Locations->setupNewGame();

        $this->SpecialTiles->setupNewGame();

        // Activate first player (which is in general a good idea :) )
        $this->activeNextPlayer();

        /************ End of the game initialization *****/
    }

    /**
     * Lay out the first 10 influence tiles
     */
    protected function setupInfluenceTiles() {
        $influence = $this->createInfluenceTiles();
        $this->influence_tiles->createCards($influence, DECK);
        $this->influence_tiles->shuffle(DECK);
        for ($i = 1; $i <= 10; $i++) {
            $this->influence_tiles->pickCardForLocation(DECK, BOARD, $i);
        }
    }

    /**
     * Create the influence tiles for deck
     */
    protected function createInfluenceTiles() {
        $influence_tiles = array();

        foreach( $this->Cities->cities() as $cn) {
            $influence_tiles[] = array('type' => $cn, 'type_arg' => INFLUENCE, 'location' => DECK, 'location_arg' => 0, 'nbr' => $this->Cities->getInfluenceNbr($cn));
            $influence_tiles[] = array('type' => $cn, 'type_arg' => CANDIDATE, 'location' => DECK, 'location_arg' => 0, 'nbr' => $this->Cities->getCandidateNbr($cn));
            $influence_tiles[] = array('type' => $cn, 'type_arg' => ASSASSIN, 'location' => DECK, 'location_arg' => 0, 'nbr' => 1);
        }
        $influence_tiles[] = array('type' => 'any', 'type_arg' => INFLUENCE, 'location' => DECK, 'location_arg' => 0, 'nbr' => 5);
        return $influence_tiles;
    }

    /*
        getAllDatas: 
        
        Gather all informations about current game situation (visible by the current player).
        
        The method is called each time the game interface is displayed to a player, ie:
        _ when the game starts
        _ when a player refreshes the game page (F5)
    */
    protected function getAllDatas()
    {
        $result = array();
    
        $current_player_id = self::getCurrentPlayerId();    // !! We must only return informations visible by this player !!
    
        // Get information about players
        // Note: you can retrieve some extra field you added for "player" table in "dbmodel.sql" if you need it.
        $sql = "SELECT player_id id, player_score score FROM player ";
        $result['players'] = self::getCollectionFromDb( $sql );
  
        $result['influencetiles'] = $this->getInfluenceDisplay();
        $result['decksize'] = $this->influence_tiles->countCardInLocation(DECK);

        $result['locationtiles'] = $this->Locations->getLocationTiles();
        
        $result['specialtiles'] = $this->getSpecialTiles($current_player_id);
        $result['influencecubes'] = $this->Cities->getAllInfluence();
        $result['candidates'] = $this->Cities->getAllCandidates();
        $result['leaders'] = $this->Cities->getLeaders();
        $result['persianleaders'] = $this->Cities->getPersianLeaders();
        $result['statues'] = $this->Cities->getAllStatues();
        $result['defeats'] = $this->Cities->getAllDefeats();
        $result['military'] = $this->getMilitary();
        $result['battletokens'] = $this->getBattleTokens();

        return $result;
    }

    /**
     * Return associative array of player_id => tile
     * For unused opponent tiles, name is null
     */
    protected function getSpecialTiles($current_player_id) {
        $specialtiles = array();
        $playertiles = self::getCollectionFromDB("SELECT player_id, special_tile special, special_tile_used used FROM player");

        foreach ($playertiles as $player_id => $tile) {
            $t = [];
            $t['used'] = ($tile['used'] == 0) ? false : true;
            if ($player_id == $current_player_id || $t['used']) {
                $t['name'] = $tile['special'];
            } else {
                // only reveal tile if it's been used
                $t['name'] = null;
            }
            $specialtiles[$player_id] = $t;
        }
        return $specialtiles;
    }

    /**
     * Get all military tokens with their locations.
     * Hide the values for other players' units sent to battle.
     */
    function getMilitary() {
        $player_id = self::getCurrentPlayerId();
        $military = self::getObjectListFromDB("SELECT id, city, type, strength, location, battlepos FROM MILITARY");
        // but we need to show only the backs for units in battle that aren't mine
        foreach (array_keys($military) as $id) {
            // is it at a battle?
            if ($military[$id]['battlepos'] != 0) {
                // if it's not mine, zero the id and strength unless the counters have been flipped
                // because it's an active battle
                if (!($this->Cities->isLeader($player_id, $military[$id]['city']) || $this->Battles->isActiveBattleLocation($military[$id]['location']))) {
                    $military[$id]['id'] = 0;
                    $military[$id]['strength'] = 0;
                }
            }
        }
        return $military;
    }

    /**
     * Return an associative array, battle_tokens, attacker_battle_tokens, defender_battle_tokens
     * Empty if no active battle.
     * @return {array}
     */
    function getBattleTokens() {
        $tokens = array();
        $is_battle = ($this->getGameStateValue("active_battle") != 0);
        if ($is_battle) {
            $att = $this->getGameStateValue(ATTACKER_TOKENS);
            $def = $this->getGameStateValue(DEFENDER_TOKENS);
            $center = 4 - ($att+$def);
            $tokens["battle_tokens"] = $center;
            $tokens["attacker_battle_tokens"] = $att;
            $tokens["defender_battle_tokens"] = $def;
        }
        return $tokens;
    }

    /*
        getGameProgression:
        
        Compute and return the current game progression.
        The number returned must be an integer beween 0 (=the game just started) and
        100 (= the game is finished or almost finished).
    
        This method is called each time we are in a game state with the "updateGameProgression" property set to true 
        (see states.inc.php)
    */
    function getGameProgression()
    {
        $turn = self::getStat('turns_number');
        $p = ($turn / 3.0) * 100;
        return $p;
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Utility functions
////////////    

    /**
     * Name of current state
     */
    function getStateName() {
        $state = $this->gamestate->state();
        return $state['name'];
    }

    /**
     * Returns true if this player has at least once Influence tile of this city
     */
    function hasCityInfluenceTile($player_id, $city) {
        $tiles = $this->influence_tiles->getCardsOfTypeInLocation($city, null, $player_id);
        return !empty($tiles);
    }

    /**
     * Have all players taken all the Influence tiles for the Take Influence phase?
     */
    function allInfluenceTilesTaken() {
        $players = self::loadPlayersBasicInfos();
        $cardlim = count($players) == 5 ? 4 : 5;
        foreach($players as $player_id => $player) {
            if ($this->influence_tiles->countCardInLocation($player_id) < $cardlim) {
                return false;
            }
        }
        return true;
    }

    /**
     * Return {name,shard,desc} array of strings
     */
    function influenceTileDescriptors($tile) {
        $name = $tile['city'];
        $shards = 2;
        $desc = "";
        if ($name == 'any') {
            $name = clienttranslate('Any');
            $shards = 1;
        } else {
            $name = $this->Cities->getNameTr($name);

            if ($tile['type'] == "assassin") {
                $desc = clienttranslate('Assassin');
                $shards = 1;
            } else if ($tile['type'] == "candidate") {
                $desc = clienttranslate('Candidate');
                $shards = 1;
            }
        }
        return [$name, $shards, $desc];
    }

    /**
     * Create translateable description string of a unit
     */
    function unitDescription($city, $strength, $type, $location) {
        $home_city = $this->Cities->getNameTr($city);
        $unit_type = $this->getUnitName($type);
        $location_name = $this->Locations->getName($location);
        $unit_desc = sprintf(clienttranslate("%s %s-%s at %s"), $home_city, $unit_type, $strength, $location_name);
        return $unit_desc;
    }

    /**
     * Add cubes to a city and send notification.
     */
    function addInfluenceToCity($city, $player_id, $cubes) {
        $players = self::loadPlayersBasicInfos();
        $player_name = $players[$player_id]['player_name'];

        $cubect = $this->Cities->allCubesOnBoard($player_id);
        if ($cubect >= 30) {
            throw new BgaUserException(self::_("You already have 30 cubes on the board"));
        }

        $this->Cities->changeInfluence($city, $player_id, $cubes);
        $city_name = $this->Cities->getNameTr($city);

        self::notifyAllPlayers('influenceCubes', clienttranslate('${player_name} adds ${cubes} Influence to ${city_name}'), array(
            'i18n' => ['city_name'],
            'player_id' => $player_id,
            'player_name' => $player_name,
            'cubes' => $cubes,
            'city' => $city,
            'city_name' => $city_name,
            'preserve' => ['city']
        ));
    }

    /**
     * Draw a new Influence card from deck and place.
     */
    function drawInfluenceTile() {
        $slot = $this->getGameStateValue("last_influence_slot");
        $this->influence_tiles->pickCardForLocation(DECK, BOARD, $slot);
        $newtile = self::getObjectFromDB("SELECT card_id id, card_type city, card_type_arg type, card_location location, card_location_arg slot FROM INFLUENCE WHERE card_location = \"".BOARD."\" AND card_location_arg =$slot");
        $this->setGameStateValue("last_influence_slot", 0);

        $descriptors = $this->influenceTileDescriptors($newtile);
        $city_name = $descriptors[0];
        $inf_type = $descriptors[2];
        if (!empty($inf_type)) {
            $inf_type = "(".$inf_type.")";
        }

        self::notifyAllPlayers("influenceCardDrawn", clienttranslate('New Influence tile: ${shards}-Shard ${city_name} tile ${inf_type}'), array(
            'i18n' => ['city_name', 'inf_type'],
            'city' => $newtile['city'],
            'city_name' => $city_name,
            'shards' => $descriptors[1],
            'inf_type' => $inf_type,
            'tile' => $newtile,
            'preserve' => ['city']
        ));
    }


    /**
     * Move a tile to the unclaimed pile.
     * Only does the movement, not notifications.
     * @param {string} id
     */
    function unclaimedTile($tile) {
        $this->moveTile($tile, UNCLAIMED);
    }

    /**
     * A player claims a tile. Return military units.
     * Add tile to player's board. Send notification to move tile.
     * 
     * @param {string} player_id
     * @param {Object} tile
     */
    function claimTile($player_id, $tile) {
        $city = $tile['city'];
        $location = $tile['location'];
        $players = self::loadPlayersBasicInfos();
        $vp = $this->Locations->getVictoryPoints($location);
        self::DbQuery( "UPDATE player SET player_score=player_score+$vp WHERE player_id=$player_id" );
        self::notifyAllPlayers('claimTile', clienttranslate('${player_name} claims ${location_name} tile'), array(
            'i18n' => ['location_name'],
            'city' => $city,
            'location' => $location,
            'player_id' => $player_id,
            'vp' => $vp,
            'player_name' => $players[$player_id]['player_name'],
            'location_name' => $this->Locations->getName($location),
            'preserve' => ['player_id', 'city', 'location'],
        ));
        $this->moveTile($tile, $player_id);
    }

    /**
     * Add a defeat to a city.
     */
    function defeatCity($city) {
        $defeats = $this->Cities->addDefeat($city);
        self::notifyAllPlayers('cityDefeat', clienttranslate('${city_name} suffers a defeat'), array(
            'i18n' => ['city_name'],
            'city' => $city,
            'city_name' => $this->Cities->getNameTr($city),
            'defeats' => $defeats,
            'preserve' => ['city'],
        ));
    }

    /**
     * First return all military.
     * Then move a tile either to a player board or unclaimed pile.
     * Does not send notification.
     * Send notification.
     */
    function moveTile($tile, $destination) {
        $id = $tile['id'];
        $this->returnMilitaryUnits($tile);
        $this->location_tiles->insertCardOnExtremePosition($id, $destination, true);
        self::DbQuery("UPDATE LOCATION SET attacker=NULL,defender=NULL,permissions=NULL WHERE card_id=$id");
    }

    /**
     * As Leader of a city, player takes all military units.
     */
    function moveMilitaryUnits($player_id, $city) {
        self::DbQuery("UPDATE MILITARY SET location = $player_id WHERE location=\"$city\"");
        $units = self::getObjectListFromDB("SELECT id, city, type, strength, location FROM MILITARY WHERE city=\"$city\" AND location=$player_id");
        // send notification that moves units from city stack to player's military zone
        self::notifyAllPlayers("takeMilitary", '', array(
            'military' => $units,
        ));
    }

    /**
     * Assign Persian units to "persians" location which js interprets as put in persian leader(s) military area.
     * @param {array} persianleaders should already have been verified non-empty
     */
    function movePersianUnits($persianleaders) {
        // flag Persians as controlled
        self::DbQuery("UPDATE MILITARY SET location = \"".CONTROLLED_PERSIANS."\" WHERE location=\"".PERSIA."\"");
        $persianunits = self::getObjectListFromDB("SELECT id, city, type, strength, location FROM MILITARY WHERE city=\"".PERSIA."\" AND location=\"".CONTROLLED_PERSIANS."\"");

        foreach($persianleaders as $persian) {
            // send notification that moves units from city stack to player's military zone
            self::notifyAllPlayers("takePersians", '', array(
                'player_id' => $persian,
                'military' => $persianunits,
            ));
        }
    }

    /**
     * Move all military units from a battle location back to the city where it belongs
     */
    function returnMilitaryUnits($tile) {
        $location = $tile['location'];
        $this->Battles->returnCounters($location);
        self::notifyAllPlayers("returnMilitary", '', array(
            'location' => $location,
            'slot' => $tile['slot']
        ));
    }

    /**
     * Assumes all checks have been done. Send a military unit to a battle location.
     */
    function sendToBattle($player_id, $mil, $battlepos) {

        $id = $mil['id'];
        $location = $mil['battle'];
        $players = self::loadPlayersBasicInfos();
        $counter = $this->Battles->getCounter($id);

        self::DbQuery("UPDATE MILITARY SET location=\"$location\", battlepos=$battlepos WHERE id=$id");
        $role = $this->getRoleName($battlepos);
        $slot = self::getUniqueValueFromDB("SELECT card_location_arg from LOCATION WHERE card_type_arg=\"$location\"");

        foreach (array_keys($players) as $pid) {
            self::notifyPlayer($pid, "sendMilitary", clienttranslate('${player_name} sends ${city_name} ${unit_type} to ${location_name} as ${battlerole}'), array(
                'i18n' => ['location_name', 'battlerole', 'unit_type', 'city_name'],
                'player_id' => $player_id,
                'player_name' => $players[$player_id]['player_name'],
                'id' => ($pid == $player_id) ? $counter['id'] : 0,
                'type' => $counter['type'],
                'unit_type' => $this->getUnitName($counter['type']),
                'strength' => ($pid == $player_id) ? $counter['strength'] : 0,
                'city' => $counter['city'],
                'city_name' => $this->Cities->getNameTr($counter['city']),
                'battlepos' => $battlepos,
                'battlerole' => $role,
                'location' => $location,
                'slot' => $slot,
                'location_name' => $this->Locations->getName($location),
                'preserve' => ['city', 'location'],
            ));
        }
    }

    /**
     * Get the translated label for a battle side
     */
    function getRoleName($role) {
        $rolename = "";
        if ($role == ATTACKER+MAIN) {
            $rolename = clienttranslate('Main attacker');
        } else if ($role == ATTACKER+ALLY) {
            $rolename = clienttranslate('Allied attacker');
        } else if ($role == DEFENDER+MAIN) {
            $rolename = clienttranslate('Main defender');
        } else if ($role == DEFENDER+ALLY) {
            $rolename = clienttranslate('Allied defender');
        } else {
            throw new BgaVisibleSystemException("Unrecognized role: $role"); // NOI18N
        }
        return $rolename;
    }

    /**
     * Faster check, just return true if at least one unit in deadpool to be retrieved by this player.
     */
    function hasDeadPool($player_id) {
        foreach($this->Cities->cities() as $cn) {
            if ($this->Cities->isLeader($player_id, $cn)) {
                $dead = self::getObjectListFromDB("SELECT id FROM MILITARY WHERE city=\"$cn\" AND location='deadpool'", true);
                if (!empty($dead)) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Get an associative array of two-shard tiles held by this player: id => city
     * @param $player_id
     */
    function twoShardTiles($player_id) {
        // "influence" cards are either 2 shards, or "Any" tiles (others are Candidate or Assassin)
        $shards = self::getCollectionFromDB("SELECT card_id id, card_type city FROM INFLUENCE WHERE card_location=$player_id AND NOT card_type=\"any\" AND card_type_arg=\"".INFLUENCE."\"", true);
        return $shards;
    }

    /**
     * Get an associative array of one-shard tiles held by this player: id => city
     * @param $player_id
     */
    function oneShardTiles($player_id) {
        // "influence" cards are either 2 shards, or "Any" tiles (others are Candidate or Assassin)
        $shards = self::getCollectionFromDB("SELECT card_id id, card_type city FROM INFLUENCE WHERE card_location=$player_id AND (card_type=\"any\" OR card_type_arg!=\"".INFLUENCE."\")", true);
        return $shards;
    }

    /**
     * Check for either 1 or 2-shard tiles held in hands.
     * @param num 1 or 2
     * @return true if at least 1 player still has a tile of num shards
     */
    private function isTileLeft($num) {
        $players = self::loadPlayersBasicInfos();
        foreach(array_keys($players) as $player_id) {
            $shards = ($num == 1) ? $this->oneShardTiles($player_id) : $this->twoShardTiles($player_id);
            if (!empty($shards)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Does anyone have a 2-shard tile in hand?
     */
    function isTwoShardTileLeft() {
        return $this->isTileLeft(2);
    }

    /**
     * Does anyone have a 1-shard tile in hand?
     */
    function isOneShardTileLeft() {
        return $this->isTileLeft(1);
    }

    /**
     * Return double associative array,
     * all cities this player is leader of, with lowest strength Hoplite and/or Trireme from the deadpool for each
     */
    function deadPoolUnits($player_id) {
        $deadpool = array();
        foreach($this->Cities->cities() as $cn) {
            if ($player_id == $this->Cities->isLeader($player_id, $cn)) {
                $dead = self::getObjectListFromDB("SELECT id, city, type, strength FROM MILITARY WHERE city=\"$cn\" AND location='deadpool'");
                if (!empty($dead)) {
                    $deadpool[$cn] = array();
                    $hop = null;
                    $tri = null;
                    foreach($dead as $d) {
                        if ($d['type'] == HOPLITE) {
                            if ($hop == null) {
                                $hop = $d;
                            } else if ($hop['strength'] > $d['strength']) {
                                $hop = $d;
                            }
                        } elseif ($d['type'] == TRIREME) {
                            if ($tri == null) {
                                $tri = $d;
                            } else if ($tri['strength'] > $d['strength']) {
                                $tri = $d;
                            }
                        }
                    }
                    if ($hop != null) {
                        $deadpool[$cn][HOPLITE] = $hop;
                    }
                    if ($tri != null) {
                        $deadpool[$cn][TRIREME] = $tri;
                    }
                }
            }
        }
        return $deadpool;
    }

    /**
     * Does a player have permission to defend a location?
     */
    function hasDefendPermission($player_id, $location) {
        $hasPerm = false;
        $permissions = self::getUniqueValueFromDB("SELECT permissions FROM LOCATION WHERE card_type_arg=\"$location\"");
        if ($permissions != null) {
            $perms = explode(",", $permissions);
            $hasPerm = in_array($player_id, $perms);
        }
        return $hasPerm;
    }

    /**
     * Let a player give another player permission to defend a location.
     */
    function giveDefendPermission($player_id, $location) {
        // make sure assigner owns it
        $assigner = self::getActivePlayerId();
        $location = self::getNonEmptyObjectFromDB("SELECT card_type city, permissions FROM LOCATION WHERE card_type_arg=\"$location\"");
        if (!$this->Cities->isLeader($assigner, $location['city'])) {
            throw new BgaUserException(self::_("You do not own this location's city"));
        }
        $permissions = $location['permissions'] == null ? [] : explode(",", $location['permissions']);
        if (!in_array($player_id, $permissions)) {
            $permissions[] = $player_id;
            $newperms = implode(',', $permissions);
            self::DbQuery("UPDATE LOCATION SET permissions=$newperms");
        }
    }

    /**
     * Check whether we have reached endgame
     */
    function isEndGame() {
        if (self::getStat('turns_number') == 3) {
            self::notifyAllPlayers("endGame", clienttranslate('End of Turn 3: Game Over!'), []);
            return true;
        }
        foreach(["sparta", "athens"] as $civ) {
            if ($this->Cities->getDefeats($civ) >= 4) {
                self::notifyAllPlayers("endGame", clienttranslate('${city_name} has suffered 4 defeats: Game Over!'), [
                    'i18n' => ['city_name'],
                    'city' => $civ,
                    'city_name' => $this->Cities->getNameTr($civ),
                    'preserve' => ['city'],
                ]);
                return true;
            }
        }
        return false;
    }

    /**
     * Discard remaining influence cards on board, shuffle, and deal new ones.
     */
    function dealNewInfluence() {
        $this->influence_tiles->moveAllCardsInLocation(BOARD, DISCARD);
        $this->influence_tiles->shuffle(DECK);
        for ($i = 1; $i <= 10; $i++) {
            $this->influence_tiles->pickCardForLocation(DECK, BOARD, $i);
        }

        self::notifyAllPlayers("newInfluence", '', array(
            'influence' => $this->getInfluenceDisplay(),
            'decksize' => $this->influence_tiles->countCardInLocation(DECK),
        ));
    }

    /**
     * Move old locations to 
     */
    function dealNewLocations() {
        $this->location_tiles->shuffle(DECK);
        for ($i = 1; $i <= 7; $i++) {
            $this->location_tiles->pickCardForLocation(DECK, BOARD, $i);
        }
        $locations = self::getObjectListFromDB("SELECT card_id id, card_type city, card_type_arg location, card_location_arg slot FROM LOCATION WHERE card_location='".BOARD."'");
        self::notifyAllPlayers("newLocations", '', array(
            'locations' => $locations
        ));
    }

    /**
     * Get all Influence tiles in current display.
     */
    function getInfluenceDisplay() {
        $influence = self::getObjectListFromDB("SELECT card_id id, card_type city, card_type_arg type, card_location location, card_location_arg slot FROM INFLUENCE WHERE card_location != \"".DECK."\" AND card_location != \"".DISCARD."\"");
        return $influence;
    }

    /**
     * @param {string} unit HOPLITE or TRIREME
     * @return translatation marked string
     */
    function getUnitName($unit) {
        $units = array(
            HOPLITE => clienttranslate('Hoplite'),
            TRIREME => clienttranslate('Trireme'),
        );
        return $units[$unit];
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Player actions
//////////// 

    /**
     * A player clicked a Special Tile Button or the Pass button.
     * Skipped by Plague and Alkibiades.
     */
    function useSpecialTile($player_id, $use) {
        // $this->checkAction('useSpecial');
        if ($use) {
            $this->playSpecialTile($player_id);
        }
        // after playing tile, or if passed
        $nextstate = "";
        if ($this->getGameStateValue('influence_phase') == 0) {
            // if it was Slave Revolt, next Commit
            $nextstate = "nextCommit";
        } else {
            // otherwise we're going to next player
            $nextstate = "nextPlayer";
        }
        $this->gamestate->nextState($nextstate);
    }

    /**
     * A player clicked a Special Tile Button or the Pass button during battle.
     */
    function useSpecialBattleTile($player_id, $use) {
        if ($use) {
            $this->playSpecialTile($player_id);
        }
        $this->gamestate->setPlayerNonMultiactive( $player_id, "" );
    }

    /**
     * Active a Special Tile.
     * Applies only to special tiles that are yes/no
     * @param player_id
     */
    function playSpecialTile($player_id) {
        $special = $this->SpecialTiles->checkSpecialTile($player_id);
        // sanity check
        $t = $special['tile'];

        switch ($t) {
            case PERIKLES:
                $this->playPerikles($player_id);
                break;
            case BRASIDAS:
                $this->setGameStateValue(BRASIDAS, 1);
                break;
            case PHORMIO:
                $this->setGameStateValue(PHORMIO, 1);
                break;
            default:
                throw new BgaVisibleSystemException("Invalid special tile: $t"); // NOI18N
        }
    }

    /**
     * Play Perikles Special tile.
     */
    function playPerikles($player_id) {
        $this->flipSpecialTile($player_id);
        $this->addInfluenceToCity('athens', $player_id, 2);
    }

    /**
     * Play the Alkibiades Special tile
     */
    function playAlkibiades($owner1, $from_city1, $to_city1, $owner2, $from_city2, $to_city2) {
        if (!$this->checkAction("useSpecialTile", false)) {
            $this->checkAction("takeInfluence");
        }
         
        $player_id = self::getCurrentPlayerId();
        $this->SpecialTiles->checkSpecialTile($player_id, ALKIBIADES);

        // check players have influence
        $influence1 = $this->Cities->influence($owner1, $from_city1);
        if ($influence1 == 0) {
            throw new BgaVisibleSystemException("$owner1 does not have any cubes to remove from $from_city1"); // NOI18N
        }
        $influence2 = $this->Cities->influence($owner2, $from_city2);
        if ($influence2 == 0) {
            throw new BgaVisibleSystemException("$owner2 does not have any cubes to remove from $from_city2"); // NOI18N
        }
        // check the case of moving two cities of the same player from the same city
        if ($owner1 == $owner2 && $from_city1 == $from_city2) {
            if ($influence1 < 2) {
                throw new BgaVisibleSystemException("$owner2 does not have 2 to remove from $from_city2"); // NOI18N
            }
        }
        // passed all checks.
        $this->flipSpecialTile($player_id);
        $this->Cities->changeInfluence($from_city1, $owner1, -1);
        $this->Cities->changeInfluence($from_city2, $owner2, -1);
        $this->Cities->changeInfluence($to_city1, $owner1, 1);
        $this->Cities->changeInfluence($to_city2, $owner2, 1);
        $this->alkibiadesNotify($owner1, $from_city1, $to_city1);
        $this->alkibiadesNotify($owner2, $from_city2, $to_city2);
        if ($player_id == self::getActivePlayerId() && $this->getStateName() == "specialTile") {
            $this->gamestate->nextState("nextPlayer");
        }
    }

    /**
     * Send a notification about a cube being moved between cities.
     */
    function alkibiadesNotify($player_id, $city, $city2) {
        $players = self::loadPlayersBasicInfos();
        self::notifyAllPlayers("alkibiadesMove", clienttranslate('1 of ${player_name}\'s cubes moved from ${city_name} to ${city_name2}'), array(
            'i18n' => ['city_name', 'city_name2'],
            'player_id' => $player_id,
            'player_name' => $players[$player_id]['player_name'],
            'city' => $city,
            'city2' => $city2,
            'city_name' => $this->Cities->getNameTr($city),
            'city_name2' => $this->Cities->getNameTr($city2),
            'preserve' => ['player_id', 'city', 'city2'],
        ));
    }

    /**
     * Play Plague special tile.
     */
    function playPlague($city) {
        if (!$this->checkAction("useSpecialTile", false)) {
            $this->checkAction("takeInfluence");
        }
        $player_id = self::getCurrentPlayerId();
        $this->SpecialTiles->checkSpecialTile($player_id, PLAGUE);

        $this->flipSpecialTile($player_id);
        $players = self::loadPlayersBasicInfos();
        // how many cubes does each player have? Count candidates
        foreach ($players as $p => $player) {
            $cubes = $this->Cities->cubesInCity($p, $city);

            $to_reduce = floor($cubes/2);
            if ($to_reduce > 0) {
                self::notifyAllPlayers("plagueReduce", clienttranslate('Plague removes ${nbr} of ${player_name}\'s cubes in ${city_name}'), array(
                    'i18n' => ['city_name'],
                    'player_id' => $p,
                    'player_name' => $player['player_name'],
                    'city' => $city,
                    'city_name' => $this->Cities->getNameTr($city),
                    'nbr' => $to_reduce,
                    'preserve' => ['city'],
                ));
                $this->Cities->changeInfluence($city, $p, -$to_reduce);
                for ($i = 0; $i < $to_reduce; $i++) {
                    self::notifyAllPlayers("cubeRemoved", '', array(
                        'candidate_id' => $p,
                        'city' => $city,
                        'preserve' => ['candidate_id', 'city']
                    ));
                }
            }
        }
        if ($player_id == self::getActivePlayerId() && $this->getStateName() == "specialTile") {
            $this->gamestate->nextState("nextPlayer");
        }
    }

    /**
     * Player selected Slave Revolt
     */
    function playSlaveRevolt($revoltlocation) {
        if (!$this->checkAction("useSpecialTile", false)) {
            $this->checkAction("assignUnits");
        }
        
        // sanity check - there is a Sparta leader
        $sparta_leader = $this->Cities->getLeader("sparta");
        if (empty($sparta_leader)) {
            throw new BgaVisibleSystemException("No Sparta Leader!"); // NOI18N
        }

        $player_id = self::getCurrentPlayerId();
        $this->SpecialTiles->checkSpecialTile($player_id, SLAVEREVOLT);

        $location = "";
        $location_name = "";
        // if it's "sparta" then take it from the player's pool
        if ($revoltlocation == "sparta") {
            $players = self::loadPlayersBasicInfos();
            $player_name = $players[$sparta_leader]['player_name'];
            $location = $sparta_leader;
            $location_name = sprintf(clienttranslate("%s's unit pool"), $player_name);
        } else {
            // it's a battle tile
            $location = $revoltlocation;
            $location_name = $this->Locations->getName($location);
        }
        // locaion is now either location tile name or Sparta player id
        // get all Hoplite counters
        $hoplites = self::getObjectListFromDB("SELECT id FROM MILITARY WHERE city=\"sparta\" AND type=\"".HOPLITE."\" AND location=\"$location\"");
        if (empty($hoplites)) {
            throw new BgaVisibleSystemException("No Spartan Hoplites at $location"); // NOI18N
        }

        $this->flipSpecialTile($player_id);

        // randomize and pick one
        shuffle($hoplites);
        $revolted = array_pop($hoplites);
        $id = $revolted['id'];
        // need to flip the counter
        $counter = self::getObjectListFromDB("SELECT id, city, type, strength, location, battlepos FROM MILITARY WHERE id=$id")[0];

        // relocate it to Sparta
        self::DbQuery("UPDATE MILITARY SET location=\"sparta\", battlepos=0 WHERE id=$id");

        // this will flip the counter, and move it to Sparta
        self::notifyAllPlayers("slaveRevolt", clienttranslate('Hoplite counter returned to Sparta from ${location_name}'), array(
            'i18n' => ['location_name'],
            'military' => $counter,
            'location' => $revoltlocation, // may be sparta or a battle name
            'location_name' => $location_name,
            'sparta_player' => $sparta_leader,
        ));
    }

    /**
     * Player played their Special Tile. Flip it and mark it used.
     */
    function flipSpecialTile($player_id) {
        $tile = $this->SpecialTiles->getSpecialTile($player_id);
        $tile_name = $this->SpecialTiles->getSpecialTileName($player_id);
        $players = self::loadPlayersBasicInfos();
        self::notifyAllPlayers("playSpecial", clienttranslate('${player_name} uses Special tile ${special_tile}'), array(
            'i18n' => ['special_tile'],
            'player_id' => $player_id,
            'player_name' => $players[$player_id]['player_name'],
            'tile' => $tile,
            'special_tile' => $tile_name,
        ));
        $this->SpecialTiles->markUsed($player_id);
    }

    /**
     * Spartan player chose first player for influence phase.
     */
    function chooseNextPlayer($first_player) {
        $this->checkAction('chooseNextPlayer');
        $players = self::loadPlayersBasicInfos();

        $player_id = self::getActivePlayerId();
        self::notifyAllPlayers("spartanChoice", clienttranslate('${player_name} chooses ${candidate_name} to commit forces first'), array(
            'player_id' => $player_id,
            'player_name' => $players[$player_id]['player_name'],
            'candidate_id' => $first_player,
            'candidate_name' => $players[$first_player]['player_name'],
            'preserve' => ['player_id', 'candidate_id'],
        ));
        $this->setGameStateValue("spartan_choice", $first_player);
        $this->gamestate->nextState();
    }

    /**
     * Player chose an Influence tile
     */
    function takeInfluence($influence_id) {
        $this->checkAction( 'takeInfluence' );
        $influence_card = self::getObjectFromDB("SELECT card_id id, card_type city, card_type_arg type, card_location location, card_location_arg slot FROM INFLUENCE WHERE card_id=$influence_id");

        // is it on the board?
        if ($influence_card['location'] != BOARD) {
            throw new BgaUserException(self::_("This card is not selectable"));
        }
        $player_id = self::getActivePlayerId();
        // has this player already selected a card from this city?
        $descriptors = $this->influenceTileDescriptors($influence_card);

        $city = $influence_card['city'];
        $city_name = $descriptors[0];

        if ($this->hasCityInfluenceTile($player_id, $city)) {
            // only allowed if there are no other Influence cards he can take
            $availablecities = self::getObjectListFromDB("SELECT card_type city FROM INFLUENCE WHERE card_location=\"".BOARD."\"", true);
            foreach($availablecities as $cn) {
                if (!$this->hasCityInfluenceTile($player_id, $cn)) {
                    // at least one city that you don't have yet
                    throw new BgaUserException(sprintf(self::_("You may not take another %s Influence tile"), $city_name));
                }
            }
        }
        // got past checks, so it's a valid choice
        $this->influence_tiles->insertCardOnExtremePosition($influence_id, $player_id, true);
        $players = self::loadPlayersBasicInfos();

        $inf_type = $descriptors[2];
        if (!empty($inf_type)) {
            $inf_type = "(".$inf_type.")";
        }

        $slot = $influence_card['slot'];
        $this->setGameStateValue("last_influence_slot", $slot);

        self::notifyAllPlayers("influenceCardTaken", clienttranslate('${player_name} took ${shards}-Shard ${city_name} tile ${inf_type}'), array(
            'i18n' => ['city_name', 'inf_type'],
            'player_name' => $players[$player_id]['player_name'],
            'player_id' => $player_id,
            'city' => $city,
            'city_name' => $city_name,
            'shards' => $descriptors[1],
            'inf_type' => $inf_type,
            'card_id' => $influence_id,
            'slot' => $slot,
            'tile' => $influence_card,
            'preserve' => ['player_id', 'city'],
        ));

        $state = ($city == "any") ? "choosePlaceCube" : "placeCube";
        $this->gamestate->nextState( $state );
    }

    /**
     * Player chose a city with an Any card.
     */
    function placeAnyCube($city) {
        $this->checkAction( 'placeAnyCube' );
        $player_id = self::getActivePlayerId();
        $this->addInfluenceToCity($city, $player_id, 1);
        $state = "nextPlayer";
        if ($this->SpecialTiles->canPlaySpecial($player_id, "influence")) {
            $state = "useSpecial";
        }
        $this->gamestate->nextState($state);
    }

    /**
     * Player is selecting a candidate for a city.
     */
    function proposeCandidate($city, $candidate_id) {
        $this->checkAction('proposeCandidate');
        $actingplayer = self::getActivePlayerId();
        $city_name = $this->Cities->getNameTr($city);
        // player must have a cube in the city
        if (!$this->Cities->hasInfluence($actingplayer, $city)) {
            throw new BgaUserException(sprintf(self::_("You cannot propose a Candidate in %s: you have no Influence cubes in this city"), $city_name));
        }

        $players = self::loadPlayersBasicInfos();
        $candidate_name = $players[$candidate_id]['player_name'];
        // is there an available candidate slot?
        $slot = "a";
        $a = $this->Cities->getCandidate($city, "a");
        if (!empty($a)) {
            $b = $this->Cities->getCandidate($city, "b");
            if (!empty($b)) {
                throw new BgaUserException(sprintf(self::_("%s has no empty Candidate spaces"), $city_name));
            } else if ($a == $candidate_id) {
                throw new BgaUserException(sprintf(self::_("%s is already a Candidate in %s"), $candidate_name, $city_name));
            }
            $slot = "b";
        }
        // does the nominated player have cubes there?
        $cubes = $this->Cities->influence($candidate_id, $city);
        if (empty($cubes)) {
            throw new BgaUserException(sprintf(self::_("%s has no Influence cubes in %s"), $candidate_name, $city_name));
        }
        // passed checks, can assign Candidate
        $this->Cities->changeInfluence($city, $candidate_id, -1);

        $this->Cities->setCandidate($candidate_id, $city, $slot);

        $c = ($slot == "a") ? ALPHA : BETA;
        self::notifyAllPlayers("candidateProposed", clienttranslate('${player_name} proposes ${candidate_name} as Candidate ${candidate} in ${city_name}'), array(
            'i18n' => ['city_name'],
            'player_id' => $actingplayer,
            'player_name' => $players[$actingplayer]['player_name'],
            'candidate_id' => $candidate_id,
            'candidate_name' => $candidate_name,
            'city' => $city,
            'city_name' => $city_name,
            'candidate' => $c,
            'preserve' => ['player_id', 'candidate_id', 'city'],
        ) );
        $state = $this->SpecialTiles->canPlaySpecial($actingplayer, "influence") ? "useSpecial" : "nextPlayer";

        $this->gamestate->nextState($state);
    }

    /**
     * Player chose a cube to remove.
     * $cube is a, b, or a number
     */
    function chooseRemoveCube($target_id, $city, $cube) {
        $this->checkAction('chooseRemoveCube');
        $player_id = self::getActivePlayerId();
        $players = self::loadPlayersBasicInfos();
        $city_name = $this->Cities->getNameTr($city);
        if ($cube == "a") {
            $alpha = $this->Cities->getCandidate($city, "a");
            if ($alpha != $target_id) {
                throw new BgaVisibleSystemException("Missing cube at $city $cube"); // NO18N
            }
            $this->Cities->clearCandidate($city, "a");
            self::notifyAllPlayers("cubeRemoved", clienttranslate('${player_name} removed ${candidate_name}\'s Candidate ${candidate} in ${city_name}'), array(
                'i18n' => ['city_name'],
                'player_id' => $player_id,
                'player_name' => $players[$player_id]['player_name'],
                'candidate_id' => $target_id,
                'candidate_name' => $players[$target_id]['player_name'],
                'candidate' => ALPHA,
                'city' => $city,
                'city_name' => $city_name,
                'preserve' => ['player_id', 'candidate_id', 'city']
            ));
            // promote candidate b to emptied a slot
            $beta = $this->Cities->getCandidate($city, "b");
            if (!empty($beta)) {
                $this->Cities->clearCandidate($city, "b");
                $this->Cities->setCandidate($beta, $city, "a");
                self::notifyAllPlayers("candidatePromoted", clienttranslate('${candidate_name}\'s Candidate moves from ${B} to ${A} in ${city_name}'), array(
                    'i18n' => ['city_name'],
                    'candidate_id' => $beta,
                    'candidate_name' => $players[$beta]['player_name'],
                    'A' => ALPHA,
                    'B' => BETA,
                    'city' => $city,
                    'city_name' => $city_name,
                    'preserve' => ['candidate_id', 'city']
    
                ));
            }
        } else if ($cube == "b") {
            $alpha = $this->Cities->getCandidate($city, "a");
            if (empty($alpha)) {
                // should not happen!
                throw new BgaVisibleSystemException("Unexpected game state: Candidate B with no Candidate A"); // NOI18N
            }
            $beta = $this->Cities->getCandidate($city, "b");
            if ($beta != $target_id) {
                throw new BgaVisibleSystemException("Missing cube at $city $cube"); // NOI18N
            }
            $this->Cities->clearCandidate($city, "b");
            self::notifyAllPlayers("cubeRemoved", clienttranslate('${player_name} removed ${candidate_name}\'s Candidate ${candidate} in ${city_name}'), array(
                'i18n' => ['city_name'],
                'player_id' => $player_id,
                'player_name' => $players[$player_id]['player_name'],
                'candidate_id' => $target_id,
                'candidate_name' => $players[$target_id]['player_name'],
                'candidate' => BETA,
                'city' => $city,
                'city_name' => $city_name,
                'preserve' => ['player_id', 'candidate_id', 'city']
            ));
        } else {
            $this->Cities->changeInfluence($city, $target_id, -1);
            self::notifyAllPlayers("cubeRemoved", clienttranslate('${player_name} removed one of ${candidate_name}\'s Influence cubes in ${city_name}'), array(
                'i18n' => ['city_name'],
                'player_id' => $player_id,
                'player_name' => $players[$player_id]['player_name'],
                'candidate_id' => $target_id,
                'candidate_name' => $players[$target_id]['player_name'],
                'city' => $city,
                'city_name' => $city_name,
                'preserve' => ['player_id', 'candidate_id', 'city']
            ));
        }
        $state = $this->SpecialTiles->canPlaySpecial($player_id, "influence") ? "useSpecial" : "nextPlayer";

        $this->gamestate->nextState($state);
    }

    function chooseDeadUnits() {
        throw new BgaUserException("Take dead not implemented yet");
    }

    /**
     * Send units to battle locations.
     * @param unitstr a space-delimited string id_attdef_battle (or empty)
     * @param cube empty string or cube spent for extra units
     */
    function assignUnits($unitstr, $cube) {
        $this->checkAction('assignUnits');
        $player_id = self::getActivePlayerId();

        if (trim($unitstr) == "") {
            $this->noCommitUnits($player_id);
        } else {
            $this->validateMilitaryCommits($player_id, $unitstr, $cube);
        }
        $state = "nextPlayer";
        if ($this->SpecialTiles->canPlaySpecial($player_id, "commit")) {
            $state = "useSpecial";
        }
        $this->gamestate->nextState($state);
    }

    /**
     * Send a message when there are no units being sent.
     */
    function noCommitUnits($player_id) {
        self::notifyAllPlayers('noCommits', clienttranslate('${player_name} commits no forces'), array(
            'player_id' => $player_id,
            'player_name' => self::getActivePlayerName(),
            'preserve' => ['player_id'],
        ));
    }

    /**
     * Make sure all commitment assignments are valid.
     */
    function validateMilitaryCommits($player_id, $unitstr, $cube) {
        // do all the checks for whether this is a valid action
        // can I commit extra forces from the chosen city?
        if ($cube != "") {
            if (!$this->Cities->canSpendInfluence($player_id, $cube)) {
                throw new BgaUserException(sprintf(self::_("You cannot send extra units from %s"), $this->Cities->getNameTr($cube)));
            }
        }

        $units = explode(" ", trim($unitstr));
        // get main attackers/defenders location => player
        $main_attacker = [];
        $main_defender = [];
        $myforces = array(
            'attack' => [],
            'defend' => [],
        );
        // MAKE NO CHANGES IN DB until this loop is completed!
        foreach($units as $unit) {
            [$id, $side, $location] = explode("_", $unit);
            $counter = self::getObjectFromDB("SELECT id, city, type, location, strength FROM MILITARY WHERE id=$id");
            $counter['battle'] = $location;
            // Is this unit in my pool?
            $unit_desc = $this->unitDescription($counter['city'], $counter['strength'], $counter['type'], $location);

            $attacker = $this->Battles->getAttacker($location);
            $defender = $this->Battles->getDefender($location);
            if ($attacker != null) {
                $main_attacker[$location] = $attacker;
            }
            if ($defender != null) {
                $main_defender[$location] = $defender;
            }
            if ($side == "attack") {
                $this->validateAttacker($player_id, $counter, $unit_desc);

                // Is there already a main attacker who is not me?
                if ($attacker == null) {
                    // I am now the main attacker
                    $main_attacker[$location] = $player_id;
                }
                $myforces['attack'][] = $counter;
            } else if ($side == "defend") {
                $this->validateDefender($player_id, $counter, $unit_desc);

                // is there already a main defender?
                if ($defender == null) {
                    // I am now the main defender
                    $main_defender[$location] = $player_id;
                }
                $myforces['defend'][] = $counter;
            }
        }

        // all units passed all tests for valid assignment
        // did we spend an influence cube?
        if ($cube != "" && count($units) > 2) {
            $this->Cities->changeInfluence($cube, $player_id, -1);
            self::notifyAllPlayers('spentInfluence', clienttranslate('${player_name} spent an Influence cube from ${city_name} to send extra units'), array(
                'i18n' => ['city_name'],
                'candidate_id' => $player_id, // candidate because that's the notif arg
                'player_id' => $player_id,
                'player_name' => self::getActivePlayerName(),
                'city' => $cube,
                'city_name' => $this->Cities->getNameTr($cube),
                'preserve' => ['candidate_id', 'city'],
            ));
        }
        // now ship 'em off
        foreach($myforces as $attdef => $forces) {
            foreach($forces as $f) {
                $battle = $f['battle'];
                $main = $attdef == "attack" ? $main_attacker[$battle] : $main_defender[$battle];
                if ($main == $player_id) {
                    // I became main
                    $battlepos = MAIN + ($attdef == "attack" ? ATTACKER : DEFENDER);
                    $col = $attdef == "attack" ? "attacker" : "defender";
                    self::DbQuery("UPDATE LOCATION SET $col=$player_id WHERE card_type_arg=\"$battle\"");
                } else {
                    $battlepos = ALLY + ($attdef == "attack" ? ATTACKER : DEFENDER);
                }
                $this->sendToBattle($player_id, $f, $battlepos);
            }
        }
    }

    /**
     * Player chose a counter to die.
     */
    function chooseLoss($city) {
        $this->checkAction('chooseLoss');
        // where is the current battle?
        $casualties = $this->getPossibleCasualties();
        if (empty($casualties)) {
            throw new BgaVisibleSystemException("no casualties at current battle location!"); // NOI18N
        } else {
            $deadpool = false;
            // get the first one matching the chosen city
            foreach ($casualties as $counter) {
                if ($counter['city'] == $city) {
                    $this->moveToDeadpool($counter);
                    $deadpool = true;
                    break;
                }
            }
            if (!$deadpool) {
                throw new BgaVisibleSystemException("no valid counter found for $city"); // NOI18N
            }
        }
    }

    /**
     * Checks whether a unit can attack a city, throws an Exception if it fails.
     * Also marks unit as Allies with all attackers and At War with all Defenders.
     */
    private function validateAttacker($player_id, $counter, $unit_desc) {
        if ($counter['location'] != $player_id) {
            // is this a Persiam?
            if (!($counter['location'] == CONTROLLED_PERSIANS && $this->Cities->isLeader($player_id, PERSIA))) {
                throw new BgaUserException(sprintf(self::_("%s is not in your available pool"), $unit_desc));
            }
        }
        $location = $counter['battle'];
        $city = $this->Locations->getCity($location);

        // does this location belong to my own city?
        if ($this->Cities->isLeader($player_id, $city)) {
            throw new BgaUserException(sprintf(self::_("%s cannot attack a city you control!"), $unit_desc));
        }
        // is this unit allied with the defender (including because a unit was already played as a defender)?
        if ($this->Cities->isAlly($counter['city'], $city)) {
            throw new BgaUserException(sprintf(self::_("%s cannot attack a city it is allied with!"), $unit_desc));
        }

        // is counter at war with any of the other attackers?
        $attackers = $this->Cities->getAllAttackers($location);
        foreach($attackers as $att) {
            if ($this->Cities->atWar($counter['city'], $att)) {
                throw new BgaUserException(sprintf(self::_("%s cannot join battle with hostile units"), $unit_desc));
            }
        }

        // are we sending a trireme to a land battle?
        if ($this->Locations->isLandBattle($location) && $counter['type'] == TRIREME) {
            throw new BgaUserException(sprintf(self::_("%s cannot be sent to a land battle"), $unit_desc));
        }
        // passed all checks. Declare war with all defenders.
        $defenders = $this->Cities->getAllDefenders($location, $city);
        foreach($defenders as $def) {
            $this->Cities->setWar($counter['city'], $def);
        }
        // and declare allies with all attackers
        foreach($attackers as $att) {
            $this->Cities->setAlly($counter['city'], $att);
        }
    }

    /**
     * Checks whether a unit can defend a city, throws an Exception if it fails.
     */
    private function validateDefender($player_id, $counter, $unit_desc) {
        if ($counter['location'] != $player_id) {
            throw new BgaUserException(sprintf(self::_("%s is not in your available pool"), $unit_desc));
        }

        $location = $counter['battle'];

        // am I at war with any of the defenders?
        $city = $this->Locations->getCity($location);
        $defenders = $this->Cities->getAllDefenders($location, $city);
        foreach($defenders as $def) {
            if ($this->Cities->atWar($counter['city'], $def)) {
                throw new BgaUserException(sprintf(self::_("%s cannot join battle with hostile units"), $unit_desc));
            }
        }
        $city = $this->Locations->getCity($location);
        // Do I control this city? If not, I need permission from defender
        if (!$this->Cities->isLeader($player_id, $city)) {
            if (!$this->hasDefendPermission($player_id, $location)) {
                $city_name = $this->Cities->getNameTr($city);
                $location_name = $this->Locations->getName($location);
                throw new BgaUserException(self::_("You need permission from the leader of $city_name to defend $location_name"));
            }
        }

        // are we sending a trireme to a land battle?
        if ($this->Locations->isLandBattle($location) && $counter['type'] == TRIREME) {
            throw new BgaUserException(sprintf(self::_("%s cannot be sent to a land battle"), $unit_desc));
        }

        // passed all checks. Declare war with all attackers
        $attackers = $this->Cities->getAllAttackers($location);
        foreach($attackers as $att) {
            $this->Cities->setWar($counter['city'], $att);
        }
        // and declare allies with all attackers
        foreach($defenders as $def) {
            $this->Cities->setAlly($counter['city'], $def);
        }
    }

    /**
     * When there are no forces on either side at a city tile.
     * According to Martin Wallace, should almost never happen!
     * Neither side gets a tile or any cubes.
     * https://boardgamegeek.com/thread/1109420/collection-all-martin-wallace-errata-clarification
     * @param $battle battle DB row from LOCATION
     */
    function noBattle($tile) {
        $location = $tile['location'];
        self::notifyAllPlayers('unclaimedTile', clienttranslate('No battle at ${location_name}; no one claims the tile'), array(
            'i18n' => ['location_name'],
            'location' => $location,
            'location_name' => $this->Locations->getName($location),
        ));
        $this->unclaimedTile($tile);
    }

    /**
     * Only one side came to the party.
     * Per the rules:
     *  1. If no one attacks the city, the defender does not get the tile, but gets 2 cubes.
     * According to Martin Wallace: 
     *  If there are no units in the second round of combat:
     *      a) If the attacker was the only one to send units, but not to the second round of combat,
     *      then no one gets the tile, and defender does not get cubes.
     *      b) If only the defender has units in the first round, and no one has units in the second round,
     *      then no one gets the tile, and the defender gets 2 cubes.
     */
    function uncontestedBattle($tile) {
        $location = $tile['location'];
        $city = $tile['city'];
        $attacker = $tile['attacker'];
        $defender = $tile['defender'];
        // should be null attacker or defender but not both
        $noattacker = ($attacker == null);
        $nodefender = ($defender == null);
        // sanity check
        if (!($noattacker xor $nodefender)) {
            throw new BgaVisibleSystemException("uncontested battle state reached with 0 or 2 participants"); // NOI18N
        }

        $player_id = $noattacker ? $defender : $attacker;

        // am I the defender?
        if ($player_id ==$defender) {
            // there was a defender with no attacker: don't win the tile, but get two cubes
            self::notifyAllPlayers('unclaimedTile', clienttranslate('Defender wins uncontested battle at ${location_name}; no one claims the tile'), array(
                'i18n' => ['location_name'],
                'location' => $location,
                'location_name' => $this->Locations->getName($location),
                'preserve' => ['location']
            ));
            $this->addInfluenceToCity($city, $player_id, 2);
            $this->unclaimedTile($tile);
        } else {
            // attacker with no defenders
            // you must send units to the last round to win the tile
            $battletype = $this->Locations->getCombat($location, 2) ?? HOPLITE;

            // did we send units of that type?
            $attackingcounters = $this->Battles->getAttackingCounters($location, $battletype);
            if (empty($attackingcounters)) {
                // sent attackers to first round but not second
                self::notifyAllPlayers('unclaimedTile', clienttranslate('Attacker wins uncontested battle at ${location_name} but did not send forces to the second battle; no one claims the tile'), array(
                    'i18n' => ['location_name'],
                    'location' => $location,
                    'location_name' => $this->Locations->getName($location),
                    'preserve' => ['location']
                ));
                $this->unclaimedTile($tile);
            } else {
                self::notifyAllPlayers('uncontestedVictory', clienttranslate('Attacker wins uncontested battle at ${location_name}'), array(
                    'i18n' => ['location_name'],
                    'location' => $location,
                    'location_name' => $this->Locations->getName($location),
                    'preserve' => ['location']
                ));
                $this->defeatCity($city);
                $this->claimTile($player_id, $tile);
            }
        }
    }

    /**
     * Resolve a single round - Hoplite or Trireme battle. Roll until one side wins.
     * @param {string} location
     * @param {string} type HOPLITE or TRIREME
     * @param {int} attstr
     * @param {int} defstr
     * @return {int} ATTACKER or DEFENDER
     */
    function rollBattle($location, $type, $attstr, $defstr) {
        $crt = $this->Battles->getCRTColumn($attstr, $defstr);
        // highlight CRT Column
        $unit = $this->getUnitName($type);
        self::notifyAllPlayers('crtOdds', clienttranslate('${unit_type} battle at ${location_name}: attacker strength ${att} vs. defender strength ${def}, rolling in the ${odds} column'), array(
            'i18n' => ['unit_type', 'location_name'],
            'unit_type' => $unit,
            'location' => $location,
            'location_name' => $this->Locations->getName($location),
            'att' => $attstr,
            'def' => $defstr,
            'crt' => $crt,
            'odds' => $this->Battles->getOdds($crt),
            'preserve' => ['location']
        ));
        $winner = null;
        while ($this->getGameStateValue(ATTACKER_TOKENS) < 2 && $this->getGameStateValue(DEFENDER_TOKENS) < 2) {
            $this->rollDice($crt);
        }
        // one side has two tokens, but do they both?
        if ($this->getGameStateValue(ATTACKER_TOKENS) == 2 && $this->getGameStateValue(DEFENDER_TOKENS) == 2) {
            // they need to roll off until one side hits and the other doesn't
            $winner = $this->rollDice($crt);
            while ($winner == null) {
                $winner = $this->rollDice($crt);
            }
        } elseif ($this->getGameStateValue(ATTACKER_TOKENS) == 2) {
            $winner = ATTACKER;
        } elseif ($this->getGameStateValue(DEFENDER_TOKENS) == 2) {
            $winner = DEFENDER;
        } else {
            throw new BgaVisibleSystemException("Invalid condition at end of rollBattle"); // NOI18N
        }
        // we have a winner for this battle
        return $winner;
    }

    /**
     * One roll in a combat. Adjust combat tokens.
     * Returns side that scored a hit when the other didn't, or null if both or neither hit.
     * @return ATTACKER, DEFENDER, or null
     */
    function rollDice($crt) {
        $winner = null;
        $attacker_tn = $this->Battles->getTargetNumber(ATTACKER, $crt);
        $defender_tn = $this->Battles->getTargetNumber(DEFENDER, $crt);
        // roll for attacker
        $attd1 = bga_rand(1,6);
        $attd2 = bga_rand(1,6);
        $atthit = ($attd1 + $attd2) >= $attacker_tn;
        // roll for defender
        $defd1 = bga_rand(1,6);
        $defd2 = bga_rand(1,6);
        $defhit = ($defd1 + $defd2) >= $defender_tn;
        self::notifyAllPlayers("diceRoll", clienttranslate('Attacker rolls ${attd1} ${attd2} ${atttotal} (${atthit}), Defender rolls ${defd1} ${defd2} ${deftotal} (${defhit})'), array(
            'attd1' => $attd1,
            'attd2' => $attd2,
            'defd1' => $defd1,
            'defd2' => $defd2,
            'atthit' => $atthit,
            'atttotal' => $attd1+$attd2,
            'deftotal' => $defd1+$defd2,
            'defhit' => $defhit,
        ));
        // did either one hit?
        if ($atthit) {
            if ($this->getGameStateValue(ATTACKER_TOKENS) < 2) {
                $this->takeToken(ATTACKER);
            }
        }
        if ($defhit) {
            if ($this->getGameStateValue(DEFENDER_TOKENS) < 2) {
                $this->takeToken(DEFENDER);
            }
        }
        if ($atthit && !$defhit) {
            $winner = ATTACKER;
        } elseif ($defhit && !$atthit) {
            $winner = DEFENDER;
        }
        return $winner;
    }

    /**
     * One side scores a hit, send notification for token.
     * Assumes check has already been done for neither side having 2 tokens.
     * @param {int} ATTACKER or DEFENDER
     * @param {bool} bIsRound2 are we taking our starting token for winning previous round?
     */
    function takeToken($winner, $bIsRound2=false) {
        $token = "";
        $role = "";
        $side = "";
        if ($winner == ATTACKER) {
            $side = "attacker";
            $token = ATTACKER_TOKENS;
            $role = clienttranslate('Attacker');
        } elseif ($winner == DEFENDER) {
            $side = "defender";
            $token = DEFENDER_TOKENS;
            $role = clienttranslate('Defender');
        } else {
            throw new BgaVisibleSystemException("Invalid side to take Token: $winner"); // NOI18N
        }
        $msg = $bIsRound2 ? clienttranslate('${winner} starts second round with Battle Token') : clienttranslate('${winner} gains a Battle Token');
        self::notifyAllPlayers("takeToken", $msg, array(
            'i18n' => ['winner'],
            'side' => $side,
            'winner' => $role,
        ));
        if ($this->getGameStateValue($token) > 1) {
            throw new BgaVisibleSystemException("$role already has 2 Battle Tokens"); // NOI18N
        } else {
            $this->incGameStateValue($token, 1);
        }
    }

    /**
     * Get any players who hold a Special Tile they can use at the current battle, in the current combat round.
     * @param {string} HOPLITE or TRIREME
     * @return {array} player_ids (may be empty)
     */
    function specialBattleTilePlayers($type) {
        $specialtileplayers = [];
        $specialplayers = $this->SpecialTiles->playersWithSpecial($type."_battle");
        if (!empty($specialplayers)) {
            // have to check requirements for the combat special tiles
            foreach ($specialplayers as $pid) {
                $special = $this->SpecialTiles->getSpecialTile($pid);
                if ($this->Battles->mayUseBattleSpecial($special)) {
                    $specialtileplayers[] = $pid;
                }
            }
        }
        return $specialtileplayers;
    }

    /**
     * One side has won a battle and gets to claim the tile.
     * @param tile
     */
    function battleVictory($tile) {
        $winner = null;
        $location = $tile['location'];
        $location_name = $this->Locations->getName($location);
        $city = $tile['city'];
        $city_name = $this->Cities->getNameTr($city);
        $attacker = $this->Battles->getAttacker($location);
        $defender = $this->Battles->getDefender($location);
        if ($this->getGameStateValue(ATTACKER_TOKENS) == 2) {
            $winner = $attacker;
            self::notifyAllPlayers("attackerWins", clienttranslate('Attacker defeats ${city_name} at ${location_name}'), array(
                'i18n' => ['city_name'],
                'city' => $city,
                'city_name' => $city_name,
                'location' => $location,
                'location_name' => $location_name,
                'preserve' => ['city', 'location'],
            ));
            $this->defeatCity($city);
        } elseif ($this->getGameStateValue(DEFENDER_TOKENS) == 2) {
            $winner = $defender;
            self::notifyAllPlayers("defenderWins", clienttranslate('Defender (${city_name}) defeats attackers at ${location_name}'), array(
                'i18n' => ['city_name'],
                'city' => $city,
                'city_name' => $city_name,
                'location' => $location,
                'location_name' => $location_name,
                'preserve' => ['city', 'location'],
            ));
        } else {
            throw new BgaVisibleSystemException("No winner found at end of battle for tile $location"); // NOI18N
        }
        $this->claimTile($winner, $tile);
    }

    /**
     * Move a counter to the Deadpool.
     * Send notification.
     * @param {Object} counter to lose; may be null
     */
    function moveToDeadpool($counter) {
        if ($counter != null) {
            $id = $counter['id'];
            $unit_desc = $this->unitDescription($counter['city'], $counter['strength'], $counter['type'], $counter['location']);
            self::DbQuery("UPDATE MILITARY SET location=\"".DEADPOOL."\", battlepos=0 WHERE id=$id");
            self::notifyAllPlayers('toDeadpool', clienttranslate('Losing side takes one casualty: ${unit} is sent to Dead Pool'), array(
                'i18n' => ['unit'],
                'id' => $id,
                'unit' => $unit_desc,
                'city' => $counter['city'],
                'strength' => $counter['strength'],
                'type' => $counter['type'],
                'location' => $counter['location'],
                'preserve' => ['location'],
            ));
        }
    }

    /**
     * Get all eligibile casualties for the current battle.
     * Gets the lowest counters at the current active battles.
     * May be empty (for example, when losers were militia only)
     * @return {array} list of counters, may be empty
     */
    function getPossibleCasualties() {
        $battle = $this->getGameStateValue("active_battle");
        $location = $this->Locations->getBattleTile($battle);
        $loser = $this->getGameStateValue("battle_loser");
        $round = $this->getGameStateValue("battle_round");
        $type = $this->Locations->getCombat($location, $round);
        $casualties = $this->Battles->getCasualties($loser, $location, $type);
        return $casualties;
    }

    /**
     * Reset for entire next battle.
     */
    function battleReset() {
        // reinitialize battle tokens before every battle
        $this->setGameStateValue(ATTACKER_TOKENS, 0);
        $this->setGameStateValue(DEFENDER_TOKENS, 0);
        $this->setGameStateValue("commit_phase", 0);
        $this->setGameStateValue("active_battle", 0);
        $this->setGameStateValue("battle_round", 0);
        $this->setGameStateValue("battle_loser", -1);
        $this->setGameStateValue(BRASIDAS, 0);
        $this->setGameStateValue(PHORMIO, 0);
        self::notifyAllPlayers("resetBattleTokens", '', []);
    }

    /**
     * Set up for second round of a battle.
     * @param {int} ATTACKER or DEFENDER
     */
    function secondRoundReset($firstroundloser) {
        $winner = ($firstroundloser == ATTACKER) ? DEFENDER : ATTACKER;
        $this->setGameStateValue(ATTACKER_TOKENS, 0);
        $this->setGameStateValue(DEFENDER_TOKENS, 0);
        self::notifyAllPlayers("resetBattleTokens", '', []);
        $this->takeToken($winner, true);
    }

    /**
     * When a battle starts, flip all counters face up and place Victory tokens.
     * @param {object} battle tile
     */
    function revealCounters($tile) {
        $counters = $this->Battles->getLocationCounters($tile['location']);
        self::notifyAllPlayers("revealCounters", '', array(
            'slot' => $tile['slot'],
            'military' => $counters
        ));
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Game state arguments
////////////

    /*
        Here, you can create methods defined as "game state arguments" (see "args" property in states.inc.php).
        These methods function is to return some additional information that is specific to the current
        game state.
    */

    /**
     * Can the current active player play a special card during this Influence phase.
     */
    function argsSpecial() {
        $players = self::loadPlayersBasicInfos();
        $private = array();
        $phase = $this->checkPhase();
        foreach (array_keys($players) as $player_id) {
            $private[$player_id] = array('special' => $this->SpecialTiles->canPlaySpecial($player_id, $phase));
        }
        return array(
            '_private' => $private
        );
    }

    /**
     * Same as args special but for battle tiles.
     */
    function argsSpecialBattle() {
        $tile = $this->Battles->nextBattle();
        $location = $tile['location'];
        $round = $this->getGameStateValue('battle_round');
        $combat = $this->Locations->getCombat($location, $round);
        $players = $this->specialBattleTilePlayers($combat);
        $private = array();
        foreach ($players as $player_id) {
            $private[$player_id] = array('special' => true, 'location' => $location);
        }
        return array(
            '_private' => $private
        );
    }

    /**
     * Present player with choice of cities to take casualties from
     */
    function argsLoss() {
        $battle = $this->getGameStateValue("active_battle");
        $location = $this->Locations->getBattleTile($battle);
        $round = $this->getGameStateValue("battle_round");
        $type = $this->Locations->getCombat($location, $round);

        // may be empty
        $casualties = $this->getPossibleCasualties();
        $strength = 0;
        if (!empty($casualties)) {
            $strength = $casualties[0]['strength'];
        }
        $cities = $this->Battles->getCounterCities($casualties);
        return array(
            "type" => $type,
            "strength" => $strength,
            "cities" => $cities,
            "location" => $location,
        );
    }

    /**
     * Get the phase to check against for use of a Special tile.
     * @return "influence, commit, or
     */
    function checkPhase() {
        $state = $this->getStateName();
        if ($state == "takeInfluence") {
            return "influence_phase";
        } elseif ($state == "commitForces") {
            return "commit_phase";
        } elseif ($state == "specialTile") {
            // this may be 0, 1, or 2 (2 = candidate phase, no special tiles)
            if ($this->getGameStateValue("influence_phase") == 1) {
                return "influence_phase";
            } elseif ($this->getGameStateValue("commit_phase") == 1) {
                return "commit_phase";
            }
        }
        return null;
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Game state actions
////////////

    /**
     * Handles next player action through Influence phase.
     */
    function stNextPlayer() {
        $state = "";
        if ($this->getGameStateValue("influence_phase") > 0) {
            if ($this->allInfluenceTilesTaken()) {
                // no longer taking influence, enter candidates phase
                $this->setGameStateValue("influence_phase", 2);

                // we're nominating candidates
                if ($this->Cities->canAnyoneNominate()) {
                    $player_id = self::activeNextPlayer();
                    if ($this->Cities->canNominateAny($player_id)) {
                        self::giveExtraTime( $player_id );
                        $state = "proposeCandidate";
                    } else {
                        $player_id = self::activeNextPlayer();
                        $state = "nextPlayer";
                    }
                } else {
                    $state = "elections";
                }
            } else {
                $this->drawInfluenceTile();
                $player_id = self::activeNextPlayer();
                self::giveExtraTime( $player_id );
                $state = "takeInfluence";
            }
        } elseif ($this->getGameStateValue("commit_phase") == 1) {
            $state = "nextCommit";
        }
        $this->gamestate->nextState($state);
    }

    /**
     * Choose next player to play an Influence tile and commit forces.
     */
    function stNextCommit() {
        $state = "commit";
        // is this the first committer? Start with whoever Spartan player chose
        $player_id = $this->getGameStateValue("spartan_choice");
        if ($player_id != 0) {
            $this->gamestate->changeActivePlayer($player_id);
            $this->setGameStateValue("spartan_choice", 0);
            $this->setGameStateValue("commit_phase", 1);
        } else {
            $player_id = self::activeNextPlayer();
            self::giveExtraTime( $player_id );
        }
        // use which of this player's tiles, 2 or 1 shard?
        $s = 2;
        // do I have a 2-shard tile?
        $shards = $this->twoShardTiles($player_id);
        if (empty($shards)) {
            // does anyone else have a two-shard tile left?
            if ($this->isTwoShardTileLeft()) {
                // go to next player with 2-shards
                $state = "nextPlayer";
            } else {
                // no 2-shards left.
                // do I have a 1-shard?
                $shards = $this->oneShardTiles($player_id);
                if (empty($shards)) {
                    // does anyone have any shards left?
                    if ($this->isOneShardTileLeft()) {
                        // go to next player with 1-shard
                        $state = "nextPlayer";
                    } else {
                        // everyone is out of tiles
                        $state = "resolve";
                    }
                } else {
                    $s = 1;
                }
            }
        }
        // stayed commit if the current player has a playable tile
        if ($state == "commit") {
            $city = reset($shards);
            $city_name = ($city == "any") ? clienttranslate('Any') : $this->Cities->getNameTr($city);
            $id = key($shards);
            $this->influence_tiles->moveCard($id, DISCARD);
            self::notifyAllPlayers('useTile', clienttranslate('${player_name} uses a ${shardct}-shard tile (${city_name})'), array(
                'i18n' => ['city_name'],
                'player_id' => $player_id,
                'player_name' => self::getActivePlayerName(),
                'id' => $id,
                'shardct' => $s,
                'city' => $city,
                'city_name' => $city_name,
                'preserve' => ['player_id', 'city']
            ));
            // does this player actually still have forces to send?
            $counter_loc = $player_id;
            // are we a Persian leader?
            if ($this->Cities->isLeader($player_id, PERSIA)) {
                $counter_loc = CONTROLLED_PERSIANS;
            }
            $counters = self::getObjectListFromDB("SELECT id FROM MILITARY WHERE location=\"$counter_loc\"");

            if (empty($counters)) {
                $this->noCommitUnits($player_id);
                $state = "nextPlayer";
            }
        }
        $this->gamestate->nextState($state);
    }

    /**
     * Check whether player can collect units
     */
    function stDeadPool() {
        $picked = $this->getGameStateValue("deadpool_picked");
        if ($picked == 0) {
            $first_player = $this->getGameStateValue("spartan_choice");
            $this->gamestate->changeActivePlayer($first_player);
        }

        $state = "nextPlayer";
        $players = self::loadPlayersBasicInfos();
        $nbr = count($players);
        if ($picked == $nbr) {
            $this->setGameStateValue("deadpool_picked", 0);
            $state = "startCommit";
        } else {
            $player_id = self::getActivePlayerId();
            if ($this->hasDeadPool($player_id)) {
                $state = "takeDead";
            } else {
                $player_id = self::activeNextPlayer();
                self::giveExtraTime( $player_id );
                $state = "nextPlayer";
            }
            $this->incGameStateValue("deadpool_picked", 1);
        }
        $this->gamestate->nextState($state);
    }

    /**
     * Place Influence cubes from card where the city is already determined.
     */
    function stPlaceInfluence() {
        $player_id = self::getActivePlayerId();
        // card on top should be most recently added card
        $card = $this->influence_tiles->getCardOnTop($player_id);
        $city = $card['type'];
        $type = $card['type_arg'];
        $cubes = ($type == 'influence') ? 2 : 1;
        $this->addInfluenceToCity($city, $player_id, $cubes);

        $state = "nextPlayer";
        if ($type == 'assassin') {
            $state = "assassinate";
        } else if ($type == 'candidate') {
            $state = "candidate";
        } else if ($this->SpecialTiles->canPlaySpecial($player_id, "influence")) {
            $state = "useSpecial";
        }
        $this->gamestate->nextState( $state );
    }

    /**
     * Do all the elections.
     */
    function stElections() {
        $players = self::loadPlayersBasicInfos();
        // end influence phase
        $this->setGameStateValue("influence_phase", 0);

        foreach ($this->Cities->cities() as $cn) {
            $city_name = $this->Cities->getNameTr($cn);

            $a = $this->Cities->getCandidate($cn, "a");
            $b = $this->Cities->getCandidate($cn, "b");
            $winner = 0;
            if (empty($a)) {
                if (empty($b)) {
                    // no candidates!
                    self::notifyAllPlayers("noElection", clienttranslate('${city_name} has no candidates; no Leader assigned'), array(
                        'i18n' => ['city_name'],
                        'city_name' => $city_name,
                    ));
                } else {
                    // B is unopposed
                    $winner = $b;
                    self::notifyAllPlayers("election", clienttranslate('${player_name} becomes Leader of ${city_name} unopposed'), array(
                        'i18n' => ['city_name'],
                        'player_id' => $winner,
                        'player_name' => $players[$winner]['player_name'],
                        'city' => $cn,
                        'city_name' => $city_name,
                        'cubes' => 0,
                        'preserve' => ['player_id', 'city']
                        ));
                }
            } elseif (empty($b)) {
                // A is unopposed
                $winner = $a;
                self::notifyAllPlayers("election", clienttranslate('${player_name} becomes Leader of ${city_name} unopposed'), array(
                    'i18n' => ['city_name'],
                    'player_id' => $winner,
                    'player_name' => $players[$winner]['player_name'],
                    'city' => $cn,
                    'city_name' => $city_name,
                    'cubes' => 0,
                    'preserve' => ['player_id', 'city']
                ));
            } else {
                // contested election
                $a_inf = $this->Cities->influence($a, $cn);
                $b_inf = $this->Cities->influence($b, $cn);
                // default
                $winner = $a;
                $loser_inf = $b_inf;
                if ($a_inf < $b_inf) {
                    $winner = $b;
                    $loser_inf = $a_inf;
                }
                $this->Cities->changeInfluence($cn, $winner, -$loser_inf);
                self::notifyAllPlayers("election", clienttranslate('${player_name} becomes Leader of ${city_name}'), array(
                    'i18n' => ['city_name'],
                    'player_id' => $winner,
                    'player_name' => $players[$winner]['player_name'],
                    'city' => $cn,
                    'city_name' => $city_name,
                    'cubes' => $loser_inf,
                    'preserve' => ['player_id', 'city']
                ));
            }
            $this->Cities->clearCandidates($cn);
            $this->Cities->setLeader($winner, $cn);

            if (!empty($winner)) {
                $this->moveMilitaryUnits($winner, $cn);
            }
        }
        // anyone who is not a leader of any city is a Persian leader
        $this->Cities->assignPersianLeaders();
        $persianleaders = $this->Cities->getPersianLeaders();
        if (!empty($persianleaders)) {
            foreach($persianleaders as $persian) {
                $msg = (count($persianleaders) > 1) ? clienttranslate('${player_name} won no elections and shares leadership of the Persians') : clienttranslate('${player_name} won no elections and takes control of the Persians');
                self::notifyAllPlayers("persianLeader", $msg, array(
                    'player_id' => $persian,
                    'player_name' => $players[$persian]['player_name'],
                    'preserve' => ['player_id']
                ));
            }
            $this->movePersianUnits($persianleaders);
        }

        // sparta leader chooses
        $sparta = $this->Cities->getLeader("sparta");
        $this->gamestate->changeActivePlayer($sparta);
        $this->gamestate->nextState();
    }

    /**
     * Do the battles.
     * Either go to next battle, or if none left, to end turn.
     */
    function stNextLocationTile() {
        $state = "";
        // commit phase is over
        $this->battleReset();

        // is there another tile?
        $tile = $this->Battles->nextBattle();
        if ($tile == null) {
            $state = "endTurn";
        } else {
            $location = $tile['location'];
            // are there combatants?
            $attacker = $this->Battles->getAttacker($location);
            $defender = $this->Battles->getDefender($location);
            if ($attacker && $defender) {
                // battle!
                $state = "resolve";
            } elseif ($attacker) {
                // if attacker, still needs to beat militia
                if ($this->Locations->hasDefendingMilitia($location)) {
                    $state = "resolve";
                } else {
                    // only attacker sent forces, no defending militia
                    $this->uncontestedBattle($tile);
                    $state = "nextBattle";
                }
            }elseif ($defender) {
                // only defender sent forces
                $this->uncontestedBattle($tile);
                $state = "nextBattle";
            } else {
                // nobody sent forces
                $this->noBattle($tile);
                $state = "nextBattle";
            }
        }

        $this->gamestate->nextState($state);
    }

    /**
     * Resolve all the battles for the next location in the queue.
     * Assumes we have already checked that there is another location tile to be fought for,
     * and we know there are combatants on both sides.
     */
    function stResolveTile() {
        $tile = $this->Battles->nextBattle();
        if ($tile == null) {
            // shouldn't happen!
            throw new BgaVisibleSystemException("No battle tile to resolve"); // NOI18N
        }
        // next state
        $state = "";

        $location = $tile['location'];
        $slot = $tile['slot'];

        $this->setGameStateValue("active_battle", $slot);
        // initialized to 0 in stNextLocationTile, so this makes it either 1 or 2
        $this->incGameStateValue("battle_round", 1);
        $round = $this->getGameStateValue("battle_round");

        $combat = $this->Locations->getCombat($location, $round);
        if ($combat == null) {
            // there should be a winner and we have already done losses
            $this->battleVictory($tile);
            $state = "endBattle";
        } else {
            if ($round == 1) {
                $this->revealCounters($tile);
            } else {
                // one side starts with a battle token
                $loser = $this->getGameStateValue("battle_loser");
                $this->secondRoundReset($loser);
            }
            $state = "nextCombat";
        }
        $this->gamestate->nextState($state);
    }

    /**
     * There are forces on both sides (at least in one battle).
     * We know there is a battle to be fought.
     */
    function stCombat() {
        $tile = $this->Battles->nextBattle();
        // should not happen!
        if ($tile == null) {
            throw new BgaVisibleSystemException("no battle!"); // NOI18N
        }
        $state = "";

        $location = $tile['location'];
        $attacker = $tile['attacker'];
        $defender = $tile['defender'];
        // do we have combatants on both sides for this round?
        $round = $this->getGameStateValue('battle_round');
        $combat = $this->Locations->getCombat($location, $round);
        $attackers = $this->Battles->getAttackingCounters($location, $combat);
        $defenders = $this->Battles->getDefendingCounters($location, $combat);
        $players = self::loadPlayersBasicInfos();
        // is this round a one-sided battle?
        $unopposed = null;
        if (empty($attackers) || empty($defenders)) {
            if (empty($attackers)) {
                // attacker brought nothing to this round
                $unopposed = DEFENDER;
            } elseif (empty($defenders)) {
                // defender brought nothing to this round
                // attackers might still need to defeat militia
                if (!$this->Locations->hasDefendingMilitia($location, $combat)) {
                    $unopposed = ATTACKER;
                }
            }
        }

        if ($unopposed !== null) {
            $unopposed_id = ($unopposed == ATTACKER) ? $attacker : $defender;
            $unopposed_role = ($unopposed == ATTACKER) ? clienttranslate('Attacker') : clienttranslate('Defender');
            self::notifyAllPlayers("freeToken", clienttranslate('${player_name} (${side}) wins ${combat_type} battle unopposed'), array(
                'i18n' => ['combat_type', 'side'],
                'player_id' => $unopposed_id,
                'player_name' => $players[$unopposed_id]['player_name'],
                'type' => $combat,
                'side' => $unopposed_role,
                'combat_type' => $this->getUnitName($combat),
                'preserve' => ['player_id']
            ));

            if ($round == 1) {
                $loser = ($unopposed == ATTACKER) ? DEFENDER : ATTACKER;
                $this->setGameStateValue("battle_loser", $loser);
                $state = "continueBattle";
            } else {
                // unopposed side wins tile
                $this->claimTile($unopposed_id, $tile);
                $state = "nextBattle";
            }
        } else {
            // there are combatants on both sides
            // before battle Special Tiles can come into play
            $specialplayers = $this->specialBattleTilePlayers($combat);
            if (empty($specialplayers)) {
                $state = "combat";
            } else {
                $this->gamestate->setPlayersMultiactive( $specialplayers, "combat", false );
                $state = "useSpecial";
            }
        }
        $this->gamestate->nextState($state);
    }

    /**
     * All checks have been done, there are forces on each side, and all player actions completed.
     * At this point, roll dice until one side wins. This is for ONE combat.
     */
    function stRollCombat() {
        $tile = $this->Battles->nextBattle();
        $location = $tile['location'];
        $round = $this->getGameStateValue("battle_round");
        $type = $this->Locations->getCombat($location, $round);
        $bonus = (($this->getGameStateValue(BRASIDAS) == 1) || ($this->getGameStateValue(PHORMIO) == 1));
        // get all attacking units
        $attstrength = $this->Battles->getAttackStrength($location, $type, $bonus);
        // get all defending units
        $defstrength = $this->Battles->getDefenseStrength($location, $type, $bonus);

        $militia = $this->Locations->getMilitia($location);
        if (!empty($militia)) {
            switch ($militia) {
                case "dht":
                    $defstrength++;
                    break;
                case "dh":
                    if ($type == HOPLITE) {
                        $defstrength++;
                    }
                    break;
                case "aht":
                    $attstrength++;
                    break;
                case "ah":
                    if ($type == HOPLITE) {
                        $attstrength++;
                    }
                    break;
                default:
                // should not happen!
                    throw new BgaVisibleSystemException("Invalid intrinsic defender code"); // NOI18N
            }
        }
        if ($attstrength == 0 || $defstrength == 0) {
            // shouldn't happen!
            throw new BgaVisibleSystemException("no combat strength found at $location!"); // NOI18N
        }
        $winner = $this->rollBattle($location, $type, $attstrength, $defstrength);

        $winningside = null;
        if ($winner == ATTACKER) {
            $winningside = clienttranslate('Attacker');
            $loser = DEFENDER;
            $loser_id = $this->Battles->getDefender($location);
        } else {
            $winningside = clienttranslate('Defender');
            $loser = ATTACKER;
            $loser_id = $this->Battles->getAttacker($location);
        }
        self::notifyAllPlayers("combatWinner", clienttranslate('${winner} wins ${type} battle'), array(
            'i18n' => ['winner', 'type'],
            'winner' => $winningside,
            'type' => $this->getUnitName($type),
        ));

        $this->setGameStateValue("battle_loser", $loser);
        $casualties = $this->Battles->getCasualties($loser, $location, $type);
        $cities = $this->Battles->getCounterCities($casualties);

        $state = "endBattle";

        // more than one city, player must choose
        if (count($cities) > 1) {
            $this->gamestate->changeActivePlayer($loser_id);
            $state = "takeLoss";
        } else {
            // just pop the first one
            $casualty = null;
            if (!empty($casualties)) {
                $casualty = array_pop($casualties);
            }
            $this->moveToDeadpool($casualty);
        }

        // claiming the tile is done in stResolveTile
        $this->gamestate->nextState($state);
    }

    /**
     * End of turn refresh.
     */
    function stEndTurn() {
        self::incStat(1, 'turns_number');
        $state = $this->isEndGame() ? "endGame" : "nextTurn";

        $players = self::loadPlayersBasicInfos();
        // add statues
        foreach ($this->Cities->cities() as $cn) {
            $leader = $this->Cities->getLeader($cn);
            if (!empty($leader)) {
                $statues = $this->Cities->addStatue($leader, $cn);
                self::notifyAllPlayers("addStatue", clienttranslate('Statue to ${player_name} erected in ${city_name}'), array(
                    'i18n' => ['city_name'],
                    'city' => $cn,
                    'city_name' => $this->Cities->getNameTr($cn),
                    'player_id' => $leader,
                    'statues' => $statues,
                    'player_name' => $players[$leader]['player_name'],
                    'preserve' => ['player_id', 'city'],
                ));
            }
        }
        $this->Cities->clearLeaders();
        if ($state == "nextTurn") {
            // reshuffle Influence deck and deal new cards
            $this->dealNewInfluence();
            $this->dealNewLocations();
        }
        $this->gamestate->nextState($state);
    }

    /**
     * End of game scoring
     */
    function stScoring() {
        $players = self::loadPlayersBasicInfos();

        foreach($players as $player_id) {
            $playerstatues = $this->Cities->getStatues($player_id);
            foreach($this->Cities->cities() as $city) {
                // 1 point per cube
                $cubes = $this->Cities->cubesInCity($player_id, $city);
                if ($cubes > 0) {
                    self::notifyAllPlayers("scoreCubes", clienttranslate('${player_name} scores ${vp} cubes in ${city_name}'), array(
                        'i18n' => ['city_name'],
                        'player_id' => $player_id,
                        'player_name' => $players[$player_id]['player_name'],
                        'city' => $city,
                        'city_name' => $this->Cities->getNameTr($city),
                        'vp' => $cubes,
                        'preserve' => ['player_id', 'city']
                    ));
                }
                // statues
                $statues = $playerstatues[$city];
                if ($statues > 0) {
                    $vp = $this->Cities->victoryPoints($city);
                    $total = $statues*$cityvp;
                    self::notifyAllPlayers("scoreStatues", clienttranslate('${player_name} scores ${total} points for ${statues} in ${city_name}'), array(
                        'i18n' => ['city_name'],
                        'player_id' => $player_id,
                        'player_name' => $players[$player_id]['player_name'],
                        'city' => $city,
                        'city_name' => $this->Cities->getNameTr($city),
                        'total' => $total,
                        'vp' => $vp, // per statue
                        'statues' => $statues,
                        'preserve' => ['player_id', 'city']
                    ));
                }

            }
        }

        // Score statues

        $this->gamestate->nextState("");
    }

    function stDebug() {
        $player = self::getActivePlayerName();
        throw new BgaVisibleSystemException("$player in stDebug");
    }

    function logDebug($msg) {
        self::notifyAllPlayers("debug", $msg, []);
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Zombie
////////////

    /*
        zombieTurn:
        
        This method is called each time it is the turn of a player who has quit the game (= "zombie" player).
        You can do whatever you want in order to make sure the turn of this player ends appropriately
        (ex: pass).
        
        Important: your zombie code will be called when the player leaves the game. This action is triggered
        from the main site and propagated to the gameserver from a server, not from a browser.
        As a consequence, there is no current player associated to this action. In your zombieTurn function,
        you must _never_ use getCurrentPlayerId() or getCurrentPlayerName(), otherwise it will fail with a "Not logged" error message. 
    */

    function zombieTurn( $state, $active_player )
    {
    	$statename = $state['name'];
    	
        if ($state['type'] === "activeplayer") {
            switch ($statename) {
                case 'takeInfluence':
                    $tile = $this->chooseRandomTile($active_player);
                    $this->takeInfluence($tile['id']);
                    break;
                case 'choosePlaceInfluence':
                    $cities = $this->Cities->cities();
                    shuffle($cities);
                    $city = $cities[0];
                    $this->placeAnyCube($city);
                    break;
                case 'proposeCandidates':
                    // should have already checked that it's possible
                    $this->chooseRandomCandidate($active_player);
                    break;
                case 'assassinate':
                    $this->removeRandomCube($active_player);
                    break;
                case 'spartanChoice':
                    $firstplayer = $this->chooseRandomPlayer();
                    $this->chooseNextPlayer($firstplayer);
                    break;
                case 'commitForces':
                    $this->sendRandomUnits($active_player);
                    break;
                case 'specialTile':
                    $this->useSpecialTile($active_player, false);
                    break;
                case 'specialBattleTile':
                    $this->useSpecialBattleTile($active_player, false);
                    break;
                case "takeLoss":
                        $casualty = null;
                        $casualties = $this->getPossibleCasualties();
                        if (!empty($casualties)) {
                            shuffle($casualties);
                            $casualty = array_pop($casualties);
                        }
                        $this->moveToDeadpool($casualty);
                        $this->gamestate->nextState( "endBattle" );
                        break;
                default:
                    $this->gamestate->nextState( "zombiePass" );
                	break;
            }

            return;
        }

        if ($state['type'] === "multipleactiveplayer") {
            $this->gamestate->setPlayerNonMultiactive( $active_player, "" );
            return;
        }

        throw new feException( "Zombie mode not supported at this game state: ".$statename );
    }

    /**
     * Choose a random player_id.
     */
    function chooseRandomPlayer() {
        $players = self::loadPlayersBasicInfos();
        $playerids = array_keys($players);
        shuffle($playerids);
        return $playerids[0];
    }

    /**
     * Take a random city tile on the board and give it to zombie player.
     */
    function chooseRandomTile($player_id) {
        $tiles = $this->influence_tiles->getCardsInLocation(BOARD);
        foreach (array_values($tiles) as $tile) {
            $city = $tile['type'];
            if (!$this->hasCityInfluenceTile($player_id, $city)) {
                return $tile;
            }
        }
        // zombie player cannot pick a tile in a city he doesn't already have,
        // so take first
        shuffle($tiles);
        return array_pop($tiles);
    }

    /**
     * Zombie player propose in random city
     */
    function chooseRandomCandidate($player_id) {
        $players = self::loadPlayersBasicInfos();
        $cities = $this->Cities->cities();
        shuffle($cities);
        foreach($cities as $cn) {
            if ($this->Cities->canNominate($player_id, $cn)) {
                $a = $this->Cities->getCandidate($cn, "a");
                if (empty($a)) {
                    foreach (array_keys($players) as $candidate_id) {
                        if ($this->Cities->influence($candidate_id, $cn) > 0) {
                            $this->proposeCandidate($cn, $candidate_id);
                            return;
                        }
                    }
                } else {
                    $b = $this->Cities->getCandidate($cn, "b");
                    if (!empty($b)) {
                        throw new BgaVisibleSystemException("Unexpected zombie state: cannot nominate candidate in $cn"); //NO18N
                    }
                    foreach (array_keys($players) as $candidate_id) {
                        if ($candidate_id != $a && $this->Cities->influence($candidate_id, $cn) > 0) {
                            $this->proposeCandidate($cn, $candidate_id);
                            return;
                        }
                    }
                }
            }
        }
    }

    /**
     * Zombie player pick a random cube to kill, not self.
     */
    function removeRandomCube($player_id) {
        $cities = $this->Cities->cities();
        shuffle($cities);
        foreach ($cities as $cn) {
            $players = self::loadPlayersBasicInfos();
            $toremove = [];
            $candidates = $this->Cities->getCandidates($cn);
            foreach($candidates as $c => $candidate) {
                if ($candidate != $player_id) {
                    $toremove[] = $c;
                }
            }
            foreach(array_keys($players) as $target_id) {
                if ($player_id != $target_id && $this->Cities->influence($target_id, $cn) > 0) {
                    $toremove[] = $target_id;
                }
            }
            shuffle($toremove);
            $killcube = array_pop($toremove);
            if ($killcube == "a" || $killcube == "b") {
                $target = $this->Cities->getCandidate($cn, $killcube);
                $this->chooseRemoveCube($target, $cn, $killcube);
                break;
            } else {
                $this->chooseRemoveCube($killcube, $cn, 1);
                break;
            }
        }
    }

    /**
     * Send two random military units. Don't use cubes.
     * Prioritize defense, then attack.
     */
    function sendRandomUnits($player_id) {
        $assignment = "";
        for ($i = 0; $i < 2; $i++) {
            $unitstr = "";
            $military = $this->Battles->getPlayerCounters($player_id);
            if (!empty($military)) {
                shuffle($military);
                while (!empty($military) && $unitstr === "") {
                    $unit = array_pop($military);
                    $city = $unit['city'];
                    // does this unit have any cities to defend?
                    $mycitybattles = self::getObjectListFromDB("SELECT card_type_arg location FROM LOCATION WHERE card_type=\"$city\" AND card_location=\"".BOARD."\"", true);
    
                    // is there a city we can attack?
                    if (empty($mycitybattles)) {
                        $allbattles = self::getObjectListFromDB("SELECT card_type city, card_type_arg location, attacker FROM LOCATION WHERE card_location=\"".BOARD."\"");
                        shuffle($allbattles);
                        $location = array_pop($allbattles);
                        $defcity = $location['city'];
                        $location = $location['location'];
                       
                        if ($this->Cities->canAttack($player_id, $city, $defcity, $location)) {
                            // we can attack this city
                            // make sure not sending trireme to a land battle
                            if (!($unit['type'] == TRIREME && $this->Locations->isLandBattle($location))) {
                                $unitstr = $unit['id']."_attack_".$location;
                            }
                        }
                    } else {
                        // go defend that place
                        shuffle($mycitybattles);
                        $defbattle = array_pop($mycitybattles);
                        // make sure not sending trireme to a land battle
                        if (!($unit['type'] == TRIREME && $this->Locations->isLandBattle($defbattle))) {
                            $unitstr = $unit['id']."_defend_".$defbattle;
                        }
                    }
                }
            }
            $assignment .= $unitstr." ";
        }

        $this->assignUnits($assignment, "");
    }
    
///////////////////////////////////////////////////////////////////////////////////:
////////// DB upgrade
//////////

    /*
        upgradeTableDb:
        
        You don't have to care about this until your game has been published on BGA.
        Once your game is on BGA, this method is called everytime the system detects a game running with your old
        Database scheme.
        In this case, if you change your Database scheme, you just have to apply the needed changes in order to
        update the game database and allow the game to continue to run with your new version.
    
    */
    
    function upgradeTableDb( $from_version )
    {
        // $from_version is the current version of this game database, in numerical form.
        // For example, if the game was running with a release of your game named "140430-1345",
        // $from_version is equal to 1404301345
        
        // Example:
//        if( $from_version <= 1404301345 )
//        {
//            // ! important ! Use DBPREFIX_<table_name> for all tables
//
//            $sql = "ALTER TABLE DBPREFIX_xxxxxxx ....";
//            self::applyDbUpgradeToAllDB( $sql );
//        }
//        if( $from_version <= 1405061421 )
//        {
//            // ! important ! Use DBPREFIX_<table_name> for all tables
//
//            $sql = "CREATE TABLE DBPREFIX_xxxxxxx ....";
//            self::applyDbUpgradeToAllDB( $sql );
//        }
//        // Please add your future database scheme changes here
//
//


    }    
}
