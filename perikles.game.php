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
require_once( 'modules/PeriklesDeadpool.class.php' );

//  MARTIN WALLACE'S ERRATA ON BGG: https://boardgamegeek.com/thread/1109420/collection-all-martin-wallace-errata-clarification

// player preferences
define("AUTOPASS", 100);

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

// state values
define("ATHENS_PLAYER", "athens_previous");
define("ATTACKER_TOKENS", "attacker_tokens");
define("DEFENDER_TOKENS", "defender_tokens");
define("INFLUENCE_PHASE", "influence_phase");
define("COMMIT_PHASE", "commit_phase");
define("LAST_INFLUENCE", "last_influence_slot");
define("ACTIVE_BATTLE", "active_battle");
define("BATTLE_ROUND", "battle_round");
define("LOSER", "battle_loser");
define("FIRST_PLAYER_BATTLE", "spartan_choice");
define("DEADPOOL_COUNTER", "deadpool_picked");

// permission states
define("REQUESTING_PLAYER", "permission_requesting_player");
define("REQUESTING_CITY", "permission_requesting_city");
define("REQUESTED_LOCATION", "permission_requested_location");
define("REQUEST_STATUS", "permission_request_status");

// use these to flag city_deadpool states that have been picked already or to be picked
define("DEADPOOL_NOPICK", 0);
define("DEADPOOL_TOPICK", 1);
define("DEADPOOL_PICKED", 2);

// Special tiles
define("PERIKLES", "perikles");
define("PERSIANFLEET", "persianfleet");
define("SLAVEREVOLT", "slaverevolt");
define("BRASIDAS", "brasidas");
define("THESSALANIANALLIES", "thessalanianallies");
define("ALKIBIADES", "alkibiades");
define("PHORMIO", "phormio");
define("PLAGUE", "plague");

define("CONTROLLED_PERSIANS", "_persia_"); // used to flag Persian units that will go to a player board

define("DEADPOOL", "deadpool");
define("UNIT_PENDING", "pending_unit");
define("CUBE_PENDING", "pending_cube");

// Polyfill for PHP 4 - PHP 7, safe to utilize with PHP 8
if (!function_exists('str_contains')) {
    function str_contains (string $haystack, string $needle)
    {
        return empty($needle) || strpos($haystack, $needle) !== false;
    }
}

class Perikles extends Table
{
	function __construct( )
	{
        parent::__construct();

        self::initGameStateLabels( array(
            "initial_influence" => 70,

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
            // deadpool picks
            "argos_deadpool" => 70,
            "athens_deadpool" => 71,
            "corinth_deadpool" => 72,
            "megara_deadpool" => 73,
            "sparta_deadpool" => 74,
            "thebes_deadpool" => 75,
            // permission requests
            // theoretically could be requesting permission for up to four different locations!
            REQUESTING_CITY."1" => 80,
            REQUESTING_CITY."2" => 81,
            REQUESTING_CITY."3" => 82,
            REQUESTING_CITY."4" => 83,
            REQUESTED_LOCATION."1" => 84,
            REQUESTED_LOCATION."2" => 85,
            REQUESTED_LOCATION."3" => 86,
            REQUESTED_LOCATION."4" => 87,
            REQUEST_STATUS."1" => 90,
            REQUEST_STATUS."2" => 91,
            REQUEST_STATUS."3" => 92,
            REQUEST_STATUS."4" => 93,
            REQUESTING_PLAYER => 88, // player who initiated permission requests

            ACTIVE_BATTLE => 40,
            BATTLE_ROUND => 41, // 0,1
            LOSER => 42,
            ATTACKER_TOKENS => 43, // battle tokens won by attacker so far in current battle
            DEFENDER_TOKENS => 44, // battle tokens won by defender so far in current battle

            INFLUENCE_PHASE => 50,
            LAST_INFLUENCE => 51, // keep track of where to put next Influence tile
            COMMIT_PHASE => 52,
            FIRST_PLAYER_BATTLE => 55, // who Sparta picked to go first in military phase
            DEADPOOL_COUNTER => 56, // how many players have been checked for deadpool?
            ATHENS_PLAYER => 57, // most recent leader of Athens

            BRASIDAS => 60, // Brasides activated for next battle
            PHORMIO => 61, // Phormio activated for next battle
        ) );

        $this->Cities = new PeriklesCities($this);
        $this->Locations = new PeriklesLocations($this);
        $this->Battles = new PeriklesBattles($this);
        $this->SpecialTiles = new PeriklesSpecial($this);
        $this->Deadpool = new PeriklesDeadpool($this);

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
        foreach( $players as $player_id => $player ) {
            $color = array_shift( $default_colors );
            $values[] = "('".$player_id."','$color','".$player['player_canal']."','".addslashes( $player['player_name'] )."','".addslashes( $player['player_avatar'] )."')";
            foreach($this->Cities->cities() as $cn) {
                $statues = $cn."_statues";
                self::initStat( 'player', $statues, 0, $player_id);
            }
            $player_stats = array("persian_leader", "victory_tiles", "victory_tile_points", "statue_points", "cube_points", "battles_won_attacker", "battles_won_defender","battles_lost_attacker", "battles_lost_defender");
            foreach($player_stats as $stat) {
                self::initStat( 'player', $stat, 0, $player_id);
            }

            // get autopass pref
            $autopass = $this->player_preferences[$player_id][AUTOPASS] ?? 0;
            self::DbQuery("UPDATE player SET special_tile_pass=$autopass WHERE player_id=$player_id");
        }
        self::initStat('table', 'turns_number', 0);
        self::initStat('table', 'unclaimed_tiles', 0);

        $sql .= implode(',', $values );
        self::DbQuery( $sql );
        self::reattributeColorsBasedOnPreferences( $players, $gameinfos['player_colors'] );
        self::reloadPlayersBasicInfos();

        /************ Start the game initialization *****/

        // Init global values with their initial values
        $city_states = ["leader", "a", "b", "defeats", "deadpool"];
        foreach($this->Cities->cities() as $cn) {
            foreach ($city_states as $lbl) {
                self::setGameStateInitialValue( $cn."_".$lbl, 0 );
            }
        }
        self::setGameStateInitialValue("initial_influence", 0);
        self::setGameStateInitialValue(LAST_INFLUENCE, 0);
        self::setGameStateInitialValue(DEADPOOL_COUNTER, -1);
        self::setGameStateInitialValue(FIRST_PLAYER_BATTLE, 0);
        self::setGameStateInitialValue(ATTACKER_TOKENS, 0);
        self::setGameStateInitialValue(DEFENDER_TOKENS, 0);
        self::setGameStateInitialValue(ACTIVE_BATTLE, 0);
        self::setGameStateInitialValue(BATTLE_ROUND, 0);
        self::setGameStateInitialValue(LOSER, -1);
        self::setGameStateInitialValue(BRASIDAS, 0);
        self::setGameStateInitialValue(PHORMIO, 0);

        for ($i = 1; $i <= 4; $i++) {
            self::setGameStateInitialValue(REQUESTING_CITY.$i, 0);
            self::setGameStateInitialValue(REQUESTED_LOCATION.$i, 0);
            self::setGameStateInitialValue(REQUEST_STATUS.$i, 0);
            $this->globals->set(UNIT_PENDING.$i, "");
        }
        self::setGameStateInitialValue(REQUESTING_PLAYER, 0);
        $this->globals->set(CUBE_PENDING, "");

        // when we are in the Influence Phase and influence special tiles can be used. Start with Influence, ends with candidate nominations.
        self::setGameStateInitialValue(INFLUENCE_PHASE, 0);
        // when we are in the committing phase. Start with first commit, end with battle phase.
        self::setGameStateInitialValue(COMMIT_PHASE, 0);

        $this->Cities->setupNewGame();

        $this->setupInfluenceTiles();

        $this->Locations->setupNewGame();

        $this->SpecialTiles->setupNewGame();
        foreach( $players as $player_id => $player ) {
            $special = $this->SpecialTiles->getSpecialTileIndex($player_id);
            self::initStat( 'player', "special_tile", $special, $player_id);
        }

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
        $result['wars'] = $this->Cities->getCityRelationships();
        $result['permissions'] = $this->Locations->getAllPermissions();

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
        $is_battle = ($this->getGameStateValue(ACTIVE_BATTLE) != 0);
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
    function getGameProgression() {
        $players = self::loadPlayersBasicInfos();
        $turn = self::getStat('turns_number');
        $turnx = count($players) == 3 ? 21 : 19;
        $p = $turn * $turnx;

        // 26 cards (36 - 10 on board), may go to as low as 16
        $cardsremaining = $this->influence_tiles->countCardInLocation(DECK);
        $p += (26 - $cardsremaining);
        // how many tiles have been collected?
        // 21 locations * 2 (+42)
        $cardlocs = $this->location_tiles->countCardsInLocations();
        foreach($cardlocs as $loc => $num) {
            if (($loc != DECK) && ($loc != BOARD)) {
                $p += (2 * $num);
            }
        }
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
     * Has this player say autopass to true?
     * @param {string} player_id
     * @return {bool} true if player_id will pass during Special Tile phase
     */
    function isAutopass($player_id) {
        $pass = $this->getUniqueValueFromDB("SELECT special_tile_pass FROM player WHERE player_id=$player_id");
        return ($pass == 1);
    }

    /**
     * Convenience function, add VPs to a player's total.
     * @param {string} player_id
     * @param {int} vp
     */
    function addVPs($player_id, $vp) {
        self::DbQuery( "UPDATE player SET player_score=player_score+$vp WHERE player_id=$player_id" );
    }

    /**
     * Convenience function to get VP total for player.
     * @param {string} player_id
     * @param {int} VP for player_id
     */
    function getVPs($player_id) {
        $vp = $this->getUniqueValueFromDB("SELECT player_score from player WHERE player_id=$player_id");
        return $vp;
    }

    /**
     * for adding to the tie-breaking aux score
     * @param {string} player_id
     * @param {int} vp
     */
    function addAuxVPs($player_id, $vp) {
        self::DbQuery( "UPDATE player SET player_score_aux=player_score_aux+$vp WHERE player_id=$player_id" );
    }

    /**
     * Convenience function to get Aux VP total for player.
     * @param {string} player_id
     * @param {int} aux VP for player_id
     */
    function getAuxVPs($player_id) {
        $auxvp = $this->getUniqueValueFromDB("SELECT player_score_aux from player WHERE player_id=$player_id");
        return $auxvp;
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
     * Create translateable description string of a unit.
     * @param string $city
     * @param string $strength
     * @param string $type
     * @param string $location (optional) label for location tile
     * @return string descriptive string for logs
     */
    function unitDescription($city, $strength, $type, $location=null) {
        $home_city = $this->Cities->getNameTr($city);
        $unit_type = $this->getUnitName($type);
        if ($location == null) {
            $desc = sprintf(clienttranslate("%s %s-%s"), $home_city, $unit_type, $strength);
        } else {
            $location_name = $this->Locations->getName($location);
            $desc = sprintf(clienttranslate("%s %s-%s at %s"), $home_city, $unit_type, $strength, $location_name);
        }
        return $desc;
    }

    /**
     * Add cubes to a city and send notification.
     */
    function addInfluenceToCity($city, $player_id, $cubes) {
        $players = self::loadPlayersBasicInfos();
        $player_name = $players[$player_id]['player_name'];

        $cubect = $this->Cities->allCubesOnBoard($player_id);
        if ($cubect >= 30) {
            self::notifyAllPlayers('cubeLimit', clienttranslate('${player_name} has 30 cubes on the board and cannot add more Influence'), array(
                'player_id' => $player_id,
                'player_name' => $player_name,
            ));
        } else {
            $this->Cities->changeInfluence($city, $player_id, $cubes);
            $city_name = $this->Cities->getNameTr($city);
    
            self::notifyAllPlayers('influenceCubes', clienttranslate('${icon} ${player_name} adds ${cubes} Influence to ${city_name}'), array(
                'i18n' => ['city_name'],
                'player_id' => $player_id,
                'player_name' => $player_name,
                'icon' => true,
                'cubes' => $cubes,
                'city' => $city,
                'city_name' => $city_name,
                'preserve' => ['city', 'player_id']
            ));
        }
    }

    /**
     * Draw a new Influence card from deck and place.
     */
    function drawInfluenceTile() {
        $slot = $this->getGameStateValue(LAST_INFLUENCE);
        $this->influence_tiles->pickCardForLocation(DECK, BOARD, $slot);
        $newtile = self::getObjectFromDB("SELECT card_id id, card_type city, card_type_arg type, card_location location, card_location_arg slot FROM INFLUENCE WHERE card_location = \"".BOARD."\" AND card_location_arg =$slot");
        $this->setGameStateValue(LAST_INFLUENCE, 0);

        $descriptors = $this->influenceTileDescriptors($newtile);
        $city_name = $descriptors[0];
        $msg = clienttranslate('New Influence tile: ${shards}-Shard ${city_name} tile');
        $i18n = ['city_name'];
        $inf_type = $descriptors[2];
        if (!empty($inf_type)) {
            $msg = clienttranslate('New Influence tile: ${shards}-Shard ${city_name} tile (${inf_type})');
            $i18n[] = ['inf_type'];
        }
        self::notifyAllPlayers("influenceCardDrawn", $msg, array(
            'i18n' => $i18n,
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
        self::incStat(1, 'unclaimed_tiles');
        $this->moveTile($tile, UNCLAIMED);
    }

    /**
     * A player claims a tile. Return military units.
     * Add tile to player's board. Send notification to move tile.
     * Add defeat if attacker won.
     * @param {string} player_id
     * @param {Object} tile
     * @param {int} winner ATTACKER or DEFENDER
     */
    function claimTile($player_id, $tile, $winner) {
        $players = self::loadPlayersBasicInfos();
        $persians = $this->Cities->getPersianLeaders();
        if (count($persians) > 1 && in_array($player_id, $persians)) {
            $this->persiansClaimTile($tile);
        } else {
            $location = $tile['location'];
            $vp = $this->Locations->getVictoryPoints($location);
            $this->addVPs($player_id, $vp);
            self::notifyAllPlayers('claimTile', clienttranslate('${icon} ${player_name} claims ${location_name} tile'), array(
                'i18n' => ['location_name'],
                'city' => $tile['city'],
                'icon' => true,
                'location' => $location,
                'player_id' => $player_id,
                'vp' => $vp,
                'player_name' => $players[$player_id]['player_name'],
                'location_name' => $this->Locations->getName($location),
                'preserve' => ['player_id', 'city', 'location'],
            ));
            $this->moveTile($tile, $player_id);
            self::incStat(1, "victory_tiles", $player_id);
        }
        if ($winner == ATTACKER) {
            $this->defeatCity($tile['city']);
        }
    }

    /**
     * Handle the special case where Persians are under joint control, and each Persian player
     * gets the tile.
     * @param {Object} tile
     */
    function persiansClaimTile($tile) {
        $persians = $this->Cities->getPersianLeaders();
        $num_persians = count($persians);
        // sanity check
        if ($num_persians < 2) {
            throw new BgaVisibleSystemException("should be multiple Persian leaders in this state"); // NOI18N
        }
        $id = $tile['id'];
        $location = $tile['location'];
        $vp = $this->Locations->getVictoryPoints($location);

        // we use a terrible hack here, setting location as "persiaN" N= number of Persians
        $persian_label = PERSIA.$num_persians;
        self::DbQuery("UPDATE LOCATION SET card_location=\"$persian_label\" WHERE card_id=$id");
        $i = 1;

        $sql = "UPDATE LOCATION SET ";
        foreach ($persians as $persia_leader) {
            $this->addVPs($persia_leader, $vp);
            self::incStat(1, "victory_tiles", $persia_leader);
            $dbcol = PERSIA.$i;
            $sql .= "$dbcol=$persia_leader";
            if ($i < $num_persians) {
                $sql .= ",";
            }
            $i++;
        }
        $sql .= " WHERE card_id=$id";
        self::DbQuery($sql);

        $slot = $tile['slot'];
        $city = $tile['city'];
        self::notifyAllPlayers('claimTilePersians', clienttranslate('${icon} Persian players jointly claim ${location_name} tile'), array(
            'i18n' => ['location_name'],
            'city' => $city,
            'icon' => true,
            'location' => $location,
            'location_name' => $this->Locations->getName($location),
            'persians' => $persians,
            'slot' => $slot,
            'vp' => $vp,
            'preserve' => ['city', 'location'],
        ));

        $this->returnMilitaryUnits($tile);
        $this->Locations->clearBattleStatus($id);
    }

    /**
     * Add a defeat to a city.
     */
    function defeatCity($city) {
        $defeats = $this->Cities->addDefeat($city);
        $num = $this->ordinals[$defeats];

        self::notifyAllPlayers('cityDefeat', clienttranslate('${icon} ${city_name} suffers ${num} defeat'), array(
            'i18n' => ['city_name', 'num'],
            'city' => $city,
            'num' => $num,
            'city_name' => $this->Cities->getNameTr($city),
            'icon' => true,
            'defeats' => $defeats,
            'preserve' => ['city', 'defeats'],
        ));
    }

    /**
     * First return all military.
     * Then move a tile either to a player board or unclaimed pile.
     * Does not send notification.
     */
    function moveTile($tile, $destination) {
        $id = $tile['id'];
        $this->returnMilitaryUnits($tile);
        $this->location_tiles->insertCardOnExtremePosition($id, $destination, true);
        $this->Locations->clearBattleStatus($id);
    }

    /**
     * As Leader of a city, player takes all military units.
     */
    function moveMilitaryUnits($player_id, $city) {
        $units = $this->Battles->claimCountersInCity($player_id, $city);
        // send notification that moves units from city stack to player's military zone
        self::notifyAllPlayers("takeMilitary", '', array(
            'city' => $city,
            'military' => $units,
        ));
    }

    /**
     * Assign Persian units to "persians" location which js interprets as put in persian leader(s) military area.
     * @param {array} persianleaders should already have been verified non-empty
     */
    function movePersianUnits($persianleaders) {
        $persianunits = $this->Battles->claimPersians();
        self::notifyAllPlayers("takePersians", '', array(
            'military' => $persianunits,
            'persianleaders' => $persianleaders
        ));
    }

    /**
     * Move all military units from a battle location back to the city where it belongs
     * @param {array} tile to return from
     * @param {string} (optional) type HOPLITE or TRIREME (all if null)
     */
    function returnMilitaryUnits($tile, $type=null) {
        $location = $tile['location'];
        $returned = $this->Battles->returnCounters($location, $type);
        // need to send units who are in deadpool because of async
        $ids = [];
        foreach($returned as $r) {
            $ids[] = $r['id'];
        }

        self::notifyAllPlayers("returnMilitary", '', array(
            'location' => $location,
            'slot' => $tile['slot'],
            'type' => $type ?? "",
            'ids' => $ids,
        ));
    }

    /**
     * End of turn, turn every city leader into a statue, send notification.
     */
    function leadersToStatues() {
        $players = self::loadPlayersBasicInfos();

        foreach ($this->Cities->cities() as $cn) {
            $leader = $this->Cities->getLeader($cn);
            if (!empty($leader)) {
                $statues = $this->Cities->addStatue($leader, $cn);
                self::notifyAllPlayers("addStatue", clienttranslate('${icon}Statue to ${player_name} erected in ${city_name}'), array(
                    'i18n' => ['city_name'],
                    'city' => $cn,
                    'city_name' => $this->Cities->getNameTr($cn),
                    'player_id' => $leader,
                    'statues' => $statues,
                    'icon' => true,
                    'leader' => 'statue',
                    'player_name' => $players[$leader]['player_name'],
                    'preserve' => ['player_id', 'city', 'leader'],
                ));
            }
        }
    }

    /**
     * Return all military counters from players' pools.
     */
    function returnPlayersMilitary() {
        $players = self::loadPlayersBasicInfos();
        foreach (array_keys($players) as $player_id) {
            $counters = $this->Battles->returnCounters($player_id);
            if (!empty($counters)) {
                self::notifyAllPlayers("returnMilitaryPool", '', array(
                    'player_id' => $player_id,
                    'counters' => $counters,
                ));
            }
        }
        // add Persians!
        $persians = $this->Battles->returnCounters(CONTROLLED_PERSIANS);
        if (!empty($persians)) {
            self::notifyAllPlayers("returnMilitaryPool", '', array(
                'player_id' => "persia",
                'counters' => $persians,
                'persianleaders' => $this->Cities->getPersianLeaders()
            ));
        }
    }

    /**
     * Assumes all checks have been done. Send a military unit to a battle location.
     * @param {string} player_id sending player
     * @param {array} mil the counter
     * @param {int} battlepos MAIN/ALLY+ATTACKER/DEFENDER
     */
    function sendToBattle($player_id, $mil, $battlepos) {
        $id = $mil['id'];
        $location = $mil['location'];
        $counter = $this->Battles->getCounter($id);

        // send to location in Db
        $this->Battles->toLocation($id, $location, $battlepos);

        $role = $this->getRoleName($battlepos);
        $slot = self::getUniqueValueFromDB("SELECT card_location_arg from LOCATION WHERE card_type_arg=\"$location\"");

        $players = self::loadPlayersBasicInfos();
        $owning_players = [];
        foreach (array_keys($players) as $pid) {
            // don't accidentally add Persian player twice
            if (!in_array($pid, $owning_players)) {
                if ($player_id == $pid) {
                    $owning_players[] = $pid;
                } elseif ($counter['city'] == PERSIA) {
                    // check in case Persians are under shared control
                    if ($this->Cities->isLeader($player_id, PERSIA) && $this->Cities->isLeader($pid, PERSIA)) {
                        $owning_players[] = $pid;
                    }
                }
            }
        }
        $nonowningplayers = array_diff(array_keys($players), $owning_players);

        // default to owning players shows id and strength
        $notif_args = array(
            'i18n' => ['location_name', 'battlerole', 'unit_type', 'city_name'],
            'player_id' => $player_id,
            'player_name' => $players[$player_id]['player_name'],
            'id' => $id,
            'type' => $counter['type'],
            'unit_type' => $this->getUnitName($counter['type']),
            'strength' => $counter['strength'],
            'city' => $counter['city'],
            'city_name' => $this->Cities->getNameTr($counter['city']),
            'battlepos' => $battlepos,
            'battlerole' => $role,
            'icon' => true,
            'location' => $location,
            'wars' => $this->Cities->getCityRelationships(),
            'slot' => $slot,
            'owners' => $owning_players,
            'location_name' => $this->Locations->getName($location),
            'preserve' => ['city', 'location', 'battlepos', 'type'],
        );

        $msg = clienttranslate('${player_name} sends ${city_name} ${unit_type} to ${location_name} as ${battlerole} ${icon}');
        // send this to the owners
        foreach($owning_players as $pid) {
            self::notifyPlayer($pid, "sendBattle", $msg, $notif_args);
        }
        // now replace id and strength with 0 for everyone else
        $notif_args['id'] = 0;
        $notif_args['strength'] = 0;
        foreach($nonowningplayers as $npid) {
            self::notifyPlayer($npid, "sendBattle", $msg, $notif_args);
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
     * Get all cities controlled by this player that have been marked as needing a deadpool choice.
     * @param string player_id
     * @return array cities (may be empty)
     */
    function getDeadpoolCities($player_id) {
        $cities = array();
        foreach($this->Cities->controlledCities($player_id) as $city) {
            if ($this->getGameStateValue($city."_deadpool") == DEADPOOL_TOPICK) {
                $cities[] = $city;
            }
        }
        return $cities;
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
                $dead = $this->Battles->getCountersByCity($cn, DEADPOOL);
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
     * Let a player give another city permission to defend a location.
     * @param string location tile being given permission for
     * @param string city being given permission
     * @param bool bDefend give/retract permission
     */
    function setDefendPermission($location, $city, $bDefend) {
        // make sure assigner owns it
        $assigner = self::getCurrentPlayerId();

        $controlling_city = $this->Locations->getCity($location);
        if (!$this->Cities->isLeader($assigner, $controlling_city)) {
            throw new BgaUserException(sprintf(self::_("You do not control %s"), $this->Cities->getNameTr($controlling_city)));
        }
        if ($city != PERSIA && $this->Cities->isLeader($assigner, $city)) {
            throw new BgaUserException(sprintf(self::_("You are the Leader of %s!"), $this->Cities->getNameTr($city)));
        }

        if ($bDefend) {
            // cannot give permission to a city at war
            if ($this->Cities->atWar($controlling_city, $city)) {
                throw new BgaUserException(self::_("You cannot give permission to defend a location to a city at war with that location's controlling city"));
            }
        } else {
            // cannot revoke permission if there are already defenders there
            $defenders = $this->Cities->getAllDefenders($location, $controlling_city);
            if (in_array($city, $defenders)) {
                throw new BgaUserException(self::_("You cannot revoke permissions from a city that has already placed defenders at this location"));
            }
        }     
        $this->Locations->setPermission($location, $city, $bDefend);

        $permission = $bDefend ? clienttranslate("grants") : clienttranslate("denies");

        $players = self::loadPlayersBasicInfos();
        self::notifyAllPlayers('givePermission', clienttranslate('${player_name} ${grants_or_denies} permission for ${city_name} to defend ${location_name}'), array(
            'i18n' => ['location_name', 'city_name', 'grants_or_denies'],
            'player_id' => $assigner,
            'player_name' =>  $players[$assigner]['player_name'],
            'grants_or_denies' => $permission,
            'location' => $location,
            'location_name' => $this->Locations->getName($location),
            'city' => $city,
            'city_name' => $this->Cities->getNameTr($city),
            'permissions' => $this->Locations->getPermissions($location),
            'preserve' => ['player_id', 'location', 'city'],
        ));
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
     * Move new locations to slots.
     */
    function dealNewLocations() {
        $this->location_tiles->shuffle(DECK);
        for ($i = 1; $i <= 7; $i++) {
            $this->location_tiles->pickCardForLocation(DECK, BOARD, $i);
        }
        $locations = self::getObjectListFromDB("SELECT card_id id, card_type city, card_type_arg location, card_location loc, card_location_arg slot FROM LOCATION WHERE card_location='".BOARD."'");
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

    /**
     * Get military counter by id.
     * @param {int} id
     * @return {Object} counter
     */
    function getCounterById($id) {
        $counter = self::getObjectFromDB("SELECT id, city, type, location, strength FROM MILITARY WHERE id=$id");
        return $counter;
    }

    /**
     * For an unopposed election, sends notification about the winner.
     * @param {string} winner player id
     * @param {string} city
     */
    function unopposedElection($winner, $city) {
        $players = self::loadPlayersBasicInfos();
        self::notifyAllPlayers("election", clienttranslate('${icon} ${player_name} becomes Leader of ${city_name} unopposed'), array(
            'i18n' => ['city_name'],
            'player_id' => $winner,
            'player_name' => $players[$winner]['player_name'],
            'city' => $city,
            'city_name' => $this->Cities->getNameTr($city),
            'cubes' => 0,
            'icon' => true,
            'leader' => 'leader',
            'preserve' => ['player_id', 'city', 'leader']
            ));
    }

    /**
     * Assumes we have determined this is a contested election. Calculates winner, adjusts influence, sends notification.
     * @param {string} city
     * @return {string} the winner player id
     */
    function resolveElection($city) {
        $a = $this->Cities->getCandidate($city, "a");
        $b = $this->Cities->getCandidate($city, "b");

        $a_inf = $this->Cities->influence($a, $city);
        $b_inf = $this->Cities->influence($b, $city);
        // default
        $winner = $a;
        $loser_inf = $b_inf;
        if ($a_inf < $b_inf) {
            $winner = $b;
            $loser_inf = $a_inf;
        }
        $players = self::loadPlayersBasicInfos();

        $this->Cities->changeInfluence($city, $winner, -$loser_inf);

        self::notifyAllPlayers("election", clienttranslate('${icon} ${player_name} becomes Leader of ${city_name}'), array(
            'i18n' => ['city_name'],
            'player_id' => $winner,
            'player_name' => $players[$winner]['player_name'],
            'city' => $city,
            'city_name' => $this->Cities->getNameTr($city),
            'cubes' => $loser_inf,
            'icon' => true,
            'leader' => 'leader',
            'preserve' => ['player_id', 'city', 'leader']
        ));
        return $winner;
    }

    /**
     * After elections, make anyone who's not a city leader a Persian leader.
     * Send notifications.
     */
    function setPersianLeaders() {
        $players = self::loadPlayersBasicInfos();
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
                self::incStat(1, "persian_leader", $persian);
            }
            $this->movePersianUnits($persianleaders);
        }
    }

    /**
     * Make sure all commitment assignments are valid. Will throw an error if invalid.
     * If any defender assignments require permission, return an array of locations that need permission and do not commit any assignments.
     * @param string player_id
     * @param string units a space concatenateds string of units
     * @param string cube empty string or name of city spending an extra cube
     * @return array associative array of city => [unitid => locations] requesting permission to defend, or empty array if no permissions needed
     */
    function validateMilitaryCommits($player_id, $units, $cube) {
        // do all the checks for whether this is a valid action
        // can I commit extra forces from the chosen city?
        if ($cube != "") {
            if (!$this->Cities->canSpendInfluence($player_id, $cube)) {
                throw new BgaUserException(sprintf(self::_("You cannot send extra units from %s"), $this->Cities->getNameTr($cube)));
            }
        }

        $unitstrs = explode(" ", trim($units));
        $this->debug("validateMilitaryCommits: player $player_id committing units: ".print_r($unitstrs, true)." with cube $cube");
        // get main attackers/defenders location => player
        $main_attacker = [];
        $main_defender = [];
        $myforces = array(
            'attack' => [],
            'defend' => [],
        );
        // MAKE NO CHANGES IN DB until this loop is completed!
        $permission_requests = [];
        foreach($unitstrs as $unitstr) {
            [$id, $side, $location] = explode("_", $unitstr);
            $counter = $this->getCounterById($id);
            // $counter['location'] = $location;
            $city = $counter['city'];
            // Is this unit in my pool?
            $unit_desc = $this->unitDescription($city, $counter['strength'], $counter['type'], $location);

            $attacker = $this->Battles->getAttacker($location);
            $defender = $this->Battles->getDefender($location);
            if ($attacker != null) {
                $main_attacker[$location] = $attacker;
            }
            if ($defender != null) {
                $main_defender[$location] = $defender;
            }
            if ($side == "attack") {
                $this->validateAttacker($player_id, $counter, $location, $unit_desc);

                // Is there already a main attacker who is not me?
                if ($attacker == null) {
                    // I am now the main attacker
                    $main_attacker[$location] = $player_id;
                }
                $counter['location'] = $location;
                $myforces['attack'][] = $counter;
            } else if ($side == "defend") {
                // check requirement for permission here
                if ($this->validateDefender($player_id, $counter, $location, $unit_desc)) {
                    if (!isset($permission_requests[$city])) {
                        $permission_requests[$city] = [];
                    }
                    $permission_requests[$city][$id] = $location;
                }

                // is there already a main defender?
                if ($defender == null) {
                    // I am now the main defender
                    $main_defender[$location] = $player_id;
                }
                $counter['location'] = $location;
                $myforces['defend'][] = $counter;
            }
        }
        // interrupt and do not commit assignments because we need to ask for permissions first
        if (!empty($permission_requests)) {
            return $permission_requests;
        }

        // all units passed all tests for valid assignment
        // did we spend an influence cube?
        if ($cube != "" && count($units) > 2) {
            $this->Cities->changeInfluence($cube, $player_id, -1);
            self::notifyAllPlayers('spentInfluence', clienttranslate('${player_name} spends a ${city_name} Influence cube to send extra units'), array(
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
                $battle = $f['location'];
                $main = ($attdef == "attack") ? $main_attacker[$battle] : $main_defender[$battle];
                if ($main == $player_id) {
                    // I became main
                    $battlepos = MAIN;
                    if ($attdef == "attack") {
                        $this->Locations->setAttacker($main, $battle);
                        $battlepos += ATTACKER;
                    } else {
                        $this->Locations->setDefender($main, $battle);
                        $battlepos += DEFENDER;
                    }
                } elseif ($this->Cities->isLeader($main, PERSIA) && $this->Cities->isLeader($player_id, PERSIA)) {
                    // Sharing Persian leadership
                    $battlepos = MAIN + ($attdef == "attack" ? ATTACKER : DEFENDER);
                } else {
                    $battlepos = ALLY + ($attdef == "attack" ? ATTACKER : DEFENDER);
                }
                $this->sendToBattle($player_id, $f, $battlepos);
            }
        }
        return [];
    }

    /**
     * Player played their Special Tile. Flip it and mark it used.
     */
    function flipSpecialTile($player_id) {
        $tile = $this->SpecialTiles->getSpecialTile($player_id);
        $tile_name = $this->SpecialTiles->getSpecialTileName($player_id);
        $players = self::loadPlayersBasicInfos();
        self::notifyAllPlayers("playSpecial", clienttranslate('${icon} ${player_name} uses Special tile ${special_tile}'), array(
            'i18n' => ['special_tile'],
            'player_id' => $player_id,
            'player_name' => $players[$player_id]['player_name'],
            'tile' => $tile,
            'icon' => true,
            'special_tile' => $tile_name,
            'preserve' => ['player_id', 'tile']
        ));
        $this->SpecialTiles->markUsed($player_id);
    }

    /**
     * Pack the currently committed forces for the requesting player.
     * @return array associative array 'units' => array of unit strings, 'cube' => cube string (if any)
     */
    function packCommittedForces() {
        $committed = array();
        $units = array();
        for ($i = 1; $i <=4; $i++) {
            $unitstr = $this->globals->get(UNIT_PENDING.$i);
            if ($unitstr != "") {
                $units[] = $unitstr;
            }
        }
        $committed['units'] = $units;
        $cube = $this->globals->get(CUBE_PENDING);
        if ($cube != "") {
            $committed['cube'] = $cube;
        }
        return $committed;
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Player actions
//////////// 

    /**
     * When players change player prefs.
     * @param {pref} preference option
     * @param {value} 1 or 0
     */
    function changePreference($pref, $value) {
        if ($pref == AUTOPASS) {
            $player_id = self::getCurrentPlayerId();
            self::DbQuery("UPDATE player SET special_tile_pass=$value WHERE player_id=$player_id");
            self::notifyPlayer( $player_id, "preferenceChanged", "", array() );
        }
    }

    /**
     * A player clicked pass on a Special Tile Button.
     * Zombie can provide player id.
     * @param {string} player_id (optional)
     */
    function specialTilePass($player_id=null) {
        // validity check
        $this->checkAction('useSpecial');
        $player_id ??= self::getCurrentPlayerId();
        $this->SpecialTiles->checkSpecialTile($player_id);

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
     * Only applies to special BATTLE tiles
     * @param {string} player_id
     * @param {bool} use true if use, false if pass
     * @param {string} side (optional, only for PERSIANFLEET and THESSALANIANALLIES)
     */
    function useSpecialBattleTile($player_id, $use, $side=null) {
        $this->checkAction('useSpecialBattle');
        if ($use) {
            $battle = $this->getGameStateValue(ACTIVE_BATTLE);
            $location = $this->Locations->getBattleTile($battle);
            $round = $this->getGameStateValue(BATTLE_ROUND);
            $type = $this->Locations->getCombat($location, $round);
            $special = $this->SpecialTiles->checkSpecialTile($player_id, null, $type);
            $t = $special['tile'];
    
            $this->flipSpecialTile($player_id);
            switch ($t) {
                case BRASIDAS:
                    $this->setGameStateValue(BRASIDAS, 1);
                    break;
                case PHORMIO:
                    $this->setGameStateValue(PHORMIO, 1);
                    break;
                case THESSALANIANALLIES:
                case PERSIANFLEET:
                    $tokens = array("attacker" => ATTACKER, "defender" => DEFENDER);
                    $this->startingBattleToken($tokens[$side]);
                    break;
                default:
                    throw new BgaVisibleSystemException("Invalid special tile: $t"); // NOI18N
            }
        }
        $this->gamestate->setPlayerNonMultiactive( $player_id, "battle" );
    }

    /**
     * For THESSALANIANALLIES or PERSIANFLEET to give one side a starting token.
     * @param {int} ATTACKER or DEFENDER
     */
    function startingBattleToken($side) {
        $tokens = ($side == ATTACKER) ? ATTACKER_TOKENS : DEFENDER_TOKENS;
        if ($this->getGameStateValue($tokens) > 0) {
            throw new BgaVisibleSystemException("Chosen side already has a Victory Token!"); // NOI18N
        }
        $this->takeToken($side);
    }

    /**
     * Play Perikles Special tile.
     */
    function playPerikles() {
        $this->checkAction('useSpecial');
        $player_id = self::getCurrentPlayerId();
        $this->SpecialTiles->checkSpecialTile($player_id);
        $this->flipSpecialTile($player_id);
        $this->addInfluenceToCity('athens', $player_id, 2);
        $state = $this->getStateName();
        $nextState = ($state == "specialTile") ? "nextPlayer" : "continueTurn";
        $this->gamestate->nextState($nextState);
    }

    /**
     * Play the Alkibiades Special tile
     */
    function playAlkibiades($owner1, $from_city1, $to_city1, $owner2, $from_city2, $to_city2) {
        $this->checkAction('useSpecial');
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

        $state = $this->getStateName();
        $nextState = ($state == "specialTile") ? "nextPlayer" : "continueTurn";
        $this->gamestate->nextState($nextState);
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
        $this->checkAction('useSpecial');
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
        $state = $this->getStateName();
        $nextState = ($state == "specialTile") ? "nextPlayer" : "continueTurn";
        $this->gamestate->nextState($nextState);
    }

    /**
     * Player selected Slave Revolt
     * @param {string} revoltlocation "sparta" or a tile location
     */
    function playSlaveRevolt($revoltlocation, $zombiePlayerId = null) {
        if ($zombiePlayerId !== null) {
            $this->checkAction('useSpecial');
        }
        // sanity check - there is a Sparta leader
        $sparta_leader = $this->Cities->getLeader("sparta");
        if (empty($sparta_leader)) {
            throw new BgaVisibleSystemException("No Sparta Leader!"); // NOI18N
        }

        // current not active because can be used outside current turn
        $player_id = $zombiePlayerId ?? $this->getCurrentPlayerId();
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
        $this->Battles->toLocation($id, "sparta");

        // this will flip the counter, and move it to Sparta
        self::notifyAllPlayers("slaveRevolt", clienttranslate('${icon} Hoplite-${strength} counter returned to Sparta from ${location_name}'), array(
            'i18n' => ['location_name'],
            'military' => $counter,
            'strength' => $counter['strength'],
            'icon' =>true,
            'return_from' => $revoltlocation, // may be sparta or a battle name, don't use location because of format-string recursive
            'location_name' => $location_name,
            'sparta_player' => $sparta_leader,
            'preserve' => ['return_from'],
        ));


        $state = $this->getStateName();
        $nextState = ($state == "specialTile") ? "nextPlayer" : "continueCommit";
        $this->gamestate->nextState($nextState);
    }

    /**
     * Spartan player chose first player for influence phase.
     */
    function chooseNextPlayer($first_player, $zombiePlayerId = null) {
        if ($zombiePlayerId !== null) {
            $this->checkAction('chooseNextPlayer');
        }
        $players = self::loadPlayersBasicInfos();

        $player_id = $zombiePlayerId ?? self::getActivePlayerId();
        self::notifyAllPlayers("spartanChoice", clienttranslate('${player_name} chooses ${candidate_name} to commit forces first'), array(
            'player_id' => $player_id,
            'player_name' => $players[$player_id]['player_name'],
            'candidate_id' => $first_player,
            'candidate_name' => $players[$first_player]['player_name'],
            'preserve' => ['player_id', 'candidate_id'],
        ));
        $this->setGameStateValue(FIRST_PLAYER_BATTLE, $first_player);
        $this->gamestate->nextState("");
    }

    /**
     * Player chose an Influence tile
     */
    function takeInfluence($influence_id, $zombiePlayerId = null) {
        if ($zombiePlayerId !== null) {
            $this->checkAction( 'takeInfluence' );
        }
        $influence_card = self::getObjectFromDB("SELECT card_id id, card_type city, card_type_arg type, card_location location, card_location_arg slot FROM INFLUENCE WHERE card_id=$influence_id");

        // is it on the board?
        if ($influence_card['location'] != BOARD) {
            throw new BgaUserException(self::_("This card is not selectable"));
        }
        $player_id = $zombiePlayerId ?? self::getActivePlayerId();
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

        $msg = clienttranslate('${player_name} takes ${shards}-Shard ${city_name} tile');
        $i18n = ['city_name'];
        $inf_type = $descriptors[2];
        if (!empty($inf_type)) {
            $i18n[] = 'inf_type';
            $msg = clienttranslate('${player_name} takes ${shards}-Shard ${city_name} tile (${inf_type})');
        }

        $slot = $influence_card['slot'];
        $this->setGameStateValue(LAST_INFLUENCE, $slot);

        self::notifyAllPlayers("influenceCardTaken", $msg, array(
            'i18n' => $i18n,
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
    function placeAnyCube($city, $zombiePlayerId = null) {
        if ($zombiePlayerId !== null) {
            $this->checkAction( 'placeAnyCube' );
        }
        $player_id = $zombiePlayerId ?? self::getActivePlayerId();
        $this->addInfluenceToCity($city, $player_id, 1);
        $state = "nextPlayer";
        if ($this->getGameStateValue(INFLUENCE_PHASE) == 0) {
            $state = "nextPlayerInitial";
        } elseif ($this->SpecialTiles->canPlaySpecial($player_id, INFLUENCE_PHASE)) {
            if (!$this->isAutopass($player_id)) {
                $state = "useSpecial";
            }
        }
        $this->gamestate->nextState($state);
    }

    /**
     * Player is selecting a candidate for a city.
     */
    function proposeCandidate($city, $candidate_id, $zombiePlayerId = null) {
        if ($zombiePlayerId !== null) {
            $this->checkAction('proposeCandidate');
        }
        $actingplayer = $zombiePlayerId ?? self::getActivePlayerId();
        $city_name = $this->Cities->getNameTr($city);
        // player must have a cube in the city
        if (!$this->Cities->hasInfluence($actingplayer, $city)) {
            throw new BgaUserException(sprintf(self::_("You cannot propose a Candidate in %s: you have no Influence cubes in that city"), $city_name));
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
        $canusespecial = ($this->getGameStateValue(INFLUENCE_PHASE) == 1) && $this->SpecialTiles->canPlaySpecial($actingplayer, INFLUENCE_PHASE) && !$this->isAutopass($actingplayer);
        $state = $canusespecial ? "useSpecial" : "nextPlayer";

        $this->gamestate->nextState($state);
    }

    /**
     * Player chose a cube to remove.
     * $cube is a, b, or a number
     */
    function chooseRemoveCube($target_id, $city, $cube, $zombiePlayerId = null) {
        if ($zombiePlayerId !== null) {
            $this->checkAction('chooseRemoveCube');
        }
        $player_id = $zombiePlayerId ?? self::getActivePlayerId();
        $players = self::loadPlayersBasicInfos();
        $city_name = $this->Cities->getNameTr($city);
        if ($cube == "a") {
            $alpha = $this->Cities->getCandidate($city, "a");
            if ($alpha != $target_id) {
                throw new BgaVisibleSystemException("Missing cube at $city $cube"); // NO18N
            }
            $this->Cities->clearCandidate($city, "a");
            self::notifyAllPlayers("cubeRemoved", clienttranslate('${player_name} removes ${candidate_name}\'s Candidate ${candidate} in ${city_name}'), array(
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
            self::notifyAllPlayers("cubeRemoved", clienttranslate('${player_name} removes ${candidate_name}\'s Candidate ${candidate} in ${city_name}'), array(
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
            self::notifyAllPlayers("cubeRemoved", clienttranslate('${player_name} removes one of ${candidate_name}\'s Influence cubes in ${city_name}'), array(
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
        $usespecial = $this->SpecialTiles->canPlaySpecial($player_id, INFLUENCE_PHASE) && !$this->isAutopass($player_id);
        $state = $usespecial ? "useSpecial" : "nextPlayer";

        $this->gamestate->nextState($state);
    }

    /**
     * Send units to battle locations.
     * @param string units a space-delimited string of unitstrs id_attdef_battle (or empty)
     * @param string cube empty string or cube spent for extra units
     * @param string zombiePlayerId if zombie
     */
    function assignUnits($units, $cube, $zombiePlayerId = null) {
        if ($zombiePlayerId !== null) {
            $this->checkAction('assignUnits');
        }
        $player_id = $zombiePlayerId ?? self::getActivePlayerId();

        $city_requests = [];
        if (trim($units) == "") {
            $this->noCommitUnits($player_id);
        } else {
            $city_requests = $this->validateMilitaryCommits($player_id, $units, $cube);
        }
        $state = "nextPlayer";
        if (!empty($city_requests)) {
            $state = "requestPermission";
            // Store who is requesting permission so we can return to them
            $this->setGameStateValue(REQUESTING_PLAYER, $player_id);

            $i = 1;
            foreach($city_requests as $city => $assignments) {
                foreach ($assignments as $id => $location) {
                    $this->requestPermissionToDefend($player_id, $i, $id, $city, $location);
                    $i++;
                }
            }
        } elseif ($this->SpecialTiles->canPlaySpecial($player_id, COMMIT_PHASE) && !$this->isAutopass($player_id)) {
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
                    $this->sendToDeadpool($counter);
                    $deadpool = true;
                    break;
                }
            }
            if (!$deadpool) {
                throw new BgaVisibleSystemException("no valid counter found for $city"); // NOI18N
            }
        }
        $this->gamestate->nextState("");
    }

    /**
     * Player chose a unit from the deadpool.
     * Sets gamestate to nextPlayer if we're done, or nextPick if this player has multiple choices to make.
     * @param string city
     * @param string type HOPLITE|TRIREME
     */
    function chooseDeadpool($city, $type) {
        $this->checkAction('chooseDeadUnits');
        $player_id = self::getActivePlayerId();
        // error check
        $types = $this->Deadpool->getTypesInDeadpool($city);
        if (count($types) == 2) {
            $this->retrieveFromDeadpool($player_id, $city, $type);
        } else {
            // should not happen
            throw new BgaUserException(sprintf(self::_("You cannot choose deadpool units from %s"), $this->Cities->getNameTr($city)));
        }
        // does this player have more choices to make?
        $state = empty($this->getDeadpoolCities($player_id)) ? "nextPlayer" : "nextPick";

        $this->gamestate->nextState($state);
    }

    /**
     * Player requests permission to send units to defend someone else's city.
     * This is called internally by assignUnits, not directly by players.
     * @param int player_id the id of the player requesting permission
     * @param int index the index of the request
     * @param string id the unit id being requested
     * @param string city the city for which permission is being requested
     * @param string battle the battle location for which permission is being requested
     */
    function requestPermissionToDefend($requesting_player_id, $i, $id, $city, $battle) { 
        $players = self::loadPlayersBasicInfos();
        $player_name = $players[$requesting_player_id]['player_name'];

        // get the owner of the city
        $owning_city = $this->Locations->getCity($battle);

        $location_id = $this->Locations->getLocationId($battle);
        self::setGameStateValue(REQUESTED_LOCATION."$i", $location_id);
        $city_id = $this->Cities->getCityId($city);
        self::setGameStateValue(REQUESTING_CITY."$i", $city_id);
        // make sure it's a legal request - check conflicting units
        
        // $unitstr = $this->globals->get(UNIT_PENDING.$i);
        // if (empty($unitstr)) {
        //     throw new BgaVisibleSystemException("No pending unit found for request $i"); // NOI18N
        // }
        // [$id, $_, $battle] = explode("_", $unitstr);
        // if (empty($id)) {
        //     throw new BgaVisibleSystemException("Invalid unit ID in pending unit $unitstr"); // NOI18N
        // }
        $counter = $this->getCounterById($id);
        $unitdesc = $this->unitDescription($counter['city'], $counter['strength'], $counter['type'], $battle);
        // Check whether this request is legal
        // validate should return True as permission is required
        // throw an exception if it conflicts with another assignment
        if (!$this->validateDefender($requesting_player_id, $counter, $battle, $unitdesc)) {
            throw new BgaVisibleSystemException("Invalid defender request for $unitdesc"); // NOI18N
        }
        $this->globals->set(UNIT_PENDING."$i", $id."_defend_".$battle);

        self::notifyAllPlayers("defendRequest", clienttranslate('${city_name} requests permission from ${city_name2} to defend ${battle_location}'), array(
            'i18n' => ['city_name', 'city_name2', 'battle_location'],
            'player_id' => $requesting_player_id,
            'player_name' => $player_name,
            'city' => $city,
            'city_name' => $this->Cities->getNameTr($city),
            'city2' => $owning_city,
            'city_name2' => $this->Cities->getNameTr($owning_city),
            'battle' => $battle,
            'battle_location' => $this->Locations->getName($battle),
            'preserve' => ['player_id', 'city', 'city2', 'battle']
        ));
    }

    /**
     * Requesting player canceled the request.
     */
    function cancelRequestToDefend() {
        $this->checkAction("cancelRequestToDefend"); 
        $players = self::loadPlayersBasicInfos();

        $requesting_player = $this->getGameStateValue(REQUESTING_PLAYER);

        if ($requesting_player != $this->getCurrentPlayerId()) {
            throw new BgaUserException(self::_("You have no requests to cancel!"));
        }

        self::notifyAllPlayers('requestCanceled', clienttranslate('${player_name} canceled request(s)'), array(
            'player_name' => $players[$requesting_player]['player_name'],
            'player_id' => $this->getCurrentPlayerId(),
            'preserve' => ['player_id']
        ));
        $this->clearPermissionRequests();
        // Return to commit forces state for this player
        $this->gamestate->changeActivePlayer($requesting_player);
        $this->gamestate->nextState("canceled");
    }

    /**
     * A single player responds to one or more requests to defend city by another player. Grants or allows all.
     * @param array requesting_cities the cities that are requesting permission to defend
     * @param array locations the battle locations for which permission is being requested
     * @param bool bAllow whether the player is granting permission or not
     */
    function respondPermissionToDefend($requesting_cities, $locations, $bAllow) {
        $this->checkAction("respondPermissionToDefend"); 

        if (count($requesting_cities) != count($locations)) {
            throw new BgaVisibleSystemException("number of cities and locations do not match"); // NOI18N
        }

        $requesting_player = $this->getGameStateValue(REQUESTING_PLAYER);
        $owning_player = null;
        $request_cities = [];
        $request_locations = [];

        // check all requests which should only be those for MY cities
        for ($i = 0; $i < count($requesting_cities); $i++) {
            $city = $requesting_cities[$i];
            $location = $locations[$i];
            $owner = $this->Locations->getCity($location);
            $owning_player = $this->Cities->getLeader($owner);
            if ($this->getCurrentPlayerId() != $owning_player) {
                throw new BgaUserException(self::_("You cannot respond to this request"));
            }
            if ($requesting_player != $this->Cities->getLeader($city)) {
                throw new BgaVisibleSystemException("Multiple city owners found in request response"); // NOI18N
            }

            // Clear this specific request from the tracking variables
            $location_id = $this->Locations->getLocationId($location);
            $city_id = $this->Cities->getCityId($city);
            for ($r = 1; $r <= 4; $r++) {
                $loc = $this->getGameStateValue(REQUESTED_LOCATION.$r);
                $cty = $this->getGameStateValue(REQUESTING_CITY.$r);
                if ($loc == $location_id && $cty == $city_id) {
                    self::setGameStateValue(REQUESTED_LOCATION.$r, 0);
                    self::setGameStateValue(REQUESTING_CITY.$r, 0);
                    // update permission request status if granted
                    if ($bAllow) {
                        self::setGameStateValue(REQUEST_STATUS.$r, 1);
                    }
                    break;
                }
            }
            $this->setDefendPermission($location, $city, $bAllow);
        }

        // deactivate this player
        $this->gamestate->setPlayerNonMultiactive( $owning_player, "resolveRequests");
        // are there any requests waiting from other players?
        $requests_remaining = 0;
        for ($j = 1; $j <= 4 && !$requests_remaining; $j++) {
            $locr = $this->getGameStateValue(REQUESTED_LOCATION.$j);
            $ctyr = $this->getGameStateValue(REQUESTING_CITY.$j);
            $this->debug("request $j location=$locr city=$ctyr");
            if ($locr != 0 && $ctyr != 0) {
                $requests_remaining++;
            }
        }
        $this->debug("respondPermissionToDefend: requests_remaining=".($requests_remaining));

        if ($requests_remaining == 0) {
            // All requests resolved - setAllPlayersNonMultiactive will mark everyone (including requester) as non-active
            $active_players = $this->gamestate->getActivePlayerList();
            $this->debug("transitioning to 'resolveRequests' with active players: ".implode(", ", $active_players));
            $this->gamestate->setAllPlayersNonMultiactive( "resolveRequests" );
        }
    }

    // /**
    //  * After any permission response, check if this player has any remaining requests to answer. If not, deactivate him.
    //  * @param {int} owning_player the player who owns the city being defended
    //  * @param {int} city_id the city that is requesting permission to defend
    //  * @param {int} location_id the battle location for which permission is being requested
    //  */
    // private function updatePermissionsStates($owning_player, $requesting_city, $location) {
    //     $owning_player_still_active = false;
    //     $active_requests = false;
    //     $location_id = $this->Locations->getLocationId($location);
    //     $city_id = $this->Cities->getCityId($requesting_city);
    //     for ($i = 1; $i <= 4; $i++) {
    //         $location = $this->getGameStateValue(REQUESTED_LOCATION."$i");
    //         $city = $this->getGameStateValue(REQUESTING_CITY."$i");
    //         if ($location == $location_id && $city == $city_id) {
    //             // found it, clear it
    //             self::setGameStateValue(REQUESTED_LOCATION."$i", 0);
    //             self::setGameStateValue(REQUESTING_CITY."$i", 0);
    //         } else {
    //             //  are there any remaining requests?
    //             // does the owning player have any remaining requests pending? If not, deactivate
    //             if  ($city != 0 && $location != 0) {
    //                 $active_requests = true;
    //                 $nextlocation = $this->Locations->getLocationById($location);
    //                 $owningcity = $this->Locations->getCity($nextlocation);
    //                 $nextplayer = $this->Cities->getLeader($owningcity);
    //                 if ($nextplayer == $owning_player) {
    //                     $owning_player_still_active = true;
    //                 }
    //             }
    //         }
    //     }
    //     // does the owning player still have pending requests?
    //     if (!$owning_player_still_active) {
    //         $this->gamestate->setPlayerNonMultiactive( $owning_player, "resolveRequests");
    //     }
    //     // are there any remaining requests pending?
    //     if (!$active_requests) {
    //         // no more active requests, move on
    //         $this->gamestate->setAllPlayersNonMultiactive( "resolveRequests" );
    //     }
    // }

    //////////////////////////////////////////////////////////////////
    /// BATTLE FUNCTIONS
    //////////////////////////////////////////////////////////////////


    /**
     * Checks whether a unit can attack a city, throws an Exception if it fails.
     * Also marks unit as Allies with all attackers and At War with all Defenders.
     * @param player_id the player trying to attack
     * @param counter the counter being sent to attack
     * @param location the battle location being attacked
     * @param unit_desc the description of the unit being sent to attack, for error messages
     */
    private function validateAttacker($player_id, $counter, $location, $unit_desc) {
        if ($counter['location'] != $player_id) {
            // is this a Persian?
            if (!($counter['location'] == CONTROLLED_PERSIANS && $this->Cities->isLeader($player_id, PERSIA))) {
                throw new BgaUserException(sprintf(self::_("%s is not in your available pool"), $unit_desc));
            }
        }
        $city = $this->Locations->getCity($location);

        // does this location belong to my own city?
        if ($this->Cities->isLeader($player_id, $city)) {
            throw new BgaUserException(sprintf(self::_("%s cannot attack a location owned by a city you control!"), $unit_desc));
        }
        // is this unit allied with the defender (including because a unit was already played as a defender)?
        if ($this->Cities->isAlly($counter['city'], $city)) {
            throw new BgaUserException(sprintf(self::_("%s cannot attack a location owned by an allied city!"), $unit_desc));
        }
        // is it allied with any of the other defenders? Do I own any of the other defenders?
        $defenders = $this->Cities->getAllDefenders($location, $city);
        foreach($defenders as $def) {
            if ($this->Cities->isAlly($counter['city'], $def)) {
                throw new BgaUserException(sprintf(self::_("%s cannot attack a location being defended by an allied city!"), $unit_desc));
            }
            if ($this->Cities->isLeader($player_id, $def)) {
                throw new BgaUserException(sprintf(self::_("%s cannot attack a location being defended by units you control!"), $unit_desc));
            }
        }

        // is counter at war with any of the other attackers?
        $attackers = $this->Cities->getAllAttackers($location);
        foreach($attackers as $att) {
            if ($this->Cities->atWar($counter['city'], $att)) {
                throw new BgaUserException(sprintf(self::_("%s cannot join battle with a city it is at war with!"), $unit_desc));
            }
        }

        // are we sending a trireme to a land battle?
        if ($this->Locations->isLandBattle($location) && $counter['type'] == TRIREME) {
            throw new BgaUserException(sprintf(self::_("%s cannot be sent to a Hoplites-only battle"), $unit_desc));
        }
        // passed all checks. Declare war with all defenders.
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
     * If permission is required, returns true.
     * If permission has already been set, set unit to Ally with fellow defenders and At War with attackers and return false.
     * @param player_id the player trying to defend
     * @param counter the counter being sent to defend
     * @param location the battle location being defended
     * @param unit_desc the description of the unit being sent to defend, for error messages
     * @return bool true if permission is required, false if no permission is required
     */
    private function validateDefender($player_id, $counter, $location, $unit_desc) {
        if ($counter['location'] != $player_id) {
            // is this a Persian?
            if (!($counter['location'] == CONTROLLED_PERSIANS && $this->Cities->isLeader($player_id, PERSIA))) {
                throw new BgaUserException(sprintf(self::_("%s is not in your available pool"), $unit_desc));
            }
        }

        // am I at war with any of the defenders?
        $city = $this->Locations->getCity($location);
        $defenders = $this->Cities->getAllDefenders($location, $city);
        foreach($defenders as $def) {
            if ($this->Cities->atWar($counter['city'], $def)) {
                throw new BgaUserException(sprintf(self::_("%s cannot join battle with a city it is at war with!"), $unit_desc));
            }
        }
        // am I allied with any of the attackers?
        $attackers = $this->Cities->getAllAttackers($location);
        foreach($attackers as $att) {
            if ($this->Cities->isAlly($counter['city'], $att)) {
                throw new BgaUserException(sprintf(self::_("%s cannot defend a location being attacked by an allied city!"), $unit_desc));
            }
            if ($this->Cities->isLeader($player_id, $att)) {
                throw new BgaUserException(sprintf(self::_("%s cannot defend a location being attacked by units you control!"), $unit_desc));
            }
        }

        $city = $this->Locations->getCity($location);
        // Do I control this city? If not, I need permission from defender
        $leader = $this->Cities->getLeader($city);
        if ($leader != $player_id) {
            if (!$this->Locations->hasDefendPermission($counter['city'], $location)) {
                // return true to indicate that permission is required
                return true;
            }
        }

        // are we sending a trireme to a land battle?
        if ($this->Locations->isLandBattle($location) && $counter['type'] == TRIREME) {
            throw new BgaUserException(sprintf(self::_("%s cannot be sent to a land battle"), $unit_desc));
        }

        // passed all checks. Declare war with all attackers
        foreach($attackers as $att) {
            $this->Cities->setWar($counter['city'], $att);
        }
        // and declare allies with all attackers
        foreach($defenders as $def) {
            $this->Cities->setAlly($counter['city'], $def);
        }
        return false;
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

        $winner = $noattacker ? $defender : $attacker;

        // am I the defender?
        if ($winner == $defender) {
            // there was a defender with no attacker: don't win the tile, but get two cubes
            self::notifyAllPlayers('unclaimedTile', clienttranslate('Defender wins uncontested battle at ${location_name}; no one claims the tile'), array(
                'i18n' => ['location_name'],
                'location' => $location,
                'location_name' => $this->Locations->getName($location),
                'preserve' => ['location']
            ));
            // have to check special case where Persians jointly defend an uncontested city
            $persians = $this->Cities->getPersianLeaders();
            if (in_array($winner, $persians)) {
                foreach($persians as $persian) {
                    $this->addInfluenceToCity($city, $persian, 2);
                }
            } else {
                $this->addInfluenceToCity($city, $winner, 2);
            }
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
                $this->claimTile($winner, $tile, ATTACKER);
            }
        }
    }

    /**
     * Resolve a single round - Hoplite or Trireme battle. Roll until one side wins.
     * @param {string} location
     * @param {int} slot
     * @param {string} type HOPLITE or TRIREME
     * @param {int} attstr
     * @param {int} defstr
     * @return {int} ATTACKER or DEFENDER
     */
    function rollBattle($location, $slot, $type, $attstr, $defstr) {
        $crt = $this->Battles->getCRTColumn($attstr, $defstr);
        // highlight CRT Column
        $unit = $this->getUnitName($type);
        self::notifyAllPlayers('rollBattle', clienttranslate('${unit_type} battle at ${location_name}: attacker strength ${att} vs. defender strength ${def}, rolling in the ${odds} column'), array(
            'i18n' => ['unit_type', 'location_name'],
            'type' => $type,
            'slot' => $slot,
            'unit_type' => $unit,
            'location' => $location,
            'location_name' => $this->Locations->getName($location),
            'att' => $attstr,
            'def' => $defstr,
            'crt' => $crt,
            'odds' => $this->Battles->getOdds($crt),
            'preserve' => ['type', 'slot', 'location', 'crt']
        ));
        $winner = null;
        while ($this->getGameStateValue(ATTACKER_TOKENS) < 2 && $this->getGameStateValue(DEFENDER_TOKENS) < 2) {
            $this->rollDice($crt);
        }
        // one side has two tokens, but do they both?
        if ($this->getGameStateValue(ATTACKER_TOKENS) == 2 && $this->getGameStateValue(DEFENDER_TOKENS) == 2) {
            // they need to roll off until one side hits and the other doesn't
            $winner = $this->rollDice($crt);
            // important! === because WINNER = 0 which == null!
            while ($winner === null) {
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
        $atthit = (($attd1 + $attd2) >= $attacker_tn) ? True : False;
        // roll for defender
        $defd1 = bga_rand(1,6);
        $defd2 = bga_rand(1,6);
        $defhit = (($defd1 + $defd2) >= $defender_tn) ? True : False;
        self::notifyAllPlayers("diceRoll", clienttranslate('Attacker rolls ${attd1} ${attd2} [${atttotal} vs. ${atttarget}]: ${atthit}! ${crtroll} Defender rolls ${defd1} ${defd2} [${deftotal} vs. ${deftarget}]: ${defhit}!'), array(
            'crtroll' => true,
            'attd1' => $attd1,
            'attd2' => $attd2,
            'defd1' => $defd1,
            'defd2' => $defd2,
            'atthit' => $atthit,
            // have to insert these as seperate values because client side reformats args above to html
            'attacker_1' => $attd1,
            'attacker_2' => $attd2,
            'defender_1' => $defd1,
            'defender_2' => $defd2,
            'attacker_result' => $atthit,
            'defender_result' => $defhit,
            'atttarget' => $attacker_tn,
            'deftarget' => $defender_tn,
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
        if ($winner === ATTACKER) {
            $side = "attacker";
            $token = ATTACKER_TOKENS;
            $role = clienttranslate('Attacker');
        } elseif ($winner === DEFENDER) {
            $side = "defender";
            $token = DEFENDER_TOKENS;
            $role = clienttranslate('Defender');
        } else {
            throw new BgaVisibleSystemException("Invalid side to take Token: $winner"); // NOI18N
        }
        if ($bIsRound2) {
            self::notifyAllPlayers("round2", clienttranslate('${icon} ${winner} starts second round with Battle Token'), array(
                'i18n' => ['winner'],
                'winner' => $role,
                'icon' => true,
                'token' => true,
                'side' => $side,
                'preserve' => ['token']
            ));
        } else {
            self::notifyAllPlayers("takeToken", clienttranslate('${icon} ${winner} gains a Battle Token'), array(
                'i18n' => ['winner'],
                'side' => $side,
                'winner' => $role,
                'icon' => true,
                'token' => true,
                'preserve' => ['token']
            ));
        }
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
                    if (!$this->isAutopass($pid)) {
                        $specialtileplayers[] = $pid;
                    }
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
        $loser = $this->getGameStateValue(LOSER);
        if ($loser == DEFENDER) {
            $winner = $attacker;
            self::notifyAllPlayers("attackerWins", clienttranslate('${icon} Attacker defeats ${city_name} at ${location_name}'), array(
                'i18n' => ['city_name'],
                'city' => $city,
                'city_name' => $city_name,
                'icon' => true,
                'location' => $location,
                'location_name' => $location_name,
                'preserve' => ['city', 'location'],
            ));
            if ($attacker != null) {
                self::incStat(1, "battles_won_attacker", $attacker);
            }
            if ($defender != null) {
                self::incStat(1, "battles_lost_defender", $defender);
            }
        } elseif ($loser == ATTACKER) {
            $winner = $defender;
            self::notifyAllPlayers("defenderWins", clienttranslate('${icon} Defender (${city_name}) defeats attackers at ${location_name}'), array(
                'i18n' => ['city_name'],
                'city' => $city,
                'city_name' => $city_name,
                'icon' => true,
                'location' => $location,
                'location_name' => $location_name,
                'preserve' => ['city', 'location'],
            ));
            if ($attacker != null) {
                self::incStat(1, "battles_lost_attacker", $attacker);
            }
            if ($defender != null) {
                self::incStat(1, "battles_won_defender", $defender);
            }
        } else {
            throw new BgaVisibleSystemException("No winner found at end of battle for tile $location"); // NOI18N
        }
        if ($winner == null) {
            // in the unusual case of defending militia beating an attacker
            self::notifyAllPlayers('unclaimedTile', clienttranslate('Winner didn\'t send any units; no one claims the tile'), array(
                'i18n' => ['location_name'],
                'location' => $location,
                'location_name' => $location_name,
            ));
            $this->unclaimedTile($tile);
        } else {
            $this->claimTile($winner, $tile, ($loser == ATTACKER ? DEFENDER : ATTACKER));
        }
    }

    /**
     * Move a counter to the Deadpool.
     * Send notification.
     * @param {Object} counter to lose; may be null
     */
    function sendToDeadpool($counter) {
        if ($counter != null) {
            $id = $counter['id'];
            $unit_desc = $this->unitDescription($counter['city'], $counter['strength'], $counter['type'], $counter['location']);
            $this->Deadpool->toDeadpool($counter);
            self::notifyAllPlayers('toDeadpool', clienttranslate('${icon} Losing side takes one casualty: ${unit} is sent to Dead Pool'), array(
                'i18n' => ['unit'],
                'id' => $id,
                'unit' => $unit_desc,
                'city' => $counter['city'],
                'strength' => $counter['strength'],
                'type' => $counter['type'],
                'location' => $counter['location'],
                'icon' => true,
                'casualty_log' => True,
                'preserve' => ['casualty_log', 'type', 'city', 'strength'],
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
        $battle = $this->getGameStateValue(ACTIVE_BATTLE);
        $location = $this->Locations->getBattleTile($battle);
        $loser = $this->getGameStateValue(LOSER);
        $round = $this->getGameStateValue(BATTLE_ROUND);
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
        $this->setGameStateValue(COMMIT_PHASE, 0);
        $this->setGameStateValue(ACTIVE_BATTLE, 0);
        $this->setGameStateValue(BATTLE_ROUND, 0);
        $this->setGameStateValue(LOSER, -1);
        $this->setGameStateValue(BRASIDAS, 0);
        $this->setGameStateValue(PHORMIO, 0);
        self::notifyAllPlayers("resetBattleTokens", '', []);
    }

    /**
     * Set up for second round of a battle.
     * @param {int} ATTACKER or DEFENDER (-1 for case of empty first battle)
     */
    function secondRoundReset($firstroundloser) {
        $this->setGameStateValue(ATTACKER_TOKENS, 0);
        $this->setGameStateValue(DEFENDER_TOKENS, 0);
        if ($firstroundloser == -1) {
            // there was no battle in the first round
            self::notifyAllPlayers("resetBattleTokens", '', array(
                'winner' => null,
            ));
        } else {
            $winner = ($firstroundloser == ATTACKER) ? DEFENDER : ATTACKER;
            $this->takeToken($winner, true);
            self::notifyAllPlayers("resetBattleTokens", '', array(
                'winner' => ($winner == ATTACKER) ? "attacker" : "defender",
            ));
        }
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

    /**
     * Clear all permission request settings.
     */
    function clearPermissionRequests() {
        for ($i = 1; $i <= 4; $i++) {
            self::setGameStateValue(REQUESTED_LOCATION."$i", 0);
            self::setGameStateValue(REQUESTING_CITY."$i", 0);
            self::setGameStateValue(REQUEST_STATUS.$i, 0);
            $this->globals->set(UNIT_PENDING.$i, "");
        }
        self::setGameStateValue(REQUESTING_PLAYER, 0);
        $this->globals->set(CUBE_PENDING, "");
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
     * During initial influence cube choice, are we choosing first or second cube?
     */
    function argsInitial() {
        $players = self::loadPlayersBasicInfos();
        $nbr = count($players);
        $init_count = $this->getGameStateValue("initial_influence");
        $first = clienttranslate("first");
        $second = clienttranslate("second");

        $countinf = ($init_count < $nbr) ? $first : $second;

        return array(
            'i18n' => ['count'],
            'count' => $countinf,   
        );
    }

    /**
     * Can the current active player play a special card during this Influence phase.
     */
    function argsSpecial() {
        $players = self::loadPlayersBasicInfos();
        $private = array();
        $phase = $this->checkPhase();
        $requesting_player = $this->getGameStateValue(REQUESTING_PLAYER);
        foreach (array_keys($players) as $player_id) {
            $private[$player_id] = array('special' => $this->SpecialTiles->canPlaySpecial($player_id, $phase));
            if ($requesting_player == $player_id) {
                $this->debug("$requesting_player is requesting player for defense permissions");
                $private[$player_id][] = array('committed' => $this->packCommittedForces());
            }
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
        $round = $this->getGameStateValue(BATTLE_ROUND);
        $combat = $this->Locations->getCombat($location, $round);
        $players = $this->specialBattleTilePlayers($combat);
        $private = array();
        foreach ($players as $player_id) {
            $private[$player_id] = array('special' => true, 'location' => $location);
        }

        return array(
            '_private' => $private,
            'i18n' => ['battle_location'],
            'battle_location' => $this->Locations->getName($location),
        );
    }

    /**
     * Present player with choice of cities to take casualties from
     */
    function argsLoss() {
        $battle = $this->getGameStateValue(ACTIVE_BATTLE);
        $location = $this->Locations->getBattleTile($battle);
        $round = $this->getGameStateValue(BATTLE_ROUND);
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
     * Return all the units that may be retrieved from Deadpool by the current active player.
     */
    function argsDeadPool() {
        $player_id = self::getActivePlayerId();
        $cities = $this->getDeadpoolCities($player_id);
        $deadpool = $this->Deadpool->getDeadpoolChoices($cities);
        return array(
            'deadpool' => $deadpool,
        );
    }

    /**
     * Provide relationship status of cities in war phase.
     */
    function argsWarPhase() {
        return array(
            'wars' => $this->Cities->getCityRelationships()
        );
    }

    /**
     * Provide args for requests to defend a location. Goes to all owning players.
     * @return array of permission requests ('permission_requests') =>  array of ()'location', 'owning_city', 'owner', 'requesting_city')
     */
    function argsPermissionResponse() {
        $permission_requests = [];
        $requesting_player = $this->getGameStateValue(REQUESTING_PLAYER);
        if ($requesting_player == 0) {
            throw new BgaVisibleSystemException("No requesting player set for permission response"); // NOI18N
        }
        for ($i = 1; $i <= 4; $i++) {
            $request = $this->getGameStateValue(REQUESTED_LOCATION."$i");
            $requester = $this->getGameStateValue(REQUESTING_CITY."$i");
            if ($request != 0 && $requester != 0) {
                $requesting_city = $this->Cities->getCityById($requester);
                if ($requesting_player != $this->Cities->getLeader($requesting_city)) {
                    throw new BgaVisibleSystemException("Multiple permission requests from different players"); // NOI18N
                }
                $location = $this->Locations->getLocationById($request);
                $owning_city = $this->Locations->getCity($location);
                $owner = $this->Cities->getLeader($owning_city);
                $permission_request = array(
                    'location' => $location,
                    'owning_city' => $owning_city,
                    'owner' => $owner,
                    'requesting_city' => $requesting_city,
                );
                $permission_requests[] = $permission_request;
            }
        }

        $players = self::loadPlayersBasicInfos();
        return array(
            'permission_requests' => $permission_requests,
            'otherplayer' => $players[$requesting_player]['player_name'],
            'otherplayer_id' => $requesting_player,
        );
    }

    /**
     * Get the phase to check against for use of a Special tile.
     * @return "influence, commit, or
     */
    function checkPhase() {
        $state = $this->getStateName();
        if ($state == "takeInfluence") {
            return INFLUENCE_PHASE;
        } elseif ($state == "commitForces") {
            return COMMIT_PHASE;
        } elseif ($state == "specialTile") {
            // this may be 0, 1, or 2 (2 = candidate phase, no special tiles)
            if ($this->getGameStateValue(INFLUENCE_PHASE) == 1) {
                return INFLUENCE_PHASE;
            } elseif ($this->getGameStateValue(COMMIT_PHASE) == 1) {
                return COMMIT_PHASE;
            }
        }
        return null;
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Game state actions
////////////

    /**
     * Each player adds 2 influence cubes at start of game.
     */
    function stInitialInfluence() {
        $state = "nextPlayer";
        $this->incGameStateValue("initial_influence", 1);
        $init_count = $this->getGameStateValue("initial_influence");
        $players = self::loadPlayersBasicInfos();
        $nbr = count($players);

        if ($init_count == ($nbr*2)) {
            $this->setGameStateValue(INFLUENCE_PHASE, 1);
            $state = "startGame";
        }

        $player_id = self::activeNextPlayer();
        self::giveExtraTime( $player_id );

        $this->gamestate->nextState($state);
    }

    /**
     * Handles next player action through Influence phase.
     */
    function stNextPlayer() {
        $state = "";
        if ($this->getGameStateValue(INFLUENCE_PHASE) > 0) {
            if ($this->allInfluenceTilesTaken()) {
                // no longer taking influence, enter candidates phase
                $this->setGameStateValue(INFLUENCE_PHASE, 2);

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
        } elseif ($this->getGameStateValue(COMMIT_PHASE) == 1) {
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
        $player_id = $this->getGameStateValue(FIRST_PLAYER_BATTLE);
        if ($player_id != 0) {
            $this->gamestate->changeActivePlayer($player_id);
            $this->setGameStateValue(FIRST_PLAYER_BATTLE, 0);
            $this->setGameStateValue(COMMIT_PHASE, 1);
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
     * Handle requests to defend a city from another owner.
     * Goes into multi-active state for the city owner(s) to respond to the request.
     */
    function stPermissionRequest() {
        $requesting_player = $this->getGameStateValue(REQUESTING_PLAYER);
        if ($requesting_player == 0) {
            throw new BgaVisibleSystemException("No requesting player found for permission request"); // NOI18N
        }

        $activeplayers = [];
        for ($i = 1; $i <= 4; $i++) {
            $request = $this->getGameStateValue(REQUESTED_LOCATION.$i);
            if ($request != 0) {
                $location = $this->Locations->getBattleTileById($request);
                $city = $this->Locations->getCity($location);
                $owner = $this->Cities->getLeader($city);
                if ($owner) {
                    $activeplayers[] = $owner;
                }
            }
        }
        $activeplayers = array_unique($activeplayers);
        if (empty($activeplayers)) {
            throw new BgaVisibleSystemException("No city leaders found for permission requests"); // NOI18N
        }  else {
            $activeplayers[] = $requesting_player;
        }
        // next state does not actually do anything with non-empty player list
        $this->gamestate->setPlayersMultiactive($activeplayers, "resolveRequests", true);
        $this->gamestate->nextState("");
    }

    /**
     * The player requested permission to defend cities, and ALL requests were granted or denied.
     * May be a mix from different players.
     * Return to the player who made the request or to the next player
     */
    function stPermissionResponse() {
        // Get the original requesting player (stored when we entered permission flow)
        $requesting_player = $this->getGameStateValue(REQUESTING_PLAYER);

        if ($requesting_player == 0) {
            throw new BgaVisibleSystemException("No requesting player found for permission response"); // NOI18N
        }

        // if all requests granted, assignUnits and move to next player
        // otherwise return to commit player
        $permissionsGranted = true;

        // Check all requests - use UNIT_PENDING because REQUESTING_CITY/LOCATION were already cleared
        for ($i = 1; $i <= 4 && $permissionsGranted; $i++) {
            $unitstr = $this->globals->get(UNIT_PENDING.$i);
            if (!empty($unitstr)) {
                // There was a request in this slot - check if it was granted
                if ($this->getGameStateValue(REQUEST_STATUS.$i) != 1) {
                    $permissionsGranted = false;
                }
            }
        }

        // if  ALL permissions were granted, assign units, ship 'em off, and move to next player
        if ($permissionsGranted) {
            // Pack committed forces BEFORE clearing (needs UNIT_PENDING values)
            $committedunits = $this->packCommittedForces();
            $units = implode(" ", $committedunits['units']);
            $cube = $committedunits['cube'] ?? "";
            
            // all requests granted, actually ship the units, which should all be valid now
            $perms = $this->validateMilitaryCommits($requesting_player, $units, $cube);
            if (!empty($perms)) {
                throw new BgaVisibleSystemException(sprintf("invalid commitment of units %s", json_encode($perms))); // NOI18N
            }

            $player_id = self::activeNextPlayer();
            $this->gamestate->changeActivePlayer($player_id);
            $this->debug("stPermissionResponse: permissionsGranted=".($permissionsGranted ? "true" : "false").", next player is ".$player_id);
        } else {
            // Return to commit forces state for the requesting player
            // (either no requests were made, or some were denied)
            $this->gamestate->changeActivePlayer($requesting_player);
            $this->debug("stPermissionResponse: permissionsGranted=".($permissionsGranted ? "true" : "false").", next player is ".$requesting_player);
        }

        // Clear permission request data after processing
        $this->clearPermissionRequests();
        $this->gamestate->nextState("");

        if (!$permissionsGranted)  {
            $this->notify->player($requesting_player, 'noDefend', '', array() );
        }
    }

    /**
     * Check whether player can collect units
     */
    function stDeadPool() {
        // increment until each player has had their pick
        if ($this->getGameStateValue(DEADPOOL_COUNTER) == -1) {
            $this->setGameStateValue(DEADPOOL_COUNTER, 0);
            $first_player = $this->getGameStateValue(FIRST_PLAYER_BATTLE);
            $this->gamestate->changeActivePlayer($first_player);
            // initialize choices for choosing
            foreach($this->Cities->cities() as $cn) {
                $types = $this->Deadpool->getTypesInDeadpool($cn);
                $ct = count($types);
                if ($ct == 0 || $ct == 1) {
                    $this->setGameStateValue($cn."_deadpool", DEADPOOL_NOPICK);
                } elseif ($ct == 2) {
                    $this->setGameStateValue($cn."_deadpool", DEADPOOL_TOPICK);
                } else {
                    throw new BgaVisibleSystemException("Invalid deadpool count for $cn: $ct"); // NOI18N
                }
            }
        }

        $state = "";
        $players = self::loadPlayersBasicInfos();
        $nbr = count($players);
        // are we done?
        if ($this->getGameStateValue(DEADPOOL_COUNTER) == $nbr) {
            // reset deadpool flagse
            $this->setGameStateValue(DEADPOOL_COUNTER, -1);
            foreach($this->Cities->cities() as $cn) {
                $this->setGameStateValue($cn."_deadpool", DEADPOOL_NOPICK);
            }
            $state = "startCommit";
        } else {
            $player_id = self::getActivePlayerId();

            $choose = false;
            foreach($this->Cities->controlledCities($player_id) as $city) {
                $deadpool_flag = $this->getGameStateValue($city."_deadpool");
                if ($deadpool_flag == DEADPOOL_TOPICK) {
                    $choose = true;
                } elseif ($deadpool_flag == DEADPOOL_NOPICK) {
                    $types = $this->Deadpool->getTypesInDeadpool($city);
                    if (!empty($types)) {
                        $this->retrieveFromDeadpool($player_id, $city, $types[0]);
                    }
                }
            }

            if ($choose) {
                $state = "takeDead";
            } else{
                $this->incGameStateValue(DEADPOOL_COUNTER, 1);
                $state = "nextPlayer";
                $player_id = self::activeNextPlayer();
                $this->gamestate->changeActivePlayer($player_id);
                self::giveExtraTime( $player_id );
            }
        }
        $this->gamestate->nextState($state);
    }

    /**
     * Send notification to move a unit from Deadpool to the owning player. Automatically picks the lowest strength.
     * Marks this city as having been picked already.
     * @param string player_id
     * @param string city
     * @param string type
     */
    function retrieveFromDeadpool($player_id, $city, $type) {
        $counter = $this->Deadpool->takeFromDeadpool($player_id, $city, $type);
        $id = $counter['id'];
        $strength = $counter['strength'];
        $unit_desc = $this->unitDescription($city, $strength, $type);

        $this->setGameStateValue($city."_deadpool", DEADPOOL_PICKED);

        self::notifyAllPlayers('retrieveDeadpool', clienttranslate('${icon} ${unit} retrieved from dead pool'), array(
            'i18n' => ['unit'],
            'player_id' => $player_id,
            'unit' => $unit_desc,
            'city' => $city,
            'id' => $id,
            'strength' => $strength,
            'type' => $type,
            'icon' => true,
            'deadpool' => true,
            'preserve' => ['city', 'type', 'strength', 'deadpool'],
        ));
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
        } else if ($this->SpecialTiles->canPlaySpecial($player_id, INFLUENCE_PHASE) && !$this->isAutopass($player_id)) {
            $state = "useSpecial";
        }
        $this->gamestate->nextState( $state );
    }

    /**
     * Do all the elections.
     */
    function stElections() {
        // end influence phase
        $this->setGameStateValue(INFLUENCE_PHASE, 0);

        foreach ($this->Cities->cities() as $cn) {
            $a = $this->Cities->getCandidate($cn, "a");
            $b = $this->Cities->getCandidate($cn, "b");
            $winner = 0;
            if (empty($a)) {
                if (empty($b)) {
                    // no candidates!
                    self::notifyAllPlayers("noElection", clienttranslate('${city_name} has no candidates; no Leader assigned'), array(
                        'i18n' => ['city_name'],
                        'city_name' => $this->Cities->getNameTr($cn),
                    ));
                } else {
                    // B is unopposed
                    $this->unopposedElection($b, $cn);
                    $winner = $b;
                }
            } elseif (empty($b)) {
                // A is unopposed
                $this->unopposedElection($a, $cn);
                $winner = $a;
            } else {
                // contested election
                $winner = $this->resolveElection($cn);
            }
            $this->Cities->clearCandidates($cn);
            $this->Cities->setLeader($winner, $cn);

            if ($winner != 0) {
                $this->moveMilitaryUnits($winner, $cn);
            }
        }
        // anyone who is not a leader of any city is a Persian leader
        $this->setPersianLeaders();

        // sparta leader chooses
        $sparta = $this->Cities->getLeader("sparta");
        $this->gamestate->changeActivePlayer($sparta);
        $this->gamestate->nextState("");
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

        if ($tile === null) {
            $state = "endTurn";
        } else {
            $this->revealCounters($tile);
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
            } elseif ($defender) {
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

        $this->setGameStateValue(ACTIVE_BATTLE, $slot);
        // if incremented past number of rounds at this location, battle is over
        $round = $this->incGameStateValue(BATTLE_ROUND, 1);

        $combat = $this->Locations->getCombat($location, $round);

        if ($combat == null) {
            // there should be a winner and we have already done losses
            $this->battleVictory($tile);
            $state = "endBattle";
        } else {
            if ($round != 1) {
                // if there was a previous battle, one side starts with a battle token
                $loser = $this->getGameStateValue(LOSER);

        
                // return the units from the first battle
                $prevcombat = $this->Locations->getCombat($location, 1);
                $this->returnMilitaryUnits($tile, $prevcombat);

                $this->secondRoundReset($loser);
            }
            $state = "nextCombat";
        }

        $this->gamestate->nextState($state);
    }

    /**
     * There are forces on both sides (at least in one combat).
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
        $round = $this->getGameStateValue(BATTLE_ROUND);
        $combat = $this->Locations->getCombat($location, $round);
        $attackers = $this->Battles->getAttackingCounters($location, $combat);
        $defenders = $this->Battles->getDefendingCounters($location, $combat);

        $players = self::loadPlayersBasicInfos();
        // is this round a one-sided battle?
        $unopposed = null;
        $nocombatants = false;

        if (empty($attackers) || empty($defenders)) {
            if (empty($attackers) && empty($defenders)) {
                // no combatants, no battle
                $nocombatants = true;
            } elseif (empty($attackers)) {
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

        if ($nocombatants) {
            self::notifyAllPlayers("nonbattle", clienttranslate('${icon} No ${combat_type} battle at ${location_name}'), array(
                'i18n' => ['combat_type', 'location_name'],
                'combat_type' => $this->getUnitName($combat),
                'icon' => true,
                'location' => $location,
                'location_name' => $this->Locations->getName($location),
                'preserve' => ['location']
            ));
            // if this was round 1, go on to round 2. Otherwise, unclaimed tile and go to next
            if ($round == 1) {
                $state = "continueBattle";
            } else {
                self::notifyAllPlayers('unclaimedTile', clienttranslate('No combatants in second round of battle at ${location_name}; no one claims tile'), array(
                    'i18n' => ['location_name'],
                    'icon' => true,
                    'location' => $location,
                    'location_name' => $this->Locations->getName($location),
                    'preserve' => ['location']
                ));
                $this->unclaimedTile($tile);
                $state = "nextBattle";
            }
        } else {
            if ($unopposed !== null) {
                $unopposed_id = ($unopposed === ATTACKER) ? $attacker : $defender;
                $unopposed_role = ($unopposed === ATTACKER) ? clienttranslate('Attacker') : clienttranslate('Defender');
    
                self::notifyAllPlayers("freeToken", clienttranslate('${icon} ${player_name} (${side}) wins ${combat_type} battle at ${location_name} unopposed'), array(
                    'i18n' => ['combat_type', 'side', 'location_name'],
                    'player_id' => $unopposed_id,
                    'player_name' => $players[$unopposed_id]['player_name'],
                    'type' => $combat,
                    'side' => $unopposed_role,
                    'combat_type' => $this->getUnitName($combat),
                    'icon' => true,
                    'location' => $location,
                    'location_name' => $this->Locations->getName($location),
                    'preserve' => ['player_id', 'location']
                ));
    
                if ($round == 1) {
                    $loser = ($unopposed == ATTACKER) ? DEFENDER : ATTACKER;
                    $this->setGameStateValue(LOSER, $loser);
                    $state = "continueBattle";
                } else {
                    // unopposed side wins tile
                    $this->claimTile($unopposed_id, $tile, $unopposed);
                    $state = "nextBattle";
                }
            } else {
                $specialplayers = $this->specialBattleTilePlayers($combat);
                if (empty($specialplayers)) {
                    $state = "combat";
                } else {
                    $this->gamestate->setPlayersMultiactive( $specialplayers, "combat", true );
                    $state = "useSpecial";
                }
            }
        }

        $this->gamestate->nextState($state);
    }

    /**
     * All checks have been done, there are forces on each side, and all player actions completed.
     * At this point, roll dice until one side wins. This is for ONE combat (Hoplite or Trireme).
     */
    function stRollCombat() {
        $tile = $this->Battles->nextBattle();
        $location = $tile['location'];
        $slot = $tile['slot'];

        $round = $this->getGameStateValue(BATTLE_ROUND);
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

        $winner = $this->rollBattle($location, $slot, $type, $attstrength, $defstrength);

        $winningside = null;
        if ($winner === ATTACKER) {
            $winningside = clienttranslate('Attacker');
            $loser = DEFENDER;
            $loser_id = $this->Battles->getDefender($location);
        } else {
            $winningside = clienttranslate('Defender');
            $loser = ATTACKER;
            $loser_id = $this->Battles->getAttacker($location);
        }
        self::notifyAllPlayers("combatWinner", clienttranslate('${icon} ${winner} wins ${type} battle at ${location_name}'), array(
            'i18n' => ['winner', 'type', 'location_name'],
            'winner' => $winningside,
            'type' => $this->getUnitName($type),
            'icon' => true,
            'location' => $location,
            'location_name' => $this->Locations->getName($location),
            'preserve' => ['location']
        ));

        $this->setGameStateValue(LOSER, $loser);
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
            $this->sendToDeadpool($casualty);
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
        // most recent Athens leader is start player next turn
        $athens_leader = $this->Cities->getLeader("athens");
        if ($athens_leader == 0) {
            $athens_leader = $this->getGameStateValue(ATHENS_PLAYER);
        }
        $this->setGameStateValue(ATHENS_PLAYER, $athens_leader);

        // add statues
        $this->leadersToStatues();
        $this->Cities->clearWars();
        $this->Locations->clearBattleStatus();
        $this->returnPlayersMilitary();
        // must happen AFTER returning military!
        $this->Cities->clearLeaders();
        self::notifyAllPlayers("endTurn", [], []);
        

        if ($state == "nextTurn") {
            $players = self::loadPlayersBasicInfos();
            // reshuffle Influence deck and deal new cards
            $this->setGameStateValue(INFLUENCE_PHASE, 1);
            $this->dealNewInfluence();
            $this->dealNewLocations();
            $this->gamestate->changeActivePlayer($athens_leader);
            self::notifyAllPlayers("nextTurn", clienttranslate('New turn: ${city_name} leader (${player_name}) is first player'), array(
                'i18n' => ['city_name'],
                'player_id' => $athens_leader,
                'player_name' => $players[$athens_leader]['player_name'],
                'city' => 'athens',
                'city_name' => $this->Cities->getNameTr('athens'),
                'preserve' => ['player_id', 'city'],
            ));
        }

        $this->gamestate->nextState($state);
    }

    /**
     * End of game scoring
     */
    function stScoring() {
        $players = self::loadPlayersBasicInfos();

        // start constructing the score table
        $score_table = array();
        $player_row = array("");
        $vp_tiles_row = array(clienttranslate("VPs from Location Tiles"));
        $vp_statues_row = array(clienttranslate("VPs from Statues"));
        $vp_cubes_row = array(clienttranslate("VPs from Influence cubes"));
        $vp_total_row = array(clienttranslate("Total VPs"));

        $scoring_inc = 0;
        foreach(array_keys($players) as $player_id) {
            // basic player_score_aux is points in location tiles (current VPs)
            $currentscore = $this->getVPs($player_id);
            $this->addAuxVPs($player_id, $currentscore*100 );
            self::incStat($currentscore, "victory_tile_points", $player_id);

            $player_row[] = array(
                'str' => '${player_name}',
                'args' => array( 'player_name' => $players[$player_id]['player_name'] ),
                'type' => 'header'
            );
            $vp_tiles_row[] = $currentscore;

            $playerstatues = $this->Cities->getStatues($player_id);
            $statue_vps = 0;
            $cube_vps = 0;
            foreach($this->Cities->cities() as $city) {
                // statues
                if (array_key_exists($city, $playerstatues)) {
                    $statues = $playerstatues[$city];
                    $vp = $this->Cities->victoryPoints($city);
                    $total = $statues*$vp;
                    $statue_vps += $total;
                    $this->addVPs($player_id, $total);
                    $this->addAuxVPs($player_id, $statues);
                    self::incStat($total, "statue_points", $player_id);

                    self::notifyAllPlayers("scoreStatues", clienttranslate('${player_name} scores ${total} points for ${statues} statues in ${city_name}'), array(
                        'i18n' => ['city_name'],
                        'player_id' => $player_id,
                        'player_name' => $players[$player_id]['player_name'],
                        'city' => $city,
                        'city_name' => $this->Cities->getNameTr($city),
                        'total' => $total,
                        'vp' => $vp, // per statue
                        'scoring_delay' => $scoring_inc,
                        'statues' => $statues,
                        'preserve' => ['player_id', 'city']
                    ));
                    $scoring_inc += $statues;
                }
                // 1 point per cube
                $cubes = $this->Cities->cubesInCity($player_id, $city);
                $cube_vps += $cubes;
                if ($cubes > 0) {
                    self::notifyAllPlayers("scoreCubes", clienttranslate('${player_name} scores ${vp} cubes in ${city_name}'), array(
                        'i18n' => ['city_name'],
                        'player_id' => $player_id,
                        'player_name' => $players[$player_id]['player_name'],
                        'city' => $city,
                        'city_name' => $this->Cities->getNameTr($city),
                        'vp' => $cubes,
                        'scoring_delay' => $scoring_inc,
                        'preserve' => ['player_id', 'city']
                    ));
                    $scoring_inc++;
                    $this->addVPs($player_id, $cubes);
                    self::incStat($cubes, "cube_points", $player_id);
                }
            }

            $vp_statues_row[] = $statue_vps;
            $vp_cubes_row[] = $cube_vps;
            $vp_total_row[] = $this->getVPs($player_id);
        }

        // now add completed rows to table
        $score_table[] = $player_row;
        $score_table[] = $vp_tiles_row;
        $score_table[] = $vp_statues_row;
        $score_table[] = $vp_cubes_row;
        $score_table[] = $vp_total_row;

        // In the case of a tie the tied player who scored the most victory points
        // on Location tiles wins. If there is still a tie then the tied player with the
        // most statues wins.
        $winners = $this->getWinners();
        $winner_name = "";
        // tie with all tie-breakers? Should never happen but just in case...
        if (count($winners) > 1) {
            $winner_names = [];
            foreach($winners as $winner) {
                $winner_names[] = $players[$winner]['player_name'];
            }
            $winner_name = implode(',', $winner_names);
        } else {
            $winner = $winners[0];
            $winner_name = $players[$winner]['player_name'];
        }

        $winnerstring = clienttranslate('${winner_name} is master of the Peloponnese!');
        self::notifyAllPlayers( "tableWindow", '', array(
            'id' => 'finalScoring',
            'title' => clienttranslate("The Peloponnesian War has ended!"),
            'header' => array('str' => $winnerstring, 'args' => array('winner_name' => $winner_name)),
            'table' => $score_table,
            'closing' => clienttranslate("Game Over")
        ) );

        // Score statues
        $this->gamestate->nextState("");
    }

    /**
     * Get list of player_id(s) who won.
     * @return {array} player_ids, should almost always be one player
     */
    function getWinners() {
        $players = self::loadPlayersBasicInfos();
        $max = -1;
        $winners = [];
        foreach(array_keys($players) as $player_id) {
            $vp = $this->getVPs($player_id);
            if ($vp > $max) {
                $max = $vp;
                $winners = [$player_id];
            } elseif ($vp == $max) {
                $winners[] = $player_id;
            }
        }
        if (count($winners) > 1) {
            $max = -1;
            foreach($winners as $tied) {
                $aux = $this->getAuxVPs($tied);
                if ($aux > $max) {
                    $max = $aux;
                    $winners = [$tied];
                } elseif ($aux == $max) {
                    $winners[] = $tied;
                }
            }
        }
        return $winners;
    }

    // function stDebug() {
    //     $player = self::getActivePlayerName();
    //     throw new BgaVisibleSystemException("$player in stDebug");
    // }

    // function logDebug($msg) {
    //     self::notifyAllPlayers("debug", $msg, []);
    // }

//////////////////////////////////////////////////////////////////////////////
//////////// Zombie
////////////

    // function callZombie($numCycles = 1) { // Runs zombieTurn() on all active players
    //     // Note: isMultiactiveState() doesn't work during this! It crashes without yielding an error.
    //     for ($cycle = 0; $cycle < $numCycles; $cycle++) {
    //         $state = $this->gamestate->state();
    //         $activePlayers = $this->gamestate->getActivePlayerList(); // this works in both active and multiactive states

    //         // You can remove the notification if you find it too noisy
    //         self::notifyAllPlayers('notifyZombie', '<u>ZombieTest turn ${cycle}/$numCycles for ${statename}</u>', [
    //             'cycle'     => $cycle+1,
    //             'numCycles'     => $numCycles,
    //             'statename' => $state['name']
    //         ]);

    //         // Make each active player take a zombie turn
    //         foreach ($activePlayers as $playerId) {
    //             self::zombieTurn($state, (int)$playerId);
    //         }
    //     }
    // }

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

    function zombieTurn( $state, $active_player ) {
    	$statename = $state['name'];

        // if ($state['type'] == "activeplayer") {
            switch ($statename) {
                case 'chooseInitialInfluence':
                    $this->placeRandomInfluenceCube($active_player);
                    break;
                case 'takeInfluence':
                    $tile = $this->chooseRandomTile($active_player);
                    $this->takeInfluence($tile['id'], $active_player);
                    break;
                case 'choosePlaceInfluence':
                    $this->placeRandomInfluenceCube($active_player);
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
                    $this->chooseNextPlayer($firstplayer, $active_player);
                    break;
                case 'commitForces':
                    $this->sendRandomUnits($active_player);
                    break;
                case 'requestPermission':
                    // this should never happen
                    throw new BgaVisibleSystemException("Zombie player $active_player cannot request permission"); // NOI18N
                    break;
                case 'permissionResponse':
                    throw new BgaVisibleSystemException("Zombie player $active_player cannot respond to requests - you should cancel your request"); // NOI18N
                    break;
                case 'specialTile':
                    $this->specialTilePass($active_player);
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
                    $this->sendToDeadpool($casualty);
                    $this->gamestate->nextState( "" );
                    break;
                case "takeDead":
                    // game has determined this player can choose
                    $cities = $this->getDeadpoolCities($active_player);
                    if (empty($cities)) {
                        throw new BgaVisibleSystemException("zombie player $active_player has no deadpool cities to choose"); // NOI18N
                    }
                    $types = [HOPLITE, TRIREME];
                    $coinflip = bga_rand(0,1);
                    $this->chooseDeadpool($cities[0], $types[$coinflip]);
                    break;
                default:
                    $this->gamestate->nextState( "zombiePass" );
                    break;
            }
            return;
        // }
    }

    /**
     * Choose a random city to place a cube in.
     */
    function placeRandomInfluenceCube($active_player) {
        $cities = $this->Cities->cities();
        shuffle($cities);
        $city = $cities[0];
        $this->placeAnyCube($city, $active_player);
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
                            $this->proposeCandidate($cn, $candidate_id, $player_id);
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
                            $this->proposeCandidate($cn, $candidate_id, $player_id);
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
                $this->chooseRemoveCube($target, $cn, $killcube, $player_id);
                break;
            } else {
                $this->chooseRemoveCube($killcube, $cn, 1, $player_id);
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
                    $mycitybattles = $this->Locations->getBattleTiles($city);
    
                    // is there a city we can attack?
                    if (empty($mycitybattles)) {
                        $allbattles = $this->Locations->getBattleTiles();
                        shuffle($allbattles);
                        $tile = array_pop($allbattles);
                        $defcity = $tile['city'];
                        $location = $tile['location'];
                       
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
                        $location = $defbattle['location'];
                        // make sure not sending trireme to a land battle
                        if (!($unit['type'] == TRIREME && $this->Locations->isLandBattle($location))) {
                            $unitstr = $unit['id']."_defend_".$location;
                        }
                    }
                }
            }
            $assignment .= $unitstr." ";
        }

        $this->assignUnits($assignment, "", $player_id);
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

	// public function LoadDebug()
	// {
	// 	// These are the id's from the BGAtable I need to debug.
	// 	// you can get them by running this query : SELECT JSON_ARRAYAGG(`player_id`) FROM `player`
	// 	$ids = [
	// 		84417312,
	// 		84410939,
	// 		84403562,
	// 		84828357,
	// 		84404277
	// 	];

	// 	// Id of the first player in BGA Studio
	// 	$sid = 2307217;

	// 	foreach ($ids as $id) {
	// 		// basic tables
	// 		self::DbQuery("UPDATE player SET player_id='$sid' WHERE player_id = '$id'" );
	// 		self::DbQuery("UPDATE global SET global_value='$sid' WHERE global_value = '$id'" );
	// 		self::DbQuery("UPDATE stats SET stats_player_id='$sid' WHERE stats_player_id = '$id'" );

	// 		// 'other' game specific tables. example:
	// 		// tables specific to your schema that use player_ids
	// 		self::DbQuery("UPDATE INFLUENCE SET card_location='$sid' WHERE card_location='$id'" );
    //         foreach(["card_location", "attacker", "defender", "persia1", "persia2", "persia3", "persia4"] as $locarg) {
    //             self::DbQuery("UPDATE LOCATION SET $locarg='$sid' WHERE $locarg='$id'" );
    //         }
    //         self::DbQuery("UPDATE MILITARY SET location='$sid' WHERE location='$id'" );
    //         // clear autopass
    //         self::DbQuery("UPDATE player SET special_tile_pass=0");

	// 		++$sid;
	// 	}
	// }

}
