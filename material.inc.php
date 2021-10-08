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

$this->specialcards = array(
  1 => array('name' => clienttranslate("Perikles"), "description" => clienttranslate("Place two cubes in Athens.")),
  2 => array('name' => clienttranslate("Persian Fleet"), "description" => clienttranslate("Start trireme combat with one victory marker.")),
  3 => array('name' => clienttranslate("Slave Revolt"), "description" => clienttranslate("Remove one Spartan hoplite counter from the board or from the controlling player.")),
  4 => array('name' => clienttranslate("Brasidas"), "description" => clienttranslate("Double value of all Spartan hoplites in one battle.")),
  5 => array('name' => clienttranslate("Thessalanian Allies"), "description" => clienttranslate("Start hoplite combat with one victory marker.")),
  6 => array('name' => clienttranslate("Alkibiades"), "description" => clienttranslate("Move any two cubes from any city or cities to any other city or cities.")),
  7 => array('name' => clienttranslate("Phormio"), "description" => clienttranslate("Double value of all Athenian triremes in one battle.")),
  8 => array('name' => clienttranslate("Plague"), "description" => clienttranslate("Select a city. All players must remove half, rounded down, of their cubes.")),
);