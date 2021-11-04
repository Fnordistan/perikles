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


define("INFLUENCE", "influence");
define("CANDIDATE", "candidate");
define("ASSASSIN", "assassin");
define("DECK", "deck");
define("DISCARD", "discard");
define("BOARD", "board");
define("HOPLITE", "hoplite");
define("TRIREME", "trireme");
define("PERSIA", "persia");
define("ALPHA", "\u{003B1}");
define("BETA", "\u{003B2}");

class Perikles extends Table
{
	function __construct( )
	{
        // Your global variables labels:
        //  Here, you can assign labels to global variables you are using for this game.
        //  You can use any number of global variables with IDs between 10 and 99.
        //  If your game has options (variants), you also have to associate here a label to
        //  the corresponding ID in gameoptions.inc.php.
        // Note: afterwards, you can get/set the global variables with getGameStateValue/setGameStateInitialValue/setGameStateValue
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
            "last_influence_slot" => 37, // keep track of where to put next Influence tile
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
        
        This method is called only once, when a new game is launched.
        In this method, you must setup the game according to the game rules, so that
        the game is ready to be played.
    */
    protected function setupNewGame( $players, $options = array() )
    {    
        // Set the colors of the players with HTML color code
        // The default below is red/green/blue/orange/brown
        // The number of colors defined here must correspond to the maximum number of players allowed for the gams
        $gameinfos = self::getGameinfos();
        $default_colors = $gameinfos['player_colors'];
 
        // Create players
        $sql = "INSERT INTO player (player_id, player_color, player_canal, player_name, player_avatar) VALUES ";
        $values = array();
        foreach( $players as $player_id => $player )
        {
            $color = array_shift( $default_colors );
            $values[] = "('".$player_id."','$color','".$player['player_canal']."','".addslashes( $player['player_name'] )."','".addslashes( $player['player_avatar'] )."')";
            foreach($this->cities as $cn => $city) {
                $statues = $cn."_statues";
                self::initStat( 'player', $statues, 0, $player_id);
            }
        }
        $sql .= implode( $values, ',' );
        self::DbQuery( $sql );
        self::reattributeColorsBasedOnPreferences( $players, $gameinfos['player_colors'] );
        self::reloadPlayersBasicInfos();

        /************ Start the game initialization *****/

        // Init global values with their initial values
        $city_states = ["leader", "a", "b", "defeats"];
        foreach($this->cities as $cn => $city) {
            foreach ($city_states as $lbl) {
                self::setGameStateInitialValue( $cn."_".$lbl, 0 );
            }
        }
        self::setGameStateInitialValue("last_influence_slot", 0);
        
        // Init game statistics
        // (note: statistics used in this file must be defined in your stats.inc.php file)
        //self::initStat( 'player', 'player_teststat1', 0 );  // Init a player statistics (for all players)
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
                        self::DbQuery( "INSERT INTO MILITARY VALUES($idct,\"$cn\",\"$unit\",$strength,\"$cn\")" );
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
  
        $result['influencetiles'] = self::getObjectListFromDB("SELECT card_id id, card_type city, card_type_arg type, card_location location, card_location_arg slot FROM INFLUENCE WHERE card_location != \"".DECK."\" AND card_location != \"".DISCARD."\"");
        $result['decksize'] = $this->influence_tiles->countCardInLocation(DECK);

        $result['locationtiles'] = self::getObjectListFromDB("SELECT card_id id, card_type city, card_type_arg location, card_location_arg slot FROM LOCATION WHERE card_location='".BOARD."'");
        
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
        foreach ($this->cities as $cn => $city) {
            $leader = self::getGameStateValue($cn."_leader");
            if ($leader != 0) {
                $leaders[$cn] = $leader;
            }
        }
        return $leaders;
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
     */
    function getMilitary() {
        $military = self::getObjectListFromDB("SELECT id, city, type, strength, location FROM MILITARY");
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
        // TODO: compute and return the game progression

        return 0;
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Utility functions
////////////    


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
        foreach ($this->cities as $c => $city) {
            if ($this->canNominate($player_id, $c)) {
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
        foreach ($players as $player_id => $player) {
            if ($this->canNominateAny($player_id)) {
                return true;
            }
        }
        return false;
    }

    /**
     * There is a hard limit of 30 cubes on the board per player.
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
     * Add cubes to a city and send notification.
     */
    function addInfluenceToCity($city, $player_id, $cubes) {
        $players = self::loadPlayersBasicInfos();
        $player_name = $players[$player_id]['player_name'];

        $cubect = $this->allCubesOnBoard($player_id);
        if ($cubect >= 30) {
            throw new BgaUserException(self::_("$player_name already has 30 cubes on the board"));
        }

        $this->changeInfluenceInCity($city, $player_id, $cubes);
        $city_name = $this->cities[$city]['name'];

        self::notifyAllPlayers('influenceCubes', clienttranslate('${player_name} adds ${cubes} Influence to ${city_name}'), array(
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

//////////////////////////////////////////////////////////////////////////////
//////////// Player actions
//////////// 

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

        $this->gamestate->changeActivePlayer($player_id);
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
                    throw new BgaUserException(self::_("You may not take another $city_name Influence tile"));
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
        $this->gamestate->nextState();
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
            throw new BgaUserException(self::_("You cannot propose a Candidate in $city_name: you have no Influence cubes in this city"));
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
                throw new BgaUserException(self::_("$city_name has no empty Candidate spaces"));
            } else if ($cand_a == $candidate_id) {
                throw new BgaUserException(self::_("$candidate_name is already a Candidate in $city_name"));
            }
        }
        // does the nominated player have cubes there?
        $cubes = self::getUniqueValueFromDB("SELECT $city FROM player WHERE player_id=$candidate_id");
        if ($cubes == 0) {
            throw new BgaUserException(self::_("$candidate_name has no Influence cubes in $city_name"));
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

        $this->gamestate->nextState();
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
        $this->gamestate->nextState();
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Game state arguments
////////////

    /*
        Here, you can create methods defined as "game state arguments" (see "args" property in states.inc.php).
        These methods function is to return some additional information that is specific to the current
        game state.
    */

    /*
    
    Example for game state "MyGameState":
    
    function argMyGameState()
    {
        // Get some values from the current game situation in database...
    
        // return values:
        return array(
            'variable1' => $value1,
            'variable2' => $value2,
            ...
        );
    }    
    */

//////////////////////////////////////////////////////////////////////////////
//////////// Game state actions
////////////

    /**
     * Handles next player action through Influence phase.
     */
    function stNextPlayer() {
        $state = "";
        if ($this->allInfluenceTilesTaken()) {
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

    function stMilitary() {
        $sparta = self::getGameStateValue("sparta_leader");

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
        }
        $this->gamestate->nextState( $state );
    }

    /**
     * Do all the elections.
     */
    function stElections() {
        $players = self::loadPlayersBasicInfos();
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
                default:
                    $this->gamestate->nextState( "zombiePass" );
                	break;
            }

            return;
        }

        if ($state['type'] === "multipleactiveplayer") {
            // Make sure player is in a non blocking status for role turn
            $this->gamestate->setPlayerNonMultiactive( $active_player, '' );
            
            return;
        }

        throw new feException( "Zombie mode not supported at this game state: ".$statename );
    }

    /**
     * Take a random city tile on the board and give it to zombie player.
     */
    function chooseRandomTile($player_id) {
        $tiles = $this->influence_tiles->getCardsInLocation(BOARD);
        foreach ($tiles as $t =>$tile) {
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
            foreach($players as $target_id => $player) {
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
