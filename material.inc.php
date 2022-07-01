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
// war is a bitmask to check for war status
$this->cities = array(
    "athens" => array("name" => clienttranslate("Athens"), "influence" => 3, "candidate" => 2, "h1" => 2, "h2" => 2, "h3" => 2, "t1" => 2, "t2" => 2, "t3" => 2, "t4" => 2, "war" => 0b100000),
    "sparta" => array("name" => clienttranslate("Sparta"), "influence" => 3, "candidate" => 2, "h1" => 2, "h2" => 3, "h3" => 3, "h4" => 2, "t1" => 1, "t2" => 2, "t3" => 1, "war" => 0b010000),
    "argos" => array("name" => clienttranslate("Argos"), "influence" => 2, "candidate" => 2, "h1" => 2, "h2" => 2, "h3" => 2, "t1" => 1, "t2" => 1, "t3" => 1, "war" => 0b001000),
    "corinth" => array("name" => clienttranslate("Corinth"), "influence" => 2, "candidate" => 2, "h1" => 1, "h2" => 3, "h3" => 1, "t1" => 2, "t2" => 2, "t3" => 1, "war" => 0b000100),
    "thebes" => array("name" => clienttranslate("Thebes"), "influence" => 2, "candidate" => 2, "h1" => 2, "h2" => 3, "h3" => 2, "t1" => 1, "t2" => 1, "war" => 0b000010),
    "megara" => array("name" => clienttranslate("Megara"), "influence" => 2, "candidate" => 1, "h1" => 1, "h2" => 1, "t1" => 1, "t2" => 1, "t3" => 1, "war" => 0b000001),
);

$this->persia = array(
  "persia" => array("h2" => 2, "h3" => 4, "t2" => 2, "t3" => 2)
);

$this->specialcards = array(
  1 => array('name' => clienttranslate("Perikles"), "description" => clienttranslate("Place two cubes in Athens."), "phase" => "influence"),//INFLUENCE
  2 => array('name' => clienttranslate("Persian Fleet"), "description" => clienttranslate("Start trireme combat with one victory marker."), "phase" => "trireme"),//TRIREME
  3 => array('name' => clienttranslate("Slave Revolt"), "description" => clienttranslate("Remove one Spartan hoplite counter from the board or from the controlling player."), "phase" => "commit"),//COMMIT
  4 => array('name' => clienttranslate("Brasidas"), "description" => clienttranslate("Double value of all Spartan hoplites in one battle."), "phase" => "hoplite"),//HOPLITE
  5 => array('name' => clienttranslate("Thessalanian Allies"), "description" => clienttranslate("Start hoplite combat with one victory marker."), "phase" => "hoplite"),//HOPLITE
  6 => array('name' => clienttranslate("Alkibiades"), "description" => clienttranslate("Move any two cubes from any city or cities to any other city or cities."), "phase" => "influence"),//INFLUENCE
  7 => array('name' => clienttranslate("Phormio"), "description" => clienttranslate("Double value of all Athenian triremes in one battle."), "phase" => "trireme"),//TRIREME
  8 => array('name' => clienttranslate("Plague"), "description" => clienttranslate("Select a city. All players must remove half, rounded down, of their cubes."), "phase" => "influence"),//INFLUENCE
);

$this->locations = array(
  // Athens
  "amphipolis" => array("name" => clienttranslate("Amphipolis"), "city" => "athens", "rounds" => "TH", "vp" => 6, "intrinsic" => "dh"),
  "lesbos" => array("name" => clienttranslate("Lesbos"), "city" => "athens", "rounds" => "HT", "vp" => 4, "intrinsic" => "aht"),
  "plataea" => array("name" => clienttranslate("Plataea"), "city" => "athens", "rounds" => "H", "vp" => 4, "intrinsic" => "dh"),
  "naupactus" => array("name" => clienttranslate("Naupactus"), "city" => "athens", "rounds" => "TH", "vp" => 4, "intrinsic" => null),
  "potidea" => array("name" => clienttranslate("Potidea"), "city" => "athens", "rounds" => "TH", "vp" => 5, "intrinsic" => "ah"),
  "acarnania" => array("name" => clienttranslate("Acarnania"), "city" => "athens", "rounds" => "TH", "vp" => 3, "intrinsic" => "dh"),
  "attica" => array("name" => clienttranslate("Attica"), "city" => "athens", "rounds" => "H", "vp" => 4, "intrinsic" => null),
  // Sparta
  "melos" => array("name" => clienttranslate("Melos"), "city" => "sparta", "rounds" => "HT", "vp" => 3, "intrinsic" => "dht"),
  "epidaurus" => array("name" => clienttranslate("Epidaurus"), "city" => "sparta", "rounds" => "TH", "vp" => 4, "intrinsic" => null),
  "pylos" => array("name" => clienttranslate("Pylos"), "city" => "sparta", "rounds" => "TH", "vp" => 4, "intrinsic" => null),
  "sicily" => array("name" => clienttranslate("Sicily"), "city" => "sparta", "rounds" => "TH", "vp" => 7, "intrinsic" => "dht"),
  "cephallenia" => array("name" => clienttranslate("Cephallenia"), "city" => "sparta", "rounds" => "HT", "vp" => 4, "intrinsic" => null),
  "cythera" => array("name" => clienttranslate("Cythera"), "city" => "sparta", "rounds" => "HT", "vp" => 3, "intrinsic" => null),
  "spartolus" => array("name" => clienttranslate("Spartolus"), "city" => "sparta", "rounds" => "TH", "vp" => 4, "intrinsic" => "ah"),
  // Megara
  "megara" => array("name" => clienttranslate("Megara"), "city" => "megara", "rounds" => "TH", "vp" => 5, "intrinsic" => null),
  // Argos
  "mantinea" => array("name" => clienttranslate("Mantinea"), "city" => "argos", "rounds" => "H", "vp" => 5, "intrinsic" => null),
  // Thebes
  "delium" => array("name" => clienttranslate("Delium"), "city" => "thebes", "rounds" => "TH", "vp" => 5, "intrinsic" => null),
  "aetolia" => array("name" => clienttranslate("Aetolia"), "city" => "thebes", "rounds" => "TH", "vp" => 3, "intrinsic" => null),
  // Corinth
  "corcyra" => array("name" => clienttranslate("Corcyra"), "city" => "corinth", "rounds" => "HT", "vp" => 3, "intrinsic" => "aht"),
  "leucas" => array("name" => clienttranslate("Leucas"), "city" => "corinth", "rounds" => "HT", "vp" => 4, "intrinsic" => null),
  "solygeia" => array("name" => clienttranslate("Solygeia"), "city" => "corinth", "rounds" => "HT", "vp" => 4, "intrinsic" => null),
);

$this->combat_results_table = array(
  1 => array("odds" => "1:2", "attacker" => 10, "defender" => 5),
  2 => array("odds" => "-2", "attacker" => 9, "defender" => 6),
  3 => array("odds" => "1:1", "attacker" => 8, "defender" => 7),
  4 => array("odds" => "+2", "attacker" => 7, "defender" => 8),
  5 => array("odds" => "2:1", "attacker" => 6, "defender" => 9),
  6 => array("odds" => "3:1", "attacker" => 5, "defender" => 10),
);