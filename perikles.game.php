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
// for wars
define("ARGOS",   0b100000);
define("ATHENS",  0b010000);
define("CORINTH", 0b001000);
define("MEGARA",  0b000100);
define("SPARTA",  0b000010);
define("THEBES",  0b000001);
define("ATTACKER", 0);
define("DEFENDER", 2);
define("MAIN", 1);
define("ALLY", 2);

define("ATTACKER_TOKENS", "attacker_tokens");
define("DEFENDER_TOKENS", "defender_tokens");

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
            // wars
            "argos_wars" => 40,
            "athens_wars" => 41,
            "corinth_wars" => 42,
            "megara_wars" => 43,
            "sparta_wars" => 44,
            "thebes_wars" => 45,
            "active_battle" => 46,
            "battle_round" => 47, // 0,1
            "influence_phase" => 48,

            "last_influence_slot" => 37, // keep track of where to put next Influence tile
            "deadpool_picked" => 38, // how many players have been checked for deadpool?
            "spartan_choice" => 39, // who Sparta picked to go first in military phase
            ATTACKER_TOKENS => 50, // battle tokens won by attacker so far in current battle
            DEFENDER_TOKENS => 51, // battle tokens won by defender so far in current battle
        ) );

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
            foreach(array_keys($this->cities) as $cn) {
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
        $city_states = ["leader", "a", "b", "defeats", "wars"];
        foreach(array_keys($this->cities) as $cn) {
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
        // when we are in the Influence Phase and influence special tiles can be used. Start with Influence, ends with Elections.
        self::setGameStateInitialValue("influence_phase", 1);

        // Init game statistics
        // (note: statistics used in this file must be defined in your stats.inc.php file)
        $this->setupInfluenceTiles();
        $this->setupLocationTiles();
        $this->setupMilitary();
        $this->assignSpecialTiles();
        $this->setupInfluenceCubes();

        // Activate first player (which is in general a good idea :) )
        $this->activeNextPlayer();

        /************ End of the game initialization *****/
    }

    /**
     * Assign Special tile to each player at start of game.
     */
    protected function assignSpecialTiles() {
        $spec = [1,2,3,4,5,6,7,8];
        shuffle($spec);
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

        foreach( $this->cities as $cn => $city) {
            $influence_tiles[] = array('type' => $cn, 'type_arg' => INFLUENCE, 'location' => DECK, 'location_arg' => 0, 'nbr' => $city['influence']);
            $influence_tiles[] = array('type' => $cn, 'type_arg' => CANDIDATE, 'location' => DECK, 'location_arg' => 0, 'nbr' => $city['candidate']);
            $influence_tiles[] = array('type' => $cn, 'type_arg' => ASSASSIN, 'location' => DECK, 'location_arg' => 0, 'nbr' => 1);
        }
        $influence_tiles[] = array('type' => 'any', 'type_arg' => INFLUENCE, 'location' => DECK, 'location_arg' => 0, 'nbr' => 5);
        return $influence_tiles;
    }

    /**
     * Initial assignment of 2 cubes per city per player.
     */
    protected function setupInfluenceCubes() {
        $players = self::loadPlayersBasicInfos();
        foreach($this->cities as $cn => $city) {
            foreach($players as $player_id => $player) {
                self::DbQuery("UPDATE player SET ".$cn." = 2 WHERE player_id=$player_id");
            }
        }
    }

    /**
     * Create the Location deck
     */
    protected function setupLocationTiles() {
        $locations = $this->createLocationTiles();
        $this->location_tiles->createCards($locations, DECK);
        $this->location_tiles->shuffle(DECK);
        for ($i = 1; $i <= 7; $i++) {
            $this->location_tiles->pickCardForLocation(DECK, BOARD, $i);
        }
    }

    /**
     * Fill location card database.
     */
    protected function createLocationTiles() {
        $locations = array();
        foreach($this->locations as $location => $tile) {
            $locations[] = array('type' => $tile['city'], 'type_arg' => $location, 'location' => DECK, 'location_arg' => 0, 'nbr' => 1);
        }
        return $locations;
    }

    /**
     * Create all the military counters
     */
    protected function setupMilitary() {
        $id = 1;
        foreach($this->cities as $cn => $city) {
            $id = $this->createMilitaryUnits($cn, $city, $id);
        }
        // and add the Persians
        $cn = PERSIA;
        $id = $this->createMilitaryUnits($cn, $this->persia[$cn], $id);
    }

    /**
     * Insert units into database
     */
    protected function createMilitaryUnits($cn, $city, $idct) {
        $units = array(
            HOPLITE => "h",
            TRIREME => "t",
        );
        foreach ($units as $unit => $u) {
            $strength = 1;
            for ($i = 1; $i <= 4; $i++) {
                $unittype = $u.$i;
                if (isset($city[$unittype])) {
                    for ($t = 0; $t < $city[$unittype]; $t++) {
                        self::DbQuery( "INSERT INTO MILITARY VALUES($idct,\"$cn\",\"$unit\",$strength,\"$cn\",0)" );
                        $idct++;
                    }
                }
                $strength++;
            }
        }
        return $idct;
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

        $result['locationtiles'] = self::getObjectListFromDB("SELECT card_id id, card_type city, card_type_arg battle, card_location loc, card_location_arg slot FROM LOCATION WHERE card_location !='".DECK."'");
        
        $result['specialtiles'] = $this->getSpecialTiles($current_player_id);
        $result['influencecubes'] = $this->getInfluenceCubes();
        $result['defeats'] = $this->getDefeats();
        $result['leaders'] = $this->getLeaders();
        $result['candidates'] = $this->getCandidates();
        $result['statues'] = $this->getStatues();
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
     * Return double associative array of player_id => city => influence
     */
    protected function getInfluenceCubes() {
        $influencecubes = array();

        $players = self::loadPlayersBasicInfos();
        foreach ($players as $player_id => $player) {
            $influencecubes[$player_id] = array();
            foreach($this->cities as $cn => $city) {
                $influencecubes[$player_id][$cn] = self::getUniqueValueFromDB("SELECT $cn FROM player WHERE player_id=$player_id");
            }
        }
        return $influencecubes;
    }

    /**
     * Return associative array (city => #defeats)
     */
    function getDefeats() {
        $defeats = array();
        foreach ($this->cities as $cn => $city) {
            $defeats[$cn] = self::getGameStateValue($cn."_defeats");
        }
        return $defeats;
    }

    /**
     * Return associative array: city => player_id
     */
    function getLeaders() {
        $leaders = array();
        foreach (array_keys($this->cities) as $cn) {
            $leader = self::getGameStateValue($cn."_leader");
            if ($leader != 0) {
                $leaders[$cn] = $leader;
            }
        }
        return $leaders;
    }

    /**
     * Is this player leader of a city?
     */
    function isLeader($player_id, $city) {
        return self::getGameStateValue($city."_leader") == $player_id;
    }

    /**
     * Get an array of cities led by this player.
     */
    function getControlledCities($player_id) {
        $cities = array();
        foreach (array_keys($this->cities) as $cn) {
            if ($this->isLeader($player_id, $cn)) {
                $cities[] = $cn;
            }
        }
        return $cities;
    }

    /**
     * Return associative array: "city_a" and "city_b" => player_id
     */
    function getCandidates() {
        $candidates = array();
        foreach ($this->cities as $cn => $city) {
            foreach(["a", "b"] as $c) {
                $cv = $cn."_".$c;
                $candidate = self::getGameStateValue($cv);
                if ($candidate != 0) {
                    $candidates[$cv] = $candidate;
                }
            }
        }
        return $candidates;
    }

    /**
     * Return double associative array: "city" => $player_id => statues
     */
    function getStatues() {
        $statues = array();
        $players = self::loadPlayersBasicInfos();
        foreach($this->cities as $cn => $city) {
            $statues[$cn] = array();
            foreach ($players as $player_id => $player) {
                $s = self::getStat($cn."_statues", $player_id);
                if ($s != 0) {
                    $statues[$cn][$player_id] = $s;
                }
            }
        }
        return $statues;
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
                if (!($this->isLeader($player_id, $military[$id]['city']) || $this->isActiveBattleLocation($military[$id]['location']))) {
                    $military[$id]['id'] = 0;
                    $military[$id]['strength'] = 0;
                }
            }
        }
        return $military;
    }

    /**
     * Check whether the location slot is set to the current battle.
     */
    function isActiveBattleLocation($location) {
        $slot = self::getUniqueValueFromDB("SELECT card_location_arg slot FROM LOCATION WHERE card_type_arg=\"$location\" AND card_location=\"".BOARD."\"");
        return ($slot == self::getGameStateValue("active_battle"));
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
     * Does this player have any influence in the city, including a candidate?
     */
    function hasInfluenceInCity($player_id, $city) {
        foreach(["a", "b"] as $c) {
            if (self::getGameStateValue($city."_".$c) == $player_id) {
                return true;
            }
        }
        return ($this->influenceInCity($player_id, $city) > 0);
    }

    /**
     * Can player nominate in this city?
     */ 
    function canNominate($player_id, $city) {
        $open = false;
        foreach(["a", "b"] as $c) {
            if (self::getGameStateValue($city."_".$c) == 0) {
                $open = true;
            }
            if ($open) {
                if ($this->hasInfluenceInCity($player_id, $city)) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Is there any city this player can nominate in?
     */
    function canNominateAny($player_id) {
        foreach (array_keys($this->cities) as $city) {
            if ($this->canNominate($player_id, $city)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Influence of a player in a city.
     */
    function influenceInCity($player_id, $city) {
        return self::getUniqueValueFromDB("SELECT $city FROM player WHERE player_id=$player_id");
    }

    /**
     * Are all the Candidate slots filled?
     * @return true if someone is able to nominate in a city, otherwise false
     */
    function canAnyoneNominate() {
        $players = self::loadPlayersBasicInfos();
        foreach (array_keys($players) as $player_id) {
            if ($this->canNominateAny($player_id)) {
                return true;
            }
        }
        return false;
    }

    /**
     * There is a hard limit of 30 cubes on the board per player.
     * Returns all the cubes this player has on board (including candidates)
     */
    function allCubesOnBoard($player_id) {
        $cubes = 0;
        foreach($this->cities as $cn => $city) {
            $cubes += $this->influenceInCity($player_id, $cn);
            foreach(["a", "b"] as $c) {
                $cv = $cn."_".$c;
                $candidate = self::getGameStateValue($cv);
                if ($candidate == $player_id) {
                    $cubes++;
                }
            }
        }
        return $cubes;
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
            $name = $this->cities[$name]['name'];

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
     * Only does the DB adjustment for influence in city
     * @param {string} city
     * @param {string} player_id
     * @param {int} cubes may be negative
     */
    function changeInfluenceInCity($city, $player_id, $cubes) {
        $influence = $this->influenceInCity($player_id, $city);
        $influence += $cubes;
        if ($influence < 0) {
            throw new BgaVisibleSystemException("Cannot reduce influence below 0");
        }
        self::DbQuery("UPDATE player SET $city = $influence WHERE player_id=$player_id");
    }

    /**
     * Create translateable description string of a unit
     */
    function unitDescription($city, $strength, $type, $location) {
        $home_city = $this->cities[$city]['name'];
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

        $cubect = $this->allCubesOnBoard($player_id);
        if ($cubect >= 30) {
            throw new BgaUserException("You already have 30 cubes on the board");
        }

        $this->changeInfluenceInCity($city, $player_id, $cubes);
        $city_name = $this->cities[$city]['name'];

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
     */
    function unclaimedTile($id) {
        $this->location_tiles->insertCardOnExtremePosition($id, UNCLAIMED, true);
        self::DbQuery("UPDATE LOCATION SET attacker=NULL,defender=NULL,permissions=NULL WHERE card_id=$id");
    }

    /**
     * A player claims a tile
     */
    function claimTile($id, $player_id) {
        $this->location_tiles->insertCardOnExtremePosition($id, $player_id, true);
        self::DbQuery("UPDATE LOCATION SET attacker=NULL,defender=NULL,permissions=NULL WHERE card_id=$id");
    }

    /**
     * As Leader of a city, player takes all military units.
     */
    function moveMilitaryUnits($player_id, $city) {
        $players = self::loadPlayersBasicInfos();
        self::DbQuery("UPDATE MILITARY SET location = $player_id WHERE location=\"$city\"");
        $units = self::getObjectListFromDB("SELECT id, city, type, strength, location FROM MILITARY WHERE city=\"$city\" AND location=$player_id");
        self::notifyAllPlayers("takeMilitary", clienttranslate('${player_name} takes military units from ${city_name}'), array(
            'i18n' => ['city_name'],
            'player_id' => $player_id,
            'player_name' => $players[$player_id]['player_name'],
            'city' => $city,
            'city_name' => $this->cities[$city]['name'],
            'military' => $units,
            'preserve' => ['player_id', 'city'],
        ));
    }

    /**
     * Move all military units from a battle location back to the city where it belongs
     */
    function returnMilitaryUnits($battle) {
        $location = $battle['location'];
        $slot = $battle['slot'];
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
                'unit' => ($pid == $player_id) ? $counter['id'] : 0,
                'type' => $counter['type'],
                'unit_type' => $counter['type'] == HOPLITE ? clienttranslate("Hoplite") : clienttranslate("Trireme"),
                'strength' => ($pid == $player_id) ? $counter['strength'] : 0,
                'city' => $counter['city'],
                'city_name' => $this->cities[$counter['city']]['name'],
                'battlepos' => $battlepos,
                'battlerole' => $role,
                'location' => $battle,
                'slot' => $slot,
                'location_name' => $this->locations[$battle]['name'],
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
        foreach(array_keys($this->cities) as $cn) {
            if ($player_id == self::getGameStateValue($cn."_leader")) {
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
            if ($phase == $this->specialcards[$tileid]["phase"]) {
                $canplay[] = $player_id;
            }
        }
        return $canplay;
    }

    /**
     * Can a player play a Special Tile now.
     * @return true if player_id can play a Special now
     */
    function canPlaySpecial($player_id, $phase) {
        $players = $this->playersWithSpecial($phase);
        $canplay = in_array($player_id, $players);
        return $canplay;
    }

    /**
     * Check whether this player can spend an influence cube to commit extra units.
     * Must have influence cube in the city, and be leader.
     */
    function canSpendInfluence($player_id, $city) {
        return ($this->influenceInCity($player_id, $city) > 0) && ($player_id == self::getGameStateValue($city."_leader"));
    }

    /**
     * Return double associative array,
     * all cities this player is leader of, with lowest strength Hoplite and/or Trireme from the deadpool for each
     */
    function deadPoolUnits($player_id) {
        $deadpool = array();
        foreach(array_keys($this->cities) as $cn) {
            if ($player_id == self::getGameStateValue($cn."_leader")) {
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
     * Set values for both cities war game state values
     */
    function declareWar($city1, $city2) {
        if ($city1 == $city2) {
            throw new BgaVisibleSystemException("City cannot declare war on itself!"); // NO18N
        }
        $warbits = $this->getWarBits();
        $war1 = self::getGameStateValue($city1."_wars");
        $war2 = self::getGameStateValue($city2."_wars");
        self::setGameStateValue($city1."_wars", $war1 + $warbits[$city2]);
        self::setGameStateValue($city2."_wars", $war2 + $warbits[$city1]);
    }

    /**
     * Are these cities at war?
     */
    function atWar($city1, $city2) {
        $warbits = $this->getWarBits();
        $wars1 = self::getGameStateValue($city1."_wars");
        return $wars1 & $warbits[$city2];
    }

    /**
     * Associative array, city to war bitmask
     */
    function getWarBits() {
        $warbits = array(
            "argos" => ARGOS,
            "athens" => ATHENS,
            "corinth" => CORINTH,
            "megara" => MEGARA,
            "sparta" => SPARTA,
            "thebes" => THEBES
        );
        return $warbits;
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
        if (!$this->isLeader($assigner, $location['city'])) {
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
        foreach(["sparta_defeats", "athens_defeats"] as $defeat) {
            if (self::getGameStateValue($defeat) >= 4) {
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
     * Return next battle tile, or null if there are no more.
     */
    function nextBattle() {
        $battle = null;
        $battles = self::getObjectListFromDB("SELECT card_id id, card_type city, card_type_arg location, card_location_arg slot, attacker, defender FROM LOCATION WHERE card_location = \"".BOARD."\" ORDER BY card_location_arg ASC LIMIT 1");
        if (!empty($battles)) {
            $battle = array_pop($battles);
        }
        return $battle;
    }

    /**
     * Reset the battle tokens.
     */
    function resetBattleTokens() {
        self::setGameStateValue(ATTACKER_TOKENS, 0);
        self::setGameStateValue(DEFENDER_TOKENS, 0);
        self::setGameStateValue("active_battle", 0);
        self::setGameStateValue("battle_round", 0);
        self::notifyAllPlayers("resetBattleTokens", '', []);
    }

    /**
     * Calculate which column on the CRT to use
     * @param $att attack strength
     * @param $def defense strength
     */
    function getCRT($att, $def) {
        if ($att >= ($def*3)) {
            // 3:1
            return 6;
        } else if ($att >= ($def*2)) {
            // 2:1
            return 5;
        } else if ($att >= ($def+2)) {
            // +2
            return 4;
        } else if ($att >= $def || ($att > 1 && $att >= ($def-1))) {
            // 1:1
            return 3;
        } else if (($att*2) > $def) {
            // -2
            return 2;
        } else {
            // 1:2
            return 1;
        }
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Player actions
//////////// 

    /**
     * Player either Played or Passed on special tile button.
     */
    function playSpecialTile($use) {
        self::checkAction('useSpecial');
        $nextstate = "nextPlayer";
        // 0 means player passed
        if ($use) {
            $player_id = self::getCurrentPlayerId(); // we are in multiplayeractive
            $special = self::getObjectFromDB("SELECT special_tile tile, special_tile_used used FROM player WHERE player_id=$player_id", true);
            // sanity check
            if ($special == null) {
                throw new BgaVisibleSystemException("No special tile found"); // NOI18N
            } else if ($special['used']) {
                throw new BgaVisibleSystemException("You have already used your special tile"); // NOI18N
            }
            $t = $special['tile'];
            $tile = $this->specialcards[$t];
            $state = $this->getStateName();
            switch ($t) {
                case 1: // Perikles
                    if (self::getGameStateValue("influence_phase") == 0) {
                        throw new BgaVisibleSystemException("This Special Tile cannot be used during the current phase"); // NOI18N
                    }
                    $this->addInfluenceToCity('athens', $player_id, 2);
                    break;
                case 2; // Persian Fleet
                    break;
                case 3; // Slave Revolt
                    break;
                case 4; // Brasidas
                    break;
                case 5; // Thessalanian Allies
                    break;
                case 6; // Alkibiades
                    break;
                case 7; // Phormio
                    break;
                case 8; // Plague
                    if (self::getGameStateValue("influence_phase") == 0) {
                        throw new BgaVisibleSystemException("This Special Tile cannot be used during the current phase"); // NOI18N
                    }
                    throw new BgaVisibleSystemException($tile['name']." in $state"); // NOI18N
                    break;
                default:
                    throw new BgaVisibleSystemException("Unknown special tile: $t"); // NOI18N
            }
            $this->flipSpecialTile($player_id, $tile);
        }
        $this->gamestate->nextState($nextstate);
    }

    /**
     * Player played their Special Tile. Flip it and mark it used.
     */
    function flipSpecialTile($player_id, $tile) {

        $players = self::loadPlayersBasicInfos();
        self::notifyAllPlayers("playSpecial", clienttranslate('${player_name} uses Special tile ${special_tile}'), array(
            'i18n' => ['special_tile'],
            'player_id' => $player_id,
            'player_name' => $players[$player_id]['player_name'],
            'special_tile' => $tile['name'],
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
        self::debug("placeAnyCube: $state");
        $this->gamestate->nextState($state);
    }

    /**
     * Player is selecting a candidate for a city.
     */
    function proposeCandidate($city, $candidate_id) {
        self::checkAction('proposeCandidate');
        $actingplayer = self::getActivePlayerId();
        $city_name = $this->cities[$city]['name'];
        // player must have a cube in the city
        if (!$this->hasInfluenceInCity($actingplayer, $city)) {
            throw new BgaUserException(sprintf(self::_("You cannot propose a Candidate in %s: you have no Influence cubes in this city"), $city_name));
        }

        $players = self::loadPlayersBasicInfos();
        $candidate_name = $players[$candidate_id]['player_name'];
        // is there an available candidate slot?
        $candidate_slot = $city."_a";
        $cand_a = self::getGameStateValue($candidate_slot);
        if ($cand_a != 0) {
            $candidate_slot = $city."_b";
            $cand_b = self::getGameStateValue($candidate_slot);
            if ($cand_b != 0) {
                throw new BgaUserException(sprintf(self::_("%s has no empty Candidate spaces"), $city_name));
            } else if ($cand_a == $candidate_id) {
                throw new BgaUserException(sprintf(self::_("%s is already a Candidate in %s"), $candidate_name, $city_name));
            }
        }
        // does the nominated player have cubes there?
        $cubes = self::getUniqueValueFromDB("SELECT $city FROM player WHERE player_id=$candidate_id");
        if ($cubes == 0) {
            throw new BgaUserException(sprintf(self::_("%s has no Influence cubes in %s"), $candidate_name, $city_name));
        }
        // passed checks, can assign Candidate
        $this->changeInfluenceInCity($city, $candidate_id, -1);

        self::setGameStateValue($candidate_slot, $candidate_id);

        $c = ($candidate_slot == $city."_a") ? ALPHA : BETA;
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
        self::debug("candidate: $state");

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
        $city_name = $this->cities[$city]['name'];
        if ($cube == 'a') {
            $alpha = self::getGameStateValue($city.'_a');
            if ($alpha != $target_id) {
                throw new BgaVisibleSystemException("Missing cube at $city $cube"); // NO18N
            }
            self::setGameStateValue($city.'_a', 0);
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
            $beta = self::getGameStateValue($city.'_b');
            if ($beta != 0) {
                self::setGameStateValue($city.'_b', 0);
                self::setGameStateValue($city.'_a', $beta);
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
        } else if ($cube == 'b') {
            $alpha = self::getGameStateValue($city.'_a');
            if ($alpha == 0) {
                throw new BgaVisibleSystemException("Unexpected game state: Candidate B with no Candidate A"); // NO18N
            }
            $beta = self::getGameStateValue($city.'_b');
            if ($beta != $target_id) {
                throw new BgaVisibleSystemException("Missing cube at $city $cube"); // NO18N
            }
            self::setGameStateValue($city.'_b', 0);
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
            $this->changeInfluenceInCity($city, $target_id, -1);
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
        self::debug("assassinate: $state");
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

        if ($unitstr == "") {
            $this->noCommitUnits($player_id);
        } else {
            $this->validateMilitaryCommits($player_id, $unitstr, $cube);
        }
        $this->gamestate->nextState();
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
            if (!$this->canSpendInfluence($player_id, $cube)) {
                throw new BgaUserException(sprintf(self::_("You cannot send extra units from %s"), $this->cities[$cube]['name']));
            }
        }

        $units = explode(" ", trim($unitstr));
        // get main attackers/defenders location => player
        $main_attacker = [];
        $main_defender = [];
        $mycities = $this->getControlledCities($player_id);
        $myforces = array(
            'attack' => [],
            'defend' => [],
        );
        // MAKE NO CHANGES IN DB until this loop is completed!
        foreach($units as $unit) {
            [$id, $side, $location] = explode("_", $unit);
            $counter = self::getObjectFromDB("SELECT id, city, type, location, strength FROM MILITARY WHERE id=$id");
            $counter['battle'] = $location;
            $battlename = $this->locations[$location]['name'];
            // Is this unit in my pool?
            $unit_desc = $this->unitDescription($counter['city'], $counter['strength'], $counter['type'], $battlename);
            if ($counter['location'] != $player_id) {
                throw new BgaUserException(sprintf(self::_("%s is not in your available pool"), $unit_desc));
            }
            $battlecity = $this->locations[$location]['city'];
            // am I attacking my own city?
            if ($side == "attack" && in_array($battlecity, $mycities)) {
                throw new BgaUserException(sprintf(self::_("%s cannot attack a city you control!"), $unit_desc));
            } else if ($side == "defend" && !in_array($battlecity, $mycities)) {
                // Do I own this city? If not, I need permission from defender
                if (!$this->hasDefendPermission($player_id, $location)) {
                    throw new BgaUserException(sprintf(self::_('You need permission from the leader of %s to defend %s'), $this->cities[$battlecity]['name'], $battlename));
                }
            }
            // is this unit at war with the destination location?
            if ($side == "defend" && $this->atWar($counter['city'], $battlecity)) {
                throw new BgaUserException(sprintf(self::_("%s cannot defend a location belonging to a city it is at war with!"), $unit_desc));
            }
            // are we sending a trireme to a land battle?
            if ($this->locations[$location]['rounds'] == "H" && $counter['type'] == TRIREME) {
                throw new BgaUserException(sprintf(self::_("%s cannot be sent to a land battle"), $unit_desc));
            }

            $maindef = MAIN+DEFENDER;
            $allydef = ALLY+DEFENDER;
            $mainatt = MAIN+ATTACKER;
            $allyatt = ALLY+ATTACKER;
            $attacker = self::getUniqueValueFromDB("SELECT attacker FROM LOCATION WHERE card_type_arg=\"$location\"");
            $defender = self::getUniqueValueFromDB("SELECT defender FROM LOCATION WHERE card_type_arg=\"$location\"");
            if ($attacker != null) {
                $main_attacker[$location] = $attacker;
            }
            if ($defender != null) {
                $main_defender[$location] = $defender;
            }
            $defenders = self::getCollectionFromDB("SELECT city, battlepos FROM MILITARY WHERE location=\"$location\" AND (battlepos=$maindef OR battlepos=$allydef)", true);
            $attackers = self::getCollectionFromDB("SELECT city, battlepos FROM MILITARY WHERE location=\"$location\" AND (battlepos=$mainatt OR battlepos=$allyatt)", true);
            if ($side == "attack") {
                foreach(array_keys($defenders) as $def) {
                    if (in_array($def, $mycities)) {
                        throw new BgaUserException(sprintf(self::_("%s cannot attack a city which you are also defending!"), $unit_desc));
                    }
                }
                // Is there already a main attacker who is not me?
                if ($attacker == null) {
                    // I am now the main attacker
                    $main_attacker[$location] = $player_id;
                } else if ($attacker != $player_id) {
                    // is this unit at war with any of the other attackers?
                    foreach (array_keys($attackers) as $otheratt) {
                        if ($this->atWar($counter['city'], $otheratt)) {
                            throw new BgaUserException(sprintf(self::_("%s cannot ally with units from a city it is at war with!"), $unit_desc));
                        }
                    }
                }
                $myforces['attack'][] = $counter;
            } else if ($side == "defend") {
                // is there already a main defender?
                if ($defender == null) {
                    // I am now the main defender
                    $main_defender[$location] = $player_id;
                } else if ($defender != $player_id) {
                    // is this unit at war with any of the other defenders?
                    foreach (array_keys($defenders) as $otherdef) {
                        if ($this->atWar($counter['city'], $otherdef)) {
                            throw new BgaUserException(sprintf(self::_("%s cannot ally with units from a city it is at war with!"), $unit_desc));
                        }
                    }
                }
                foreach(array_keys($attackers) as $att) {
                    if (in_array($att, $mycities)) {
                        throw new BgaUserException(sprintf(self::_("%s cannot attack a city which you are also defending!"), $unit_desc));
                    }
                }
                $myforces['defend'][] = $counter;
            }
        }
        // all units passed all tests for valid assignment
        // did we spend an influence cube?
        if ($cube != "" && count($units) > 2) {
            $this->changeInfluenceInCity($cube, $player_id, -1);
            self::notifyAllPlayers('spentInfluence', clienttranslate('${player_name} spent an Influence cube from ${city_name} to send extra units'), array(
                'i18n' => ['city_name'],
                'candidate_id' => $player_id, // candidate because that's the notif arg
                'player_id' => $player_id,
                'player_name' => self::getActivePlayerName(),
                'city' => $cube,
                'city_name' => $this->cities[$cube]['name'],
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
     * When there are no forces on either side at a city tile.
     * According to Martin Wallace, should almost never happen!
     * Neither side gets a tile or any cubes.
     * https://boardgamegeek.com/thread/1109420/collection-all-martin-wallace-errata-clarification
     * @param $battle battle DB row from LOCATIOn
     */
    function noBattle($battle) {
        $location = $battle['location'];
        self::notifyAllPlayers('unclaimedTile', clienttranslate('No battle at ${location_name}; no one claims the tile'), array(
            'i18n' => ['location_name'],
            'location' => $location,
            'location_name' => $this->locations[$location]['name'],
        ));
        $this->unclaimedTile($battle['id']);
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
    function uncontestedBattle($battle) {
        $id = $battle['id'];
        $location = $battle['location'];
        $city = $battle['city'];
        $attacker = $battle['attacker'];
        $defender = $battle['defender'];
        $slot = $battle['slot'];
        // should be null attacker or defender but not both
        $noattacker = ($attacker == null);

        $role = $noattacker ? clienttranslate("Defender") : clienttranslate("Attacker");
        $player_id = $noattacker ? $defender : $attacker;
        $players = self::loadPlayersBasicInfos();

        // am I the defender?
        if ($player_id ==$defender) {
            // don't win the tile, but get two cubes
            self::notifyAllPlayers('unclaimedTile', clienttranslate('No battle at ${location_name}; no one claims the tile'), array(
                'i18n' => ['location_name'],
                'location' => $location,
                'location_name' => $this->locations[$location]['name'],
            ));
            $this->addInfluenceToCity($city, $player_id, 2);
            $this->unclaimedTile($id);
        } else {
            // attacker with no defenders
            
            self::notifyAllPlayers('winBattle', clienttranslate('${player_name} (${role}) wins ${location_name} without a battle'), array(
                'i18n' => ['location_name', 'role'],
                'city' => $city,
                'role' => $role,
                'player_id' => $player_id,
                'player_name' => $players[$player_id]['player_name'],
                'location_name' => $this->locations[$location]['name'],
                'preserve' => ['player_id', 'city'],
            ));
        }
    }

    /**
     * Assumes that we already know attacks and defenders are both present. Runs until battle is done.
     * @param $type HOPLITE or TRIREME
     * @param $location name of tile
     * @param $intrinsic any intrinsic defenders
     * @param $slot where the battle is on the board
     */
    function doBattle($type, $location, $intrinsic, $slot) {
        $unopposed = null;
        // get all attacking units
        $mainattackers = self::getObjectListFromDB("SELECT id, city, strength FROM MILITARY WHERE type=\"$type\" AND location=\"$location\" AND battlepos=".(ATTACKER+MAIN));
        $allyattackers  = self::getObjectListFromDB("SELECT id, city, strength FROM MILITARY WHERE type=\"$type\" AND location=\"$location\" AND battlepos=".(ATTACKER+ALLY));
        $attackers = array_merge($mainattackers, $allyattackers);
        if (empty($attackers)) {
            // defenders automatically win this round
            $unopposed = DEFENDER;
        }
        // get all defending units
        $maindefenders = self::getObjectListFromDB("SELECT id, city, strength FROM MILITARY WHERE type=\"$type\" AND location=\"$location\" AND battlepos=".(DEFENDER+MAIN));
        $allydefenders  = self::getObjectListFromDB("SELECT id, city, strength FROM MILITARY WHERE type=\"$type\" AND location=\"$location\" AND battlepos=".(DEFENDER+ALLY));
        $defenders = array_merge($maindefenders, $allydefenders);
        if (empty($defenders)) {
            // attackers automatically win this round
            $unopposed = ATTACKER;
        }

        $attstrength = 0;
        foreach($attackers as $a) {
            $attstrength += $a['strength'];
        }
        $defstrength = 0;
        foreach($defenders as $d) {
            $defstrength += $d['strength'];
        }
        if ($intrinsic != null) {
            switch ($intrinsic) {
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
                    throw new BgaVisibleSystemException("Invalid intrisinc location option: $intrinsic"); // NOI18N
            }
        }
        if ($unopposed == DEFENDER) {
            self::incGameStateValue(DEFENDER_TOKENS, 1);
        } elseif ($unopposed == ATTACKER && $defstrength == 0) {
            self::incGameStateValue(ATTACKER_TOKENS, 1);
        } else {
            $crt = $this->getCRT($attstrength, $defstrength);
            $battle_type = ($type == HOPLITE) ? self::_("Hoplite") : self::_("Trireme");
            self::notifyAllPlayers('crtOdds', clienttranslate('${unit_type} battle of ${location_name}: attacker strength ${att} vs. defender strength ${def}, rolling in the ${odds} column'), array(
                'i18n' => ['unit_type', 'location_name'],
                'unit_type' => $battle_type,
                'location' => $location,
                'slot' => $slot,
                'location_name' => $this->locations[$location]['name'],
                'att' => $attstrength,
                'def' => $defstrength,
                'crt' => $crt,
                'odds' => $this->combat_results_table[$crt]['odds']
            ));
            $this->rollBattle($crt);
        }
    }

    /**
     * Resolve a single round - Hoplite or Trireme battle. Roll until one side wins.
     */
    function rollBattle($crt) {
        $attacker_tn = $this->combat_results_table[$crt]['attacker'];
        $defender_tn = $this->combat_results_table[$crt]['defender'];
        throw new BgaVisibleSystemException("Rolling battle");

        while (self::getGameStateValue(ATTACKER_TOKENS) < 2 && self::getGameStateValue(DEFENDER_TOKENS) < 2) {
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
            if ($atthit || $defhit) {
                if ($atthit && $defhit) {
                    // if they are both at 1 and 1 keep going
                    if (self::getGameStateValue(ATTACKER_TOKENS) != 1 && self::getGameStateValue(DEFENDER_TOKENS) != 1) {
                        // both get a token
                        self::incGameStateValue(ATTACKER_TOKENS, 1);
                        self::incGameStateValue(DEFENDER_TOKENS, 1);
                    }
                } else {
                    // only one scored
                    if ($atthit) {
                        self::incGameStateValue(ATTACKER_TOKENS, 1);
                    } else {
                        self::incGameStateValue(DEFENDER_TOKENS, 1);
                    }
                }
            }
        }
        // we have a winner for this battle
        $attacker_tokens = self::getGameStateValue(ATTACKER_TOKENS);
        $defender_tokens = self::getGameStateValue(DEFENDER_TOKENS);
        // sanity check: one and only one should be at 2
        if ($attacker_tokens >= 2) {
            if ($defender_tokens >= 2) {
                throw new BgaVisibleSystemException("both sides scored 2 battle tokens in battle"); // NOI18N
            } else {
                self::setGameStateValue(ATTACKER_TOKENS, 1);
                self::setGameStateValue(DEFENDER_TOKENS, 0);
            }
        } elseif ($defender_tokens >= 2) {
            self::setGameStateValue(ATTACKER_TOKENS, 0);
            self::setGameStateValue(DEFENDER_TOKENS, 1);
        } else {
            throw new BgaVisibleSystemException("no victory rolled in battle"); // NOI18N
        }
    }

    /**
     * One side has won a battle and gets to claim the tile.
     */
    function battleVictory($attacker_id, $defender_id, $id) {
        $loccard = $this->location_tiles->getCard($id);
        $location = $loccard['type_arg'];

        $players = self::loadPlayersBasicInfos();
        // who won?
        $attacker_tokens = self::getGameStateValue(ATTACKER_TOKENS);
        $defender_tokens = self::getGameStateValue(DEFENDER_TOKENS);
        // one and only one should be 2
        $winner= null;
        $winner_id = 0;
        $loser_id = 0;
        $role = null;
        if ($attacker_tokens >= 2) {
            if ($defender_tokens >= 2) {
                // something wrong happened
                throw new BgaVisibleSystemException("both sides have 2 victory tokens at $location"); // NOI18N
            }
            $winner = ATTACKER;
        } elseif ($defender_tokens >= 2) {
            $winner = DEFENDER;
        } else {
                // something wrong happened
                throw new BgaVisibleSystemException("neither side has 2 victory tokens at $location"); // NOI18N
        }

        if ($winner == ATTACKER) {
            $winner_id = $attacker_id;
            $loser_id = $defender_id;
            $role = clienttranslate("Attacker");
        } else {
            $winner_id = $defender_id;
            $loser_id = $attacker_id;
            $role = clienttranslate("Defender");
        }
        
        self::notifyAllPlayers('battleVictory', clienttranslate('${player_name} (${role}) claims ${location_name} tile'), array(
            'i18n' => ['role', 'location_name'],
            'player_id' => $winner_id,
            'player_name' => $players[$winner_id]['player_name'],
            'location_name' => $this->locations[$location]['name'],
            'role' => $role,
        ));

        $this->claimTile($id, $winner_id);
    }

    /**
     * Assumes active battle has been set to current location tile and a round.
     * Returns HOPLITE or TRIREME.
     */
    function getCurrentBattleType($location) {
        $rounds = $this->locations[$location]['rounds'];
        $r = self::getGameStateValue("battle_round");
        $ti = $rounds[$r];
        $type = null;
        if ($ti == "H") {
            $type = HOPLITE;
        } elseif ($ti == "T") {
            $type = TRIREME;
        } else {
            throw new BgaVisibleSystemException("failed to get unit type: $ti");
        }
        return $type;
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
        $player_id = self::getActivePlayerId();
        return array(
            '_private' => array(
                $player_id => array(
                    'special' => $this->canPlaySpecial($player_id, "influence")
                )
            )
        );
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Game state actions
////////////

    /**
     * Handles next player action through Influence phase.
     */
    function stNextPlayer() {
        $state = "";
        if ($this->allInfluenceTilesTaken()) {
            // we're nominating candidates
            if ($this->canAnyoneNominate()) {
                $player_id = self::activeNextPlayer();
                if ($this->canNominateAny($player_id)) {
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
            $city_name = ($city == "any") ? self::_("Any") : $this->cities[$city]['name'];
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
            $counters = self::getObjectListFromDB("SELECT id FROM MILITARY WHERE location=$player_id");
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
        self::debug("stPlaceInfluence: $state");
        $this->gamestate->nextState( $state );
    }

    /**
     * Do all the elections.
     */
    function stElections() {
        $players = self::loadPlayersBasicInfos();
        // end of influence phase
        self::setGameStateValue("influence_phase", 0);
        foreach ($this->cities as $cn => $city) {
            $city_name = $city['name'];

            $a = self::getGameStateValue($cn."_a");
            $b = self::getGameStateValue($cn."_b");
            $winner = 0;
            if ($a == 0) {
                if ($b == 0) {
                    // no candidates!
                    self::notifyAllPlayers("noElection", clienttranslate('${city_name} has no Candidates; no Leader assigned'), array(
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
            } elseif ($b == 0) {
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
                $a_inf = $this->influenceInCity($a, $cn);
                $b_inf = $this->influenceInCity($b, $cn);
                // default
                $winner = $a;
                $loser_inf = $b_inf;
                if ($a_inf != $b_inf) {
                    if ($a_inf < $b_inf) {
                        $winner = $b;
                        $loser_inf = $a_inf;
                    }
                }
                $this->changeInfluenceInCity($cn, $winner, -$loser_inf);
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
            foreach(["a", "b"] as $c) {
                self::setGameStateValue($cn."_".$c, 0);
            }
            self::setGameStateValue($cn."_leader", $winner);

            if ($winner != 0) {
                $this->moveMilitaryUnits($winner, $cn);
            }
        }
        $sparta = self::getGameStateValue("sparta_leader");
        $this->gamestate->changeActivePlayer($sparta);
        $this->gamestate->nextState();
    }

    /**
     * Do the battles.
     */
    function stStartBattles() {
        $state = "resolve";

        $battle = $this->nextBattle();

        if ($battle == null) {
            $state = "endTurn";
        }
        $this->gamestate->nextState($state);
    }

    /**
     * Resolve all the battles for the next location in the queue.
     * Assumes we have already checked that there is another location tile to be fought for.
     */
    function stResolveLocation() {
        $battle = $this->nextBattle();
        // shouldn't happen!
        if ($battle == null) {
            throw new BgaVisibleSystemException("No battle tiles for stResolveLocation");
        }
        // default next state
        $state = "doBattle";

        $attacker = $battle['attacker'];
        $defender = $battle['defender'];
        $location = $battle['location'];
        $rounds = $this->locations[$location]['rounds'];

        self::setGameStateValue("active_battle", $battle['slot']);
        // is this the first or second round?
        $round = self::getGameStateValue("battle_round");

        $is_battle = true;

        if ($round == 0) {
            $slot = $battle['slot'];
            // flip all the counters
            $counters = self::getObjectListFromDB("SELECT id, city, type, strength, location, battlepos FROM MILITARY WHERE location=\"$location\"");
            self::notifyAllPlayers("revealCounters", '', array(
                'slot' => $slot,
                'military' => $counters
            ));
            if ($attacker == null || $defender == null) {
                if ($attacker == null && $defender == null) {
                    $this->noBattle($battle);
                } else {
                    $this->uncontestedBattle($battle);
                }
                $is_battle = false;
            }
        } else {
            // is there another battle to be fought?
            if ($rounds == "H" || $rounds == "T") {
                $is_battle = false;
            }
        }

        if ($is_battle) {
            // can anyone play a special card now?
            $r = $rounds[$round];
            $phase = ($r == "H") ? HOPLITE : TRIREME;
            $hascard = $this->playersWithSpecial($phase);
            if (!empty($hascard)) {
                $state = "special";
            }
        } else {
            // did someone win?
            if (self::getGameStateValue(ATTACKER_TOKENS) > 0 xor self::getGameStateValue(DEFENDER_TOKENS) > 0) {
                $this->battleVictory($attacker, $defender, $battle['id']);
            }
            // battle for this location is over
            $this->returnMilitaryUnits($battle);
            // reinitialize battle tokens after every battle
            $this->resetBattleTokens();
            $state = "endBattle";
        }
        $this->gamestate->nextState($state);
    }

    /**
     * There are forces on both sides.
     * We know there is a battle to be fought.
     */
    function stBattle() {
        $battle = $this->nextBattle();
        // should not happen!
        if ($battle == null) {
            throw new BgaVisibleSystemException("no battle!");
        }

        $attacker = $battle['attacker'];
        $defender = $battle['defender'];
        // per Martin Wallace: if both sides fight the first round, but no one sent units to the second round of battle,
        // then resolve the battle to see who loses a unit, but no one gets the tile, but the defender gets 2 cubes.
        $location = $battle['location'];
        $slot = $battle['slot'];
        $city = $battle['city'];
        $players = self::loadPlayersBasicInfos();
        self::notifyAllPlayers('battle', clienttranslate('${attacker_name} attacks ${location_name} defended by ${defender_name}'), array(
            'i18n' => ['location_name'],
            'attacker' => $attacker,
            'defender' => $defender,
            'city' => $city,
            'attacker_name' => $players[$attacker]['player_name'],
            'defender_name' => $players[$defender]['player_name'],
            'location_name' => $this->locations[$location]['name'],
            'preserve' => ['attacker', 'defender', 'city'],
        ));
        
        $$type = $this->getCurrentBattleType($location);

        $intrinsic = $this->locations[$location]['intrinsic'];

        $this->doBattle($type, $location, $intrinsic, $slot);
        self::incGameStateValue("battle_round", 1);
    }

    /**
     * End of turn refresh.
     */
    function stEndTurn() {
        self::incStat(1, 'turns_number');
        $state = $this->isEndGame() ? "endGame" : "nextTurn";

        $players = self::loadPlayersBasicInfos();
        // add statues
        foreach (array_keys($this->cities) as $cn) {
            $leader = self::getGameStateValue($cn."_leader");
            if ($leader != 0) {
                self::setGameStateValue($cn."_leader", 0);
                self::incStat(1, $cn."_statues", $leader);
                self::notifyAllPlayers("addStatue", clienttranslate('${player_name} adds statue in ${city_name}'), array(
                    'i18n' => ['city_name'],
                    'city' => $cn,
                    'city_name' => $this->cities[$cn]['name'],
                    'player_id' => $leader,
                    'player_name' => $players[$leader]['player_name'],
                    'preserve' => ['player_id', 'city'],
                ));
            }
        }
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

    /**
     * Choose players who can use a special tile
     */
    function stUseSpecial() {
        $players = [];
        // is this a commit round?
        $is_battle = self::getGameStateValue('active_battle') != 0;
        if ($is_battle) {
            $battle = $this->nextBattle();
            $location = $battle['location'];
            $type = $this->getCurrentBattleType($location);
            $players = $this->playersWithSpecial($type);
            $this->gamestate->setPlayersMultiactive($players, "doBattle", true);
        } else {
            // take influence phase
            // TODO: this will make all players with an Influence-phase tile active...
            $players = $this->playersWithSpecial("influence");
            $this->gamestate->setPlayersMultiactive($players, "nextPlayer", true);
        }
    }

    function stDebug() {
        $player = self::getActivePlayerName();
        throw new BgaVisibleSystemException("$player in stDebug");
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
                    $cities = array_keys($this->cities);
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
                    $this->assignUnits("", "");
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
        $cities = array_keys($this->cities);
        shuffle($cities);
        foreach($cities as $cn) {
            if ($this->canNominate($player_id, $cn)) {
                $a = self::getGameStateValue($cn."_a");
                if ($a == 0) {
                    foreach ($players as $candidate_id => $player) {
                        if ($this->influenceInCity($candidate_id, $cn) > 0) {
                            $this->proposeCandidate($cn, $candidate_id);
                            return;
                        }
                    }
                } else {
                    $b = self::getGameStateValue($cn."_b");
                    if ($b != 0) {
                        throw new BgaVisibleSystemException("Unexpected zombie state: cannot nominate candidate in $cn"); //NO18N
                    }
                    foreach ($players as $candidate_id => $player) {
                        if ($candidate_id != $a && $this->influenceInCity($candidate_id, $cn) > 0) {
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
        $cities = array_keys($this->cities);
        shuffle($cities);
        foreach ($cities as $cn) {
            $players = self::loadPlayersBasicInfos();
            $toremove = [];
            foreach(["a", "b"] as $c) {
                $cd = self::getGameStateValue($cn."_".$c);
                if ($cd != 0 && $cd != $player_id) {
                    $toremove[] = $c;
                }
            }
            foreach(array_keys($players) as $target_id) {
                if ($player_id != $target_id && $this->influenceInCity($target_id, $cn) > 0) {
                    $toremove[] = $target_id;
                }
            }
            shuffle($toremove);
            $killcube = array_pop($toremove);
            if ($killcube == "a" || $killcube == "b") {
                $this->chooseRemoveCube(self::getGameStateValue($cn."_".$killcube), $cn, $killcube);
                break;
            } else {
                $this->chooseRemoveCube($killcube, $cn, 1);
                break;
            }
        }
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
