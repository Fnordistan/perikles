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
    define("TAKE_DEAD", 20);
    define("COMMIT_FORCES", 21);
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
    	"possibleactions" => array( "chooseCandidate" ),
    	"transitions" => array( "nextPlayer" => NEXT_PLAYER, "elections" => ELECTIONS )
    ),

    ASSASSINATE => array(
    	"name" => "assassinate",
    	"description" => clienttranslate('${actplayer} must remove 1 Influence cube'),
    	"descriptionmyturn" => clienttranslate('You must remove 1 Influence cube'),
    	"type" => "activeplayer",
    	"possibleactions" => array( "removeCube" ),
    	"transitions" => array( "useSpecial" => USE_SPECIAL, "nextPlayer" => NEXT_PLAYER )
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
        "updateGameProgression" => true,   
    	"action" => "stElections",
    	"transitions" => array( "" => COMMIT_FORCES )
    ),

    NEXT_PLAYER => array(
        "name" => "nextPlayer",
        "description" => "",
        "type" => "game",
        "action" => "stNextPlayer",
        "transitions" => array( "takeInfluence" => TAKE_INFLUENCE, "proposeCandidate" => PROPOSE_CANDIDATE )
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



