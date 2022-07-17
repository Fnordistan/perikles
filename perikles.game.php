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
define("PERIKLES", 1);
define("PERSIANFLEET", 2);
define("SLAVEREVOLT", 3);
define("BRASIDAS", 4);
define("THESSALIANALLIES", 5);
define("ALKIBIADES", 6);
define("PHORMIO", 7);
define("PLAGUE", 8);

define("ATTACKER_TOKENS", "attacker_tokens");
define("DEFENDER_TOKENS", "defender_tokens");
define("CONTROLLED_PERSIANS", "_persia_"); // used to flag Persian units that will go to a player board

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
            "argos_defeats" => 31,
            "athens_defeats" => 32,
            "corinth_defeats" => 33,
            "megara_defeats" => 34,
            "sparta_defeats" => 35,
            "thebes_defeats" => 36,
            "active_battle" => 46,
            "battle_round" => 47, // 0,1
            "influence_phase" => 48,
            "commit_phase" => 49,

            "last_influence_slot" => 37, // keep track of where to put next Influence tile
            "deadpool_picked" => 38, // how many players have been checked for deadpool?
            "spartan_choice" => 39, // who Sparta picked to go first in military phase
            ATTACKER_TOKENS => 50, // battle tokens won by attacker so far in current battle
            DEFENDER_TOKENS => 51, // battle tokens won by defender so far in current battle
        ) );

        $this->Cities = new PeriklesCities($this);
        $this->Locations = new PeriklesLocations($this);
        $this->Battles = new PeriklesBattles($this);

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
        // when we are in the Influence Phase and influence special tiles can be used. Start with Influence, ends with candidate nominations.
        self::setGameStateInitialValue("influence_phase", 1);
        // when we are in the committing phase. Start with first commit, end with battle phase.
        self::setGameStateInitialValue("commit_phase", 0);

        $this->Cities->setupNewGame();

        $this->setupInfluenceTiles();

        $this->Locations->setupNewGame();

        $this->assignSpecialTiles();

        // Activate first player (which is in general a good idea :) )
        $this->activeNextPlayer();

        /************ End of the game initialization *****/
    }

    /**
     * Assign Special tile to each player at start of game.
     */
    protected function assignSpecialTiles() {
        $spec = range(1,8);
        // for testing
        $spec = [2,4,5,7,1,6,8,3];
        // shuffle($spec);
        $players = self::loadPlayersBasicInfos();
        foreach ($players as $player_id => $player) {
            $tile = array_pop($spec);
            self::DbQuery("UPDATE player SET special_tile = $tile WHERE player_id=$player_id");
        }
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

        return $result;
    }

    /**
     * Return associative array of player_id => tile number.
     * For unused opponent tiles, value is 0
     * For used own tile, return negative value.
     */
    protected function getSpecialTiles($current_player_id) {
        $specialtiles = array();
        $playertiles = self::getCollectionFromDB("SELECT player_id, special_tile, special_tile_used FROM player");

        foreach ($playertiles as $player_id => $tiles) {
            $tile = 0;
            if ($player_id == $current_player_id) {
                // tile number if not used, negative tile number if used
                $tile = $tiles['special_tile'];
                if ($tiles['special_tile_used']) {
                    // mark my tile was used
                    $tile *= -1;
                }
            } elseif ($tiles['special_tile_used']) {
                // only reveal tile if it's been used
                $tile = $tiles['special_tile'];
            }
            $specialtiles[$player_id] = $tile;
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
            $name = self::_("Any");
            $shards = 1;
        } else {
            $name = $this->Cities->getNameTr($name);

            if ($tile['type'] == "assassin") {
                $desc = self::_("Assassin");
                $shards = 1;
            } else if ($tile['type'] == "candidate") {
                $desc = self::_("Candidate");
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
        $unit_type = ($type == HOPLITE) ? self::_("Hoplite") : self::_("Trireme");
        $unit_desc = sprintf(self::_("%s %s-%s at %s", ), $home_city, $unit_type, $strength, $location);
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
            throw new BgaUserException("You already have 30 cubes on the board");
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
        $slot = self::getGameStateValue("last_influence_slot");
        $this->influence_tiles->pickCardForLocation(DECK, BOARD, $slot);
        $newtile = self::getObjectFromDB("SELECT card_id id, card_type city, card_type_arg type, card_location location, card_location_arg slot FROM INFLUENCE WHERE card_location = \"".BOARD."\" AND card_location_arg =$slot");
        self::setGameStateValue("last_influence_slot", 0);

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
     * Move a tile to the unclaimed pile
     * @param {string} id
     */
    function unclaimedTile($id) {
        $this->moveTile($id, UNCLAIMED);
    }

    /**
     * A player claims a tile. Add it to player's board. Send notification to move tile.
     * @param {string} id
     * @param {string} player_id
     */
    function claimTile($id, $player_id) {
        $this->moveTile($id, $player_id);
    }

    /**
     * Move a tile either to a player board or unclaimed pile.
     * Send notification.
     */
    function moveTile($id, $destination) {
        $this->location_tiles->insertCardOnExtremePosition($id, $destination, true);
        self::DbQuery("UPDATE LOCATION SET attacker=NULL,defender=NULL,permissions=NULL WHERE card_id=$id");
        // TODO:notification
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
        $slot = $tile['slot'];
        $units = self::getObjectListFromDB("SELECT id, city, type, strength, location FROM MILITARY WHERE location=\"$location\"");
        foreach($units as $unit) {
            $id = $unit['id'];
            $city = $unit['city'];
            self::DbQuery("UPDATE MILITARY SET location=\"$city\", battlepos=0 WHERE id=$id");
        }
        self::notifyAllPlayers("returnMilitary", '', array(
            'slot' => $slot
        ));
    }

    /**
     * Assumes all checks have been done. Send a military unit to a battle location.
     */
    function sendToBattle($player_id, $mil, $battlepos) {

        $id = $mil['id'];
        $battle = $mil['battle'];
        $players = self::loadPlayersBasicInfos();
        $counter = self::getObjectFromDB("SELECT id, city, type, location, strength FROM MILITARY WHERE id=$id");

        self::DbQuery("UPDATE MILITARY SET location=\"$battle\", battlepos=$battlepos WHERE id=$id");

        $role = $this->getRoleName($battlepos);

        $slot = self::getUniqueValueFromDB("SELECT card_location_arg from LOCATION WHERE card_type_arg=\"$battle\"");

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
                'location' => $battle,
                'slot' => $slot,
                'location_name' => $this->Locations->getName($battle),
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
            $rolename = clienttranslate("Main attacker");
        } else if ($role == ATTACKER+ALLY) {
            $rolename = clienttranslate("Allied attacker");
        } else if ($role == DEFENDER+MAIN) {
            $rolename = clienttranslate("Main defender");
        } else if ($role == DEFENDER+ALLY) {
            $rolename = clienttranslate("Allied defender");
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
     * Return a list of players who are eligible to play a special tile now.
     * May be empty
     */
    function playersWithSpecial($phase) {
        $canplay = [];
        $playertiles = self::getCollectionFromDB("SELECT player_id, special_tile FROM player WHERE special_tile_used IS NOT TRUE", true);
        foreach ($playertiles as $player_id => $tileid) {
            $playable = true;
            if ($phase == $this->specialcards[$tileid]["phase"]) {
                // slaverevolt, only "commit" Special tile, can only be played on player's turn
                if ($phase == "commit") {
                    $playable = ($player_id == self::getActivePlayerId());
                }
                if ($playable) {
                    $canplay[] = $player_id;
                }
            }
        }
        return $canplay;
    }

    /**
     * Can a player play a Special Tile now?
     * @return true if player_id can play a Special now
     */
    function canPlaySpecial($player_id, $phase) {
        $players = $this->playersWithSpecial($phase);
        $canplay = in_array($player_id, $players);
        return $canplay;
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
            return true;
        }
        foreach(["sparta", "athens"] as $civ) {
            if ($this->Cities->getDefeats($civ) >= 4) {
                return true;
            }
        }
        return false;
    }

    /**
     * Move all the influence cards to deck, shuffle, and deal new ones.
     */
    function dealNewInfluence() {
        $this->influence_tiles->moveAllCardsInLocation(null, DECK);
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
     * Do a validity check and if it passes, return the Special tile belonging to this player.
     * Checks that player's Special tile has not been used, and it's the current game state.
     * 
     * @param player_id player_id player playing the tile
     * @param phase matched against commit, influence, or commit
     * @param tile expected tile number (optional)
     */
    function checkSpecialTile($player_id, $phase, $tile = 0) {
        $special = self::getObjectFromDB("SELECT special_tile tile, special_tile_used used FROM player WHERE player_id=$player_id", true);
        // sanity check
        if ($special == null) {
            throw new BgaVisibleSystemException("No special tile found"); // NOI18N
        } else if ($special['used']) {
            throw new BgaVisibleSystemException("You have already used your special tile"); // NOI18N
        }
        if (self::getGameStateValue($phase) == 0) {
            throw new BgaVisibleSystemException("This Special Tile cannot be used during the current phase"); // NOI18N
        }
        if ($tile != 0 && $special['tile'] != $tile) {
            throw new BgaVisibleSystemException(sprintf("You cannot play %s", $this->specialcards[$tile]['name'])); // NOI18N
        }

        return $special;
    }

    /**
     * @param {string} unit HOPLITE or TRIREME
     * @return translatation marked string
     */
    function getUnitName($unit) {
        $units = array(
            HOPLITE => clienttranslate("Hoplite"),
            TRIREME => clienttranslate("Trireme"),
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
        // self::checkAction('useSpecial');
        if ($use) {
            $this->playSpecialTile($player_id);
        }
        // after playing tile, or if passed
        if ($player_id == self::getActivePlayerId() && $this->getStateName() == "specialTile") {
            // We might be in the middle of battle (with Trireme or Hoplite Specials)
            $nextstate = "";
            if (self::getGameStateValue('active_battle') != 0) {
                $nextstate = "doBattle";
            } elseif (self::getGameStateValue('influence_phase') == 0) {
                // if it was Slave Revolt, next Commit
                $nextstate = "nextCommit";
            } else {
                // otherwise we're going to next player
                $nextstate = "nextPlayer";
            }
            $this->gamestate->nextState($nextstate);
        }
    }

    /**
     * Player either Played or Passed on special tile button.
     * @param player_id
     */
    function playSpecialTile($player_id) {
        $special = $this->checkSpecialTile($player_id, "influence_phase");
        // sanity check
        $t = $special['tile'];
        switch ($t) {
            case 1: // Perikles
                $this->playPerikles($player_id);
                break;
            case 2; // Persian Fleet
                throw new BgaVisibleSystemException("You haven't implemented Special Tile $t yet");
                break;
            case 3; // Slave Revolt
                throw new BgaVisibleSystemException("You haven't implemented Special Tile $t yet");
                break;
            case 4; // Brasidas
                throw new BgaVisibleSystemException("You haven't implemented Special Tile $t yet");
                break;
            case 5; // Thessalanian Allies
                throw new BgaVisibleSystemException("You haven't implemented Special Tile $t yet");
                break;
            case 6; // Alkibiades
                throw new BgaVisibleSystemException("Invalid Special card played: $t"); // NOI18N
                break;
            case 7; // Phormio
                throw new BgaVisibleSystemException("You haven't implemented Special Tile $t yet");
                break;
            case 8; // Plague
                throw new BgaVisibleSystemException("Invalid Special card played: $t"); // NOI18N
                break;
            default:
                throw new BgaVisibleSystemException("Unknown special tile: $t"); // NOI18N
        }
    }

    /**
     * Play Perikles Special tile.
     */
    function playPerikles($player_id) {
        $this->flipSpecialTile($player_id, PERIKLES);
        $this->addInfluenceToCity('athens', $player_id, 2);
    }

    /**
     * Play the Alkibiades Special tile
     */
    function playAlkibiades($owner1, $from_city1, $to_city1, $owner2, $from_city2, $to_city2) {
        $player_id = self::getCurrentPlayerId();
        $this->checkSpecialTile($player_id, "influence_phase", 6);

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
        $this->flipSpecialTile($player_id, ALKIBIADES);
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
        $player_id = self::getCurrentPlayerId();
        $this->checkSpecialTile($player_id, "influence_phase", 8);

        $this->flipSpecialTile($player_id, PLAGUE);
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
        // sanity check - there is a Sparta leader
        $sparta_leader = $this->Cities->getLeader("sparta");
        if (empty($sparta_leader)) {
            throw new BgaVisibleSystemException("No Sparta Leader!"); // NOI18N
        }

        $player_id = self::getCurrentPlayerId();
        $this->checkSpecialTile($player_id, "commit_phase", 3);

        $location = "";
        $location_name = "";
        // if it's "sparta" then take it from the player's pool
        if ($revoltlocation == "sparta") {
            $players = self::loadPlayersBasicInfos();
            $player_name = $players[$sparta_leader]['player_name'];
            $location = $sparta_leader;
            $location_name = sprintf(self::_("%s's unit pool"), $player_name);
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

        $this->flipSpecialTile($player_id, SLAVEREVOLT);

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
    function flipSpecialTile($player_id, $tile) {
        $tile_name = $this->specialcards[$tile]['name'];
        $players = self::loadPlayersBasicInfos();
        self::notifyAllPlayers("playSpecial", clienttranslate('${player_name} uses Special tile ${special_tile}'), array(
            'i18n' => ['special_tile'],
            'player_id' => $player_id,
            'player_name' => $players[$player_id]['player_name'],
            'tile' => $tile,
            'special_tile' => $tile_name,
        ));
        self::DbQuery("UPDATE player SET special_tile_used=1 WHERE player_id=$player_id");
    }

    /**
     * Spartan player chose first player for influence phase.
     */
    function chooseNextPlayer($first_player) {
        self::checkAction('chooseNextPlayer');
        $players = self::loadPlayersBasicInfos();

        $player_id = self::getActivePlayerId();
        self::notifyAllPlayers("spartanChoice", clienttranslate('${player_name} chooses ${candidate_name} to commit forces first'), array(
            'player_id' => $player_id,
            'player_name' => $players[$player_id]['player_name'],
            'candidate_id' => $first_player,
            'candidate_name' => $players[$first_player]['player_name'],
            'preserve' => ['player_id', 'candidate_id'],
        ));
        self::setGameStateValue("spartan_choice", $first_player);
        $this->gamestate->nextState();
    }

    /**
     * Player chose an Influence tile
     */
    function takeInfluence($influence_id) {
        self::checkAction( 'takeInfluence' );
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
        self::setGameStateValue("last_influence_slot", $slot);

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
        self::checkAction( 'placeAnyCube' );
        $player_id = self::getActivePlayerId();
        $this->addInfluenceToCity($city, $player_id, 1);
        $state = "nextPlayer";
        if ($this->canPlaySpecial($player_id, "influence")) {
            $state = "useSpecial";
        }
        $this->gamestate->nextState($state);
    }

    /**
     * Player is selecting a candidate for a city.
     */
    function proposeCandidate($city, $candidate_id) {
        self::checkAction('proposeCandidate');
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
        $state = $this->canPlaySpecial($actingplayer, "influence") ? "useSpecial" : "nextPlayer";

        $this->gamestate->nextState($state);
    }

    /**
     * Player chose a cube to remove.
     * $cube is a, b, or a number
     */
    function chooseRemoveCube($target_id, $city, $cube) {
        self::checkAction('chooseRemoveCube');
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
                throw new BgaVisibleSystemException("Unexpected game state: Candidate B with no Candidate A"); // NO18N
            }
            $beta = $this->Cities->getCandidate($city, "b");
            if ($beta != $target_id) {
                throw new BgaVisibleSystemException("Missing cube at $city $cube"); // NO18N
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
        $state = $this->canPlaySpecial($player_id, "influence") ? "useSpecial" : "nextPlayer";

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
        self::checkAction('assignUnits');
        $player_id = self::getActivePlayerId();

        // $this->logDebug("$player_id assigns $unitstr");

        if (trim($unitstr) == "") {
            $this->noCommitUnits($player_id);
        } else {
            $this->validateMilitaryCommits($player_id, $unitstr, $cube);
        }
        $state = "nextPlayer";
        if ($this->canPlaySpecial($player_id, "commit")) {
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
        // $this->logDebug("$player_id validates $unitstr");

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
            $battlename = $this->Locations->getName($location);
            // Is this unit in my pool?
            $unit_desc = $this->unitDescription($counter['city'], $counter['strength'], $counter['type'], $battlename);

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
        // $this->logDebug("$player_id passed all validation");
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
        // $this->logDebug("$player_id shipping forces");
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
                // $this->logDebug("$player_id sending to battle $battle");
                $this->sendToBattle($player_id, $f, $battlepos);
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
            if (!$this->hasDefendPermission($player_id, location)) {
                throw new BgaUserException(sprintf(self::_('You need permission from the leader of %s to defend %s'), $this->Cities->getNameTr($city), $this->Locations->getName($location)));
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
        $this->unclaimedTile($tile['id']);
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
        $id = $tile['id'];
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

        $role = $noattacker ? clienttranslate("Defender") : clienttranslate("Attacker");
        $player_id = $noattacker ? $defender : $attacker;
        $players = self::loadPlayersBasicInfos();

        // am I the defender?
        if ($player_id ==$defender) {
            // there was a defender with no attacker: don't win the tile, but get two cubes
            self::notifyAllPlayers('unclaimedTile', clienttranslate('Defender wins uncontested battle at ${location_name}; no one claims the tile'), array(
                'i18n' => ['location_name'],
                'location' => $location,
                'location_name' => $this->Locations->getName($location),
            ));
            $this->addInfluenceToCity($city, $player_id, 2);
            $this->unclaimedTile($id);
        } else {
            // attacker with no defenders
            // you must send units to the last round to win the tile
            $battletype = $this->Locations->getBattle($location, 2) ?? HOPLITE;

            // did we send units of that type?
            $attackingcounters = $this->Battles->getAttackingCounters($location, $battletype);
            if (empty($attackingcounters)) {
                // sent attackers to first round but not second
                self::notifyAllPlayers('unclaimedTile', clienttranslate('Attacker wins uncontested battle at ${location_name}; no one claims the tile'), array(
                    'i18n' => ['location_name'],
                    'location' => $location,
                    'location_name' => $this->Locations->getName($location),
                ));
                $this->unclaimedTile($id);
            } else {
                self::notifyAllPlayers('winBattle', clienttranslate('${player_name} (${role}) wins ${location_name} and claims the tile without a battle'), array(
                    'i18n' => ['location_name', 'role'],
                    'city' => $city,
                    'role' => $role,
                    'player_id' => $player_id,
                    'player_name' => $players[$player_id]['player_name'],
                    'location_name' => $this->Locations->getName($location),
                    'preserve' => ['player_id', 'city'],
                ));
                $this->claimTile($id, $player_id);
            }
        }
    }

    /**
     * Assumes that we already know attacks and defenders are both present. Handles one entire battle (HOPLITE or TRIREME).
     * Rolls until battle is done and there is a winner.
     * @param $type HOPLITE or TRIREME
     * @param $location name of tile
     * @param $slot where the battle is on the board
     * @return {int} ATTACKER or DEFENDER who won
     */
    function resolveBattle($type, $location) {
        $unopposed = null;
        // get all attacking units
        $attstrength = $this->Battles->getAttackStrength($location, $type);
        if (empty($attstrength)) {
            // defenders automatically win this round
            $unopposed = DEFENDER;
        }
        // get all defending units
        $defstrength = $this->Battles->getDefenseStrength($location, $type);
        if (empty($defstrength)) {
            // attackers automatically win this round
            $unopposed = ATTACKER;
        }
        if ($unopposed == null) {
            $militia = $this->Locations->getMilitia($location);
            if ($militia != null) {
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
                        throw new BgaVisibleSystemException("Invalid location militia value: $intrinsic"); // NOI18N
                }
            }
        }
        // unopposed defenders win even if there are militia attackers
        // unopposed attackers still need to beat any defending militias
        $winner = null;
        if ($unopposed == DEFENDER) {
            $winner = DEFENDER;
        } elseif ($unopposed == ATTACKER && $defstrength == 0) {
            $winner = ATTACKER;
        } else {
            // we have a battle!
            $winner = $this->rollBattle($location, $type, $attstrength, $defstrength);
        }
        return $winner;
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
            'slot' => $slot,
            'location_name' => $this->Locations->getName($location),
            'att' => $attstr,
            'def' => $defstr,
            'crt' => $crt,
            'odds' => $this->Battles->getOdds($crt)
        ));
        $winner = null;
        while (self::getGameStateValue(ATTACKER_TOKENS) < 2 && self::getGameStateValue(DEFENDER_TOKENS) < 2) {
            $this->rollCombat($crt);
        }
        // one side has two tokens, but do they both?
        if (self::getGameStateValue(ATTACKER_TOKENS) == 2 && self::getGameStateValue(DEFENDER_TOKENS) == 2) {
            // they need to roll off until one side hits and the other doesn't
            $winner = $this->rollCombat($crt);
            while ($winner == null) {
                $winner = $this->rollCombat($crt);
            }
        } elseif (self::getGameStateValue(ATTACKER_TOKENS) == 2) {
            $winner = ATTACKER;
        } elseif (self::getGameStateValue(DEFENDER_TOKENS) == 2) {
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
    function rollCombat($crt) {
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
        self::notifyAllPlayers("diceRoll", clienttranslate('Attacker rolls ${attd1} ${attd2} ${atttotal}, Defender rolls ${defd1} ${defd2} ${deftotal}'), array(
            'attd1' => $attd1,
            'attd2' => $attd2,
            'defd1' => $defd1,
            'defd2' => $defd2,
            'atttotal' => $attd1+$attd2,
            'deftotal' => $defd1+$defd2,
        ));
        // did either one hit?
        if ($atthit) {
            $this->takeToken(ATTACKER);
        } else {
            self::notifyAllPlayers("miss", clienttranslate('Attacker misses'), []);
        }
        if ($defhit) {
            $this->takeToken(DEFENDER);
        } else {
            self::notifyAllPlayers("miss", clienttranslate('Defender misses'), []);
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
     * @param {int} ATTACKER or DEFENDER
     */
    function takeToken($sideval) {
        $token = "";
        $role = "";
        $side = "";
        if ($sideval == ATTACKER) {
            $side = "attacker";
            $token = ATTACKER_TOKENS;
            $role = clienttranslate("Attacker");
        } elseif ($sideval == DEFENDER) {
            $side = "defender";
            $token = DEFENDER_TOKENS;
            $role = clienttranslate("Defender");
        } else {
            throw new BgaVisibleSystemException("Invalid side to take Token: $sideval"); // NOI18N
        }
        self::notifyAllPlayers("takeToken", clienttranslate('${side_name} rolls a hit'), array(
            'i18n' => ['side_name'],
            'side' => $side,
            'side_name' => $role,
        ));
        if (self::getGameStateValue($token) < 2) {
            self::incGameStateValue($token, 1);
        }
    }

    /**
     * One side has won a battle and gets to claim the tile.
     * @param {id} for tile
     * @param {string} location
     */
    function battleVictory($id, $location) {
        $winner = null;
        $attacker = $this->Battles->getAttacker($location);
        $defender = $this->Battles->getDefender($location);
        if (self::getGameStateValue(ATTACKER_TOKENS) == 2) {
            $winner = $attacker;
            $role = clienttranslate("Attacker");
        } elseif (self::getGameStateValue(DEFENDER_TOKENS) == 2) {
            $winner = $defender;
            $role = clienttranslate("Defender");
        } else {
            throw new BgaVisibleSystemException("No winner found at end of battle for tile $location"); // NOI18N
        }
        $this->claimTile($id, $winner);

        $players = self::loadPlayersBasicInfos();

        self::notifyAllPlayers('battleVictory', clienttranslate('${player_name} (${role}) claims ${location_name} tile'), array(
            'i18n' => ['role', 'location_name'],
            'player_id' => $winner,
            'player_name' => $players[$winner]['player_name'],
            'location_name' => $this->Locations->getName($location),
            'role' => $role,
        ));
    }

    /**
     * Loser of a battle must lose one counter.
     * @param {string} loser player_id of main who picks casualty
     * @param {string} location tile where casualty happens
     */
    function assignCasualty($loser, $location) {

    }

    /**
     * End of battle cleanup.
     * Send military back, reset battle state valies.
     */
    function endBattle($tile) {
        // battle for this location is over
        $this->returnMilitaryUnits($tile);
        // reinitialize battle tokens after every battle
        self::setGameStateValue(ATTACKER_TOKENS, 0);
        self::setGameStateValue(DEFENDER_TOKENS, 0);
        self::setGameStateValue("active_battle", 0);
        self::setGameStateValue("battle_round", 0);
        self::notifyAllPlayers("resetBattleTokens", '', []);
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
            $private[$player_id] = array('special' => $this->canPlaySpecial($player_id, $phase));
        }
        return array(
            '_private' => $private
        );
    }

    /**
     * Get the phase to check against for use of a Special tile.
     * @return "influence, commit, or
     */
    function checkPhase() {
        $state = $this->getStateName();
        if ($state == "takeInfluence") {
            return "influence";
        } elseif ($state == "commitForces") {
            return "commit";
        } elseif ($state == "specialTile") {
            // this may be 0, 1, or 2 (2 = candidate phase, no special tiles)
            if (self::getGameStateValue("influence_phase") == 1) {
                return "influence";
            } elseif (self::getGameStateValue("commit_phase") == 1) {
                return "commit";
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
        if (self::getGameStateValue("influence_phase") > 0) {
            if ($this->allInfluenceTilesTaken()) {
                // no longer taking influence, enter candidates phase
                self::setGameStateValue("influence_phase", 2);

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
        } elseif (self::getGameStateValue("commit_phase") == 1) {
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
        $player_id = self::getGameStateValue("spartan_choice");
        if ($player_id != 0) {
            $this->gamestate->changeActivePlayer($player_id);
            self::setGameStateValue("spartan_choice", 0);
            self::setGameStateValue("commit_phase", 1);
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
            $city_name = ($city == "any") ? self::_("Any") : $this->Cities->getNameTr($city);
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
        $picked = self::getGameStateValue("deadpool_picked");
        if ($picked == 0) {
            $first_player = self::getGameStateValue("spartan_choice");
            $this->gamestate->changeActivePlayer($first_player);
        }

        $state = "nextPlayer";
        $players = self::loadPlayersBasicInfos();
        $nbr = count($players);
        if ($picked == $nbr) {
            self::setGameStateValue("deadpool_picked", 0);
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
            self::incGameStateValue("deadpool_picked", 1);
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
        } else if ($this->canPlaySpecial($player_id, "influence")) {
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
        self::setGameStateValue("influence_phase", 0);

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
        $state = "resolve";
        // commit phase is over
        self::setGameStateValue("commit_phase", 0);

        throw new BgaVisibleSystemException("Start Battles");

        $battle = $this->Battles->nextBattle();
        if ($battle == null) {
            $state = "endTurn";
        }
        $this->gamestate->nextState($state);
    }

    /**
     * Resolve all the battles for the next location in the queue.
     * Assumes we have already checked that there is another location tile to be fought for.
     */
    function stResolveTile() {
        $tile = $this->Battles->nextBattle();
        if ($tile == null) {
            // shouldn't happen!
            throw new BgaVisibleSystemException("No battle tile to resolve"); // NOI18N
        }
        // default next state - we will roll the actual battle if we pass all checks below
        $state = "nextBattle";

        $location = $tile['location'];
        $attacker = $tile['attacker'];
        $defender = $tile['defender'];
        $slot = $tile['slot'];

        self::setGameStateValue("active_battle", $slot);
        // is this the first or second round?
        $round = self::getGameStateValue("battle_round");
        // HOPLITE or TRIREME or null if asking for 2nd round of a land battle
        $battletype = $this->Locations->getBattle($location, $round);
        // no second round, or we finished the second round
        if ($battletype == null) {
            // there should be a winner
            $this->battleVictory($tile['id'], $location);
            $this->endBattle($tile);
            $state = "endBattle";
        } else {
            $is_battle = true;
            if ($round == 0) {
                // first battle
                // flip all the counters
                $counters = $this->Battles->getCounters($location);
                self::notifyAllPlayers("revealCounters", '', array(
                    'slot' => $slot,
                    'military' => $counters
                ));
                // one side has no forces?
                if ($attacker == null || $defender == null) {
                    if ($attacker == null && $defender == null) {
                        $this->noBattle($tile);
                    } else {
                        $this->uncontestedBattle($tile);
                    }
                    $is_battle = false;
                    $this->endBattle($tile);
                    $state = "endBattle";
                }
            }
            // there forces on both sides, so there is a battle
            // this may be round 1 or 2
            if ($is_battle) {
                // can anyone play a special card now?
                $hascard = $this->playersWithSpecial($battletype);
                if (!empty($hascard)) {
                    $state = "special";
                }
            }
        }
        $this->gamestate->nextState($state);
    }

    /**
     * There are forces on both sides.
     * We know there is a battle to be fought.
     */
    function stBattle() {
        $tile = $this->Battles->nextBattle();
        // should not happen!
        if ($tile == null) {
            throw new BgaVisibleSystemException("no battle!");
        }

        $attacker = $tile['attacker'];
        $defender = $tile['defender'];
        // per Martin Wallace: if both sides fight the first round, but no one sent units to the second round of battle,
        // then resolve the battle to see who loses a unit, but no one gets the tile, but the defender gets 2 cubes.
        $location = $tile['location'];
        $slot = $tile['slot'];
        $city = $tile['city'];
        $players = self::loadPlayersBasicInfos();
        self::notifyAllPlayers('battle', clienttranslate('${attacker_name} attacks ${location_name} defended by ${defender_name}'), array(
            'i18n' => ['location_name'],
            'attacker' => $attacker,
            'defender' => $defender,
            'city' => $city,
            'attacker_name' => $players[$attacker]['player_name'],
            'defender_name' => $players[$defender]['player_name'],
            'location_name' => $this->Locations->getName($location),
            'preserve' => ['attacker', 'defender', 'city'],
        ));
        $round = self::getGameStateValue("battle_round");
        $type = $this->Locations->getBattle($location, $round);

        $winner = $this->resolveBattle($type, $location, $slot);
        $loser = null;
        self::incGameStateValue("battle_round", 1);
        // for second round, if there is one, one side reset to 0 Battle tokens, the other starts with one
        if ($winner == ATTACKER) {
            self::setGameStateValue(ATTACKER_TOKENS, 1);
            self::setGameStateValue(DEFENDER_TOKENS, 0);
            $loser = $this->Battles->getDefender($location);
        } elseif ($winner == DEFENDER) {
            self::setGameStateValue(ATTACKER_TOKENS, 0);
            self::setGameStateValue(DEFENDER_TOKENS, 1);
            $loser = $this->Battles->getAttacker($location);
        } else {
            throw new BgaVisibleSystemException("Invalid winner at end of battle: $winner"); // NOI18N
        }
        // loser must lose a unit
        $this->assignCasualty($loser, $location);

        $this->gamestate->nextState("");
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
                $this->Cities->addStatue($leader, $cn);
                self::notifyAllPlayers("addStatue", clienttranslate('${player_name} adds statue in ${city_name}'), array(
                    'i18n' => ['city_name'],
                    'city' => $cn,
                    'city_name' => $this->Cities->getNameTr($cn),
                    'player_id' => $leader,
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
        $this->gamestate->nextState();
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
                default:
                    $this->gamestate->nextState( "zombiePass" );
                	break;
            }

            return;
        }

        if ($state['type'] === "multipleactiveplayer") {
            // Make sure player is in a non blocking status for role turn
            $is_battle = self::getGameStateValue('active_battle') != 0;
            $nextState = $is_battle ? "doBattle" : "nextPlayer";
            $this->gamestate->setPlayerNonMultiactive( $active_player, $nextState );
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
            $military = self::getObjectListFromDB("SELECT id, city, type, strength, location, battlepos FROM MILITARY WHERE location=$player_id");
            if (!empty($military)) {
                shuffle($military);
                while (!empty($military) && $unitstr === "") {
                    $unit = array_pop($military);
                    $city = $unit['city'];
                    // does this unit have any cities to defend?
                    $mycitybattles = self::getObjectListFromDB("SELECT card_type_arg battle FROM LOCATION WHERE card_type=\"$city\" AND card_location=\"".BOARD."\"", true);
    
                    // is there a city we can attack?
                    if (empty($mycitybattles)) {
                        $allbattles = self::getObjectListFromDB("SELECT card_type city, card_type_arg battle, attacker FROM LOCATION WHERE card_location=\"".BOARD."\"");
                        shuffle($allbattles);
                        $location = array_pop($allbattles);
                        $defcity = $location['city'];
                        $battle = $location['battle'];
                        
                        if ($this->Cities->canAttack($player_id, $city, $defcity, $battle)) {
                            // we can attack this city
                            // make sure not sending trireme to a land battle
                            if (!($unit['type'] == TRIREME && $this->Locations->isLandBattle($battle))) {
                                $unitstr = $unit['id']."_attack_".$battle;
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
