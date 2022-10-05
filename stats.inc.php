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
 * stats.inc.php
 *
 * Perikles game statistics description
 *
 */

/*
    In this file, you are describing game statistics, that will be displayed at the end of the
    game.
    
    !! After modifying this file, you must use "Reload  statistics configuration" in BGA Studio backoffice
    ("Control Panel" / "Manage Game" / "Your Game")
    
    There are 2 types of statistics:
    _ table statistics, that are not associated to a specific player (ie: 1 value for each game).
    _ player statistics, that are associated to each players (ie: 1 value for each player in the game).

    Statistics types can be "int" for integer, "float" for floating point values, and "bool" for boolean
    
    Once you defined your statistics there, you can start using "initStat", "setStat" and "incStat" method
    in your game logic, using statistics names defined below.
    
    !! It is not a good idea to modify this file when a game is running !!

    If your game is already public on BGA, please read the following before any change:
    http://en.doc.boardgamearena.com/Post-release_phase#Changes_that_breaks_the_games_in_progress
    
    Notes:
    * Statistic index is the reference used in setStat/incStat/initStat PHP method
    * Statistic index must contains alphanumerical characters and no space. Example: 'turn_played'
    * Statistics IDs must be >=10
    * Two table statistics can't share the same ID, two player statistics can't share the same ID
    * A table statistic can have the same ID than a player statistics
    * Statistics ID is the reference used by BGA website. If you change the ID, you lost all historical statistic data. Do NOT re-use an ID of a deleted statistic
    * Statistic name is the English description of the statistic as shown to players
    
*/

$stats_type = array(

    // Statistics global to table
    "table" => array(

        "turns_number" => array(
            "id"=> 10,
            "name" => totranslate("Number of turns"),
            "type" => "int"
        ),
        "unclaimed_tiles" => array(
            "id"=> 11,
            "name" => totranslate("Unclaimed Tiles"),
            "type" => "int"
        ),
    ),
    
    // Statistics existing for each player
    "player" => array(
        "argos_statues" => array(
            "id"=> 10,
            "name" => totranslate("Argos Statues"),
            "type" => "int"
        ),
        "athens_statues" => array(
            "id"=> 11,
            "name" => totranslate("Athens Statues"),
            "type" => "int"
        ),
        "corinth_statues" => array(
            "id"=> 12,
            "name" => totranslate("Corinth Statues"),
            "type" => "int"
        ),
        "megara_statues" => array(
            "id"=> 13,
            "name" => totranslate("Megara Statues"),
            "type" => "int"
        ),
        "sparta_statues" => array(
            "id"=> 14,
            "name" => totranslate("Sparta Statues"),
            "type" => "int"
        ),
        "thebes_statues" => array(
            "id"=> 15,
            "name" => totranslate("Thebes Statues"),
            "type" => "int"
        ),
        "persian_leader" => array(
            "id"=> 16,
            "name" => totranslate("Persian Leader"),
            "type" => "int"
        ),
        "victory_tiles" => array(
            "id"=> 17,
            "name" => totranslate("Claimed Location Tiles"),
            "type" => "int"
        ),
        "victory_tile_points" => array(
            "id"=> 18,
            "name" => totranslate("VPs from Location Tiles"),
            "type" => "int"
        ),
        "statue_points" => array(
            "id"=> 19,
            "name" => totranslate("VPs from Statues"),
            "type" => "int"
        ),
        "cube_points" => array(
            "id"=> 20,
            "name" => totranslate("VPs from Cubes"),
            "type" => "int"
        ),
        "battles_won_attacker" => array(
            "id"=> 21,
            "name" => totranslate("Battles won (as Main Attacker)"),
            "type" => "int"
        ),
        "battles_won_defender" => array(
            "id"=> 22,
            "name" => totranslate("Battles won (as Main Defender)"),
            "type" => "int"
        ),
        "battles_lost_attacker" => array(
            "id"=> 23,
            "name" => totranslate("Battles lost (as Main Attacker)"),
            "type" => "int"
        ),
        "battles_lost_defender" => array(
            "id"=> 24,
            "name" => totranslate("Battles lost (as Main Defender)"),
            "type" => "int"
        ),
        "special_tile" => array(
            "id"=> 25,
            "name" => totranslate("Special Tile"),
            "type" => "int"
        ),
    ),

    "value_labels" => array(
		18 => array( 
			0 => totranslate("Perikles"),
			1 => totranslate("Persian Fleet"), 
			2 => totranslate("Slave Revolt"), 
			3 => totranslate("Brasidas"), 
			4 => totranslate("Thessalanian Allies"), 
			5 => totranslate("Alkibiades"), 
			6 => totranslate("Phormio"), 
			7 => totranslate("Plague"),
        )
    )


);
