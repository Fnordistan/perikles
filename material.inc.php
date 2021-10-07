<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * Perikles implementation : © <Your name here> <Your email address here>
 * 
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * material.inc.php
 *
 * Perikles game material description
 *
 * Here, you can describe the material of your game with PHP variables.
 *   
 * This file is loaded in your game logic class constructor, ie these variables
 * are available everywhere in your game logic code.
 *
 */


// "influence" is number of 2-shard tiles
$this->cities = array(
    "athens" => clienttranslate("Athens"),
    "sparta" => clienttranslate("Sparta"),
    "argos" => clienttranslate("Argos"),
    "corinth" => clienttranslate("Corinth"),
    "thebes" => clienttranslate("Thebes"),
    "megara" => clienttranslate("Megara"),
);