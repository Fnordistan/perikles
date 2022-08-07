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
 * states.inc.php
 *
 * Perikles game states description
 *
 */

if (!defined('SETUP')) { // ensure this block is only invoked once, since it is included multiple times
    // state constants
    define("SETUP", 1);

    // Influence phase
    define("TAKE_INFLUENCE", 2);
    define("PLACE_INFLUENCE", 3);
    define("CHOOSE_PLACE_INFLUENCE", 4);
    define("ASSIGN_CANDIDATE", 5);
    define("ASSASSINATE", 6);
    define("NEXT_PLAYER", 7);

    // Election phase
    define("PROPOSE_CANDIDATE", 10);
    define("ELECTIONS", 11);

    // Commit forces phase
    define("ASSIGN_LEADERS", 20);
    define("SPARTAN_CHOICE", 21);
    define("DEAD_POOL", 22);
    define("BRING_OUT_YOUR_DEAD", 23);
    define("COMMIT_FORCES", 24);
    define("NEXT_COMMIT", 25);

    // Request Permission
    define("HANDLE_REQUEST", 50);
    define("REQUEST_RESPONSE", 51);
    define("HANDLE_RESPONSE", 52);

    // Battle phase
    define("NEXT_BATTLE_TILE", 30);
    define("RESOLVE_TILE", 31);
    define("NEXT_COMBAT", 32);
    define("ROLL_BATTLE", 33);
    define("TAKE_LOSS", 34);
    define("END_TURN", 35);

    // SPECIAL TILES
    define("SPECIAL_TILE", 40);
    define("SPECIAL_BATTLE_TILE", 41);

    // Endgame
    define("SCORING", 90);
    define("ENDGAME", 99);
}

$machinestates = array(

    // The initial state. Please do not modify.
    1 => array(
        "name" => "gameSetup",
        "description" => "",
        "type" => "manager",
        "action" => "stGameSetup",
        "transitions" => array( "" => TAKE_INFLUENCE )
    ),

    TAKE_INFLUENCE => array(
    	"name" => "takeInfluence",
    	"description" => clienttranslate('${actplayer} must take an Influence tile'),
    	"descriptionmyturn" => clienttranslate('You must take an Influence tile'),
    	"type" => "activeplayer",
        "args" => "argsSpecial",
    	"possibleactions" => array("takeInfluence", "useSpecial"),
    	"transitions" => array( "placeCube" => PLACE_INFLUENCE, "choosePlaceCube" => CHOOSE_PLACE_INFLUENCE, "continueTurn" => TAKE_INFLUENCE)
    ),

    PLACE_INFLUENCE => array(
    	"name" => "placeInfluence",
    	"description" => "",
    	"type" => "game",
    	"action" => "stPlaceInfluence",
    	"transitions" => array( "useSpecial" => SPECIAL_TILE, "assassinate" => ASSASSINATE, "candidate" => PROPOSE_CANDIDATE, "nextPlayer" => NEXT_PLAYER )
    ),

    CHOOSE_PLACE_INFLUENCE => array(
    	"name" => "choosePlaceInfluence",
    	"description" => clienttranslate('${actplayer} must choose a city to add an Influence cube'),
    	"descriptionmyturn" => clienttranslate('You must choose a city to add an Influence cube'),
    	"type" => "activeplayer",
    	"possibleactions" => array( "placeAnyCube" ),
    	"transitions" => array( "nextPlayer" => NEXT_PLAYER, "useSpecial" => SPECIAL_TILE )
    ),

    PROPOSE_CANDIDATE => array(
    	"name" => "proposeCandidates",
    	"description" => clienttranslate('${actplayer} must propose a candidate'),
    	"descriptionmyturn" => clienttranslate('You must propose a candidate'),
    	"type" => "activeplayer",
    	"possibleactions" => array( "proposeCandidate" ),
    	"transitions" => array( "nextPlayer" => NEXT_PLAYER, "useSpecial" => SPECIAL_TILE )
    ),

    ASSASSINATE => array(
    	"name" => "assassinate",
    	"description" => clienttranslate('${actplayer} must remove 1 Influence cube'),
    	"descriptionmyturn" => clienttranslate('You must remove 1 Influence cube'),
    	"type" => "activeplayer",
    	"possibleactions" => array( "chooseRemoveCube" ),
    	"transitions" => array( "nextPlayer" => NEXT_PLAYER, "useSpecial" => SPECIAL_TILE )
    ),

    /**
     * This is the optional check to use Special after regular phase.
     */
    SPECIAL_TILE => array(
    	"name" => "specialTile",
    	"description" => clienttranslate('Special Tile phase'),
    	"descriptionmyturn" => clienttranslate('You may use your Special Tile'),
        "args" => "argsSpecial",
    	"type" => "activeplayer",
    	"possibleactions" => array("useSpecial"),
    	"transitions" => array( "nextPlayer" => NEXT_PLAYER, "nextCommit" => NEXT_COMMIT, "continueCommit" => COMMIT_FORCES )
    ),

    /**
     * For battle Special Tiles.
     */
    SPECIAL_BATTLE_TILE => array(
    	"name" => "specialBattleTile",
    	"description" => clienttranslate('Special Tile phase'),
    	"descriptionmyturn" => clienttranslate('You may use your Special Tile at ${battle_location}'),
        "args" => "argsSpecialBattle",
    	"type" => "multipleactiveplayer",
    	"possibleactions" => array( "useSpecialBattle" ),
    	"transitions" => array( "battle" => ROLL_BATTLE )
    ),

    ELECTIONS => array(
    	"name" => "election",
    	"description" => "",
    	"type" => "game",
    	"action" => "stElections",
    	"transitions" => array( "" => SPARTAN_CHOICE )
    ),

    SPARTAN_CHOICE => array(
        "name" => "spartanChoice",
    	"description" => clienttranslate('Spartan Leader ${actplayer} must choose the first player to commit forces'),
    	"descriptionmyturn" => clienttranslate('You must choose the first player to commit forces'),
    	"type" => "activeplayer",
    	"possibleactions" => array( "chooseNextPlayer" ),
    	"transitions" => array( "" => DEAD_POOL)
    ),

    DEAD_POOL => array(
    	"name" => "deadPool",
    	"description" => "",
    	"type" => "game",
    	"action" => "stDeadPool",
    	"transitions" => array( "nextPlayer" => DEAD_POOL, "takeDead" => BRING_OUT_YOUR_DEAD, "startCommit" => NEXT_COMMIT)
    ),

    BRING_OUT_YOUR_DEAD => array(
    	"name" => "takeDead",
    	"description" => clienttranslate('${actplayer} must choose unit(s) from the dead pool'),
    	"descriptionmyturn" => clienttranslate('You must choose unit(s) from the dead pool'),
    	"type" => "activeplayer",
    	"possibleactions" => array( "chooseDeadUnits" ),
    	"transitions" => array( "nextPlayer" => NEXT_PLAYER)
    ),

    NEXT_COMMIT => array(
        "name" => "nextPlayerCommit",
        "description" => "",
        "type" => "game",
        "action" => "stNextCommit",
        "transitions" => array( "commit" => COMMIT_FORCES, "nextPlayer" => NEXT_COMMIT, "resolve" => NEXT_BATTLE_TILE )
    ),

    COMMIT_FORCES => array(
    	"name" => "commitForces",
    	"description" => clienttranslate('${actplayer} must commit forces'),
    	"descriptionmyturn" => clienttranslate('You must commit forces'),
        "args" => "argsSpecial",
    	"type" => "activeplayer",
    	"possibleactions" => array( "assignUnits", "useSpecial" ),
    	"transitions" => array( "nextPlayer" => NEXT_COMMIT, "useSpecial" => SPECIAL_TILE, "askPermission" => HANDLE_REQUEST)
    ),

    HANDLE_REQUEST => array(
        "name" => "handleRequest",
        "description" => "",
        "type" => "game",
        "action" => "stPermissionRequest",
        "transitions" => array( "getResponse" => REQUEST_RESPONSE )
    ),

    REQUEST_RESPONSE => array(
    	"name" => "requestResponse",
    	"description" => clienttranslate('${player_name} requests permission to join defenders at ${location_name}'),
    	"descriptionmyturn" => clienttranslate('${player_name} requests permission to join defenders at ${location_name}'),
        "args" => "argsPermission",
    	"type" => "activeplayer",
    	"possibleactions" => array( "respondRequest", "cancelRequest" ),
    	"transitions" => array( "" => HANDLE_RESPONSE)
    ),

    HANDLE_RESPONSE => array(
        "name" => "handleResponse",
        "description" => "",
        "type" => "game",
        "action" => "stRequestResponse",
        "transitions" => array( "" => COMMIT_FORCES )
    ),

    // rotate to the next location tile
    NEXT_BATTLE_TILE => array(
        "name" => "nextTile",
        "description" => "",
        "type" => "game",
        "action" => "stNextLocationTile",
        "transitions" => array( "resolve" => RESOLVE_TILE, "nextBattle" => NEXT_BATTLE_TILE, "endTurn" => END_TURN )
    ),

    /**
     * Manage combat(s) for one tile.
     */
    RESOLVE_TILE => array(
        "name" => "resolveTile",
        "description" => "",
        "type" => "game",
        "action" => "stResolveTile",
        "transitions" => array( "nextCombat" => NEXT_COMBAT, "endBattle" => NEXT_BATTLE_TILE )
    ),

    // handle one battle (may be 1-2 per tile)
    NEXT_COMBAT => array(
        "name" => "battle",
        "description" => "",
        "type" => "game",
        "action" => "stCombat",
        "transitions" => array( "nextBattle" => NEXT_BATTLE_TILE, "continueBattle" => RESOLVE_TILE, "combat" => ROLL_BATTLE, "useSpecial" => SPECIAL_BATTLE_TILE )
    ),

    // all player actions completed, roll a SINGLE combat until it's over
    ROLL_BATTLE => array(
        "name" => "rollcombat",
        "description" => "",
        "type" => "game",
        "action" => "stRollCombat",
        "transitions" => array( "takeLoss" => TAKE_LOSS, "endBattle" => RESOLVE_TILE )
    ),

    // losers must lose one counter
    TAKE_LOSS => array(
    	"name" => "takeLoss",
    	"description" => clienttranslate('${actplayer} must lose one counter'),
    	"descriptionmyturn" => clienttranslate('You must lose one counter'),
        "args" => "argsLoss",
    	"type" => "activeplayer",
    	"possibleactions" => array( "chooseLoss" ),
    	"transitions" => array( "" =>  RESOLVE_TILE )
    ),

    END_TURN => array(
        "name" => "endTurn",
        "description" => "",
        "updateGameProgression" => true,   
    	"type" => "game",
    	"action" => "stEndTurn",
        "transitions" => array( "nextTurn" => TAKE_INFLUENCE, "endGame" => SCORING )
    ),

    NEXT_PLAYER => array(
        "name" => "nextPlayer",
        "description" => "",
        "type" => "game",
        "action" => "stNextPlayer",
        "transitions" => array( "takeInfluence" => TAKE_INFLUENCE, "proposeCandidate" => PROPOSE_CANDIDATE, "elections" => ELECTIONS, "nextPlayer" => NEXT_PLAYER, "nextCommit" => NEXT_COMMIT )
    ),

    SCORING => array(
        "name" => "finalScoring",
        "description" => "",
        "type" => "game",
        "action" => "stScoring",
        "transitions" => array( "" => ENDGAME )
    ),

    // Final state.
    // Please do not modify (and do not overload action/args methods).
    ENDGAME => array(
        "name" => "gameEnd",
        "description" => clienttranslate("End of game"),
        "type" => "manager",
        "action" => "stGameEnd",
        "args" => "argGameEnd"
    )

);