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
// h and t is number of hoplite/trireme counters of that class
$this->cities = array(
    "athens" => array("name" => clienttranslate("Athens"), "influence" => 3, "candidate" => 2, "h1" => 2, "h2" => 2, "h3" => 2, "t1" => 2, "t2" => 2, "t3" => 2, "t4" => 2),
    "sparta" => array("name" => clienttranslate("Sparta"), "influence" => 3, "candidate" => 2, "h1" => 2, "h2" => 3, "h3" => 3, "h4" => 2, "t1" => 1, "t2" => 2, "t3" => 1),
    "argos" => array("name" => clienttranslate("Argos"), "influence" => 2, "candidate" => 2, "h1" => 2, "h2" => 2, "h3" => 2, "t1" => 1, "t2" => 1, "t3" => 1),
    "corinth" => array("name" => clienttranslate("Corinth"), "influence" => 2, "candidate" => 2, "h1" => 1, "h2" => 3, "h3" => 1, "t1" => 2, "t2" => 2, "t3" => 1),
    "thebes" => array("name" => clienttranslate("Thebes"), "influence" => 2, "candidate" => 2, "h1" => 2, "h2" => 3, "h3" => 2, "t1" => 1, "t2" => 1),
    "megara" => array("name" => clienttranslate("Megara"), "influence" => 2, "candidate" => 1, "h1" => 1, "h2" => 1, "t1" => 1, "t2" => 1, "t3" => 1),
);

$this->persia = array(
  "persia" => array("h2" => 2, "h3" => 4, "t2" => 2, "t3" => 2)
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

$this->locations = array(
  // Athens
  "amphipolis" => array("name" => clienttranslate("Amphipolis"), "city" => "athens", "battle" => "TH", "vp" => 6, "bonus" => "dh"),
  "lesbos" => array("name" => clienttranslate("Lesbos"), "city" => "athens", "battle" => "HT", "vp" => 4, "bonus" => "aht"),
  "plataea" => array("name" => clienttranslate("Plataea"), "city" => "athens", "battle" => "H", "vp" => 4, "bonus" => "dh"),
  "naupactus" => array("name" => clienttranslate("Naupactus"), "city" => "athens", "battle" => "TH", "vp" => 4, "bonus" => null),
  "potidea" => array("name" => clienttranslate("Potidea"), "city" => "athens", "battle" => "TH", "vp" => 5, "bonus" => "ah"),
  "acarnania" => array("name" => clienttranslate("Acarnania"), "city" => "athens", "battle" => "TH", "vp" => 3, "bonus" => "dh"),
  "attica" => array("name" => clienttranslate("Attica"), "city" => "athens", "battle" => "H", "vp" => 4, "bonus" => null),
  // Sparta
  "melos" => array("name" => clienttranslate("Melos"), "city" => "sparta", "battle" => "HT", "vp" => 3, "bonus" => "dht"),
  "epidaurus" => array("name" => clienttranslate("Epidaurus"), "city" => "sparta", "battle" => "TH", "vp" => 4, "bonus" => null),
  "pylos" => array("name" => clienttranslate("Pylos"), "city" => "sparta", "battle" => "TH", "vp" => 4, "bonus" => null),
  "sicily" => array("name" => clienttranslate("Sicily"), "city" => "sparta", "battle" => "TH", "vp" => 7, "bonus" => "dht"),
  "cephallenia" => array("name" => clienttranslate("Cephallenia"), "city" => "sparta", "battle" => "HT", "vp" => 4, "bonus" => null),
  "cythera" => array("name" => clienttranslate("Cythera"), "city" => "sparta", "battle" => "HT", "vp" => 3, "bonus" => null),
  "spartolus" => array("name" => clienttranslate("Spartolus"), "city" => "sparta", "battle" => "TH", "vp" => 4, "bonus" => "ah"),
  // Megara
  "megara" => array("name" => clienttranslate("Megara"), "city" => "megara", "battle" => "TH", "vp" => 5, "bonus" => null),
  // Argos
  "mantinea" => array("name" => clienttranslate("Mantinea"), "city" => "argos", "battle" => "H", "vp" => 5, "bonus" => null),
  // Thebes
  "delium" => array("name" => clienttranslate("Delium"), "city" => "thebes", "battle" => "TH", "vp" => 5, "bonus" => null),
  "aetolia" => array("name" => clienttranslate("Aetolia"), "city" => "thebes", "battle" => "TH", "vp" => 3, "bonus" => null),
  // Corinth
  "corcyra" => array("name" => clienttranslate("Corcyra"), "city" => "corinth", "battle" => "HT", "vp" => 3, "bonus" => "aht"),
  "leucas" => array("name" => clienttranslate("Leucas"), "city" => "corinth", "battle" => "HT", "vp" => 4, "bonus" => null),
  "solygeia" => array("name" => clienttranslate("Solygeia"), "city" => "corinth", "battle" => "HT", "vp" => 4, "bonus" => null),
);