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
    define("SETUP", 1);
    define("TAKE_INFLUENCE", 2);
    define("PLACE_INFLUENCE", 3);
    define("CHOOSE_PLACE_INFLUENCE", 33);
    define("USE_SPECIAL", 4);
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
    define("START_BATTLES", 26);
    define("END_TURN", 28);
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
    	"possibleactions" => array( "takeInfluence" ),
    	"transitions" => array( "placeCube" => PLACE_INFLUENCE, "choosePlaceCube" => CHOOSE_PLACE_INFLUENCE)
    ),

    PLACE_INFLUENCE => array(
    	"name" => "placeInfluence",
    	"description" => "",
    	"type" => "game",
    	"action" => "stPlaceInfluence",
    	"transitions" => array( "assassinate" => ASSASSINATE, "candidate" => PROPOSE_CANDIDATE, "nextPlayer" => NEXT_PLAYER )
    ),

    CHOOSE_PLACE_INFLUENCE => array(
    	"name" => "choosePlaceInfluence",
    	"description" => clienttranslate('${actplayer} must choose a city to add an Influence cube'),
    	"descriptionmyturn" => clienttranslate('You must choose a city to add an Influence cube'),
    	"type" => "activeplayer",
    	"possibleactions" => array( "placeAnyCube" ),
    	"transitions" => array( "" => NEXT_PLAYER )
    ),

    PROPOSE_CANDIDATE => array(
    	"name" => "proposeCandidates",
    	"description" => clienttranslate('${actplayer} must propose a candidate'),
    	"descriptionmyturn" => clienttranslate('You must propose a candidate'),
    	"type" => "activeplayer",
    	"possibleactions" => array( "proposeCandidate" ),
    	"transitions" => array( "" => NEXT_PLAYER )
    ),

    ASSASSINATE => array(
    	"name" => "assassinate",
    	"description" => clienttranslate('${actplayer} must remove 1 Influence cube'),
    	"descriptionmyturn" => clienttranslate('You must remove 1 Influence cube'),
    	"type" => "activeplayer",
    	"possibleactions" => array( "chooseRemoveCube" ),
    	"transitions" => array( "" => NEXT_PLAYER )
    ),

    USE_SPECIAL => array(
    	"name" => "specialTile",
    	"description" => clienttranslate('${actplayer} is using a Special Tile'),
    	"descriptionmyturn" => clienttranslate('You must use your special Tile'),
    	"type" => "activeplayer",
    	"possibleactions" => array( "useSpecial" ),
    	"transitions" => array( "takeInfluence" => TAKE_INFLUENCE, "nextPlayer" => NEXT_PLAYER )
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
        "transitions" => array( "commit" => COMMIT_FORCES, "nextPlayer" => NEXT_COMMIT, "resolve" => START_BATTLES )
    ),

    COMMIT_FORCES => array(
    	"name" => "commitForces",
    	"description" => clienttranslate('${actplayer} must commit forces'),
    	"descriptionmyturn" => clienttranslate('You must commit forces'),
    	"type" => "activeplayer",
    	"possibleactions" => array( "assignUnits" ),
    	"transitions" => array( "nextPlayer" => NEXT_COMMIT)
    ),

    START_BATTLES => array(
        "name" => "resolveBattles",
        "description" => "",
        "type" => "game",
        "action" => "stResolveBattles",
        "transitions" => array( "" => 77 )
    ),

    77 => array(
        "name" => "debugstate",
    	"description" => clienttranslate('${actplayer} is in debug state'),
    	"descriptionmyturn" => clienttranslate('You are in debug state'),
    	"type" => "activeplayer",
    	"possibleactions" => array( "rollDice" ),
        "transitions" => array( "" => END_TURN )
    ),

    END_TURN => array(
        "name" => "endTurn",
        "description" => "",
        "updateGameProgression" => true,   
    	"type" => "game",
    	"action" => array( "stEndTurn" ),
        "transitions" => array( "nextTurn" => TAKE_INFLUENCE, "endGame" => SCORING )
    ),

    NEXT_PLAYER => array(
        "name" => "nextPlayer",
        "description" => "",
        "type" => "game",
        "action" => "stNextPlayer",
        "transitions" => array( "takeInfluence" => TAKE_INFLUENCE, "proposeCandidate" => PROPOSE_CANDIDATE, "elections" => ELECTIONS, "nextPlayer" => NEXT_PLAYER )
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