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

$this->specialcards = array(
  1 => array('name' => clienttranslate("Perikles"), "description" => clienttranslate("Place two cubes in Athens."), "phase" => "influence"),//INFLUENCE
  2 => array('name' => clienttranslate("Persian Fleet"), "description" => clienttranslate("Start trireme combat with one victory marker."), "phase" => TRIREME),//TRIREME
  3 => array('name' => clienttranslate("Slave Revolt"), "description" => clienttranslate("Remove one Spartan hoplite counter from the board or from the controlling player."), "phase" => "commit"),//COMMIT
  4 => array('name' => clienttranslate("Brasidas"), "description" => clienttranslate("Double value of all Spartan hoplites in one battle."), "phase" => HOPLITE),//HOPLITE
  5 => array('name' => clienttranslate("Thessalanian Allies"), "description" => clienttranslate("Start hoplite combat with one victory marker."), "phase" => HOPLITE),//HOPLITE
  6 => array('name' => clienttranslate("Alkibiades"), "description" => clienttranslate("Move any two cubes from any city or cities to any other city or cities."), "phase" => "influence"),//INFLUENCE
  7 => array('name' => clienttranslate("Phormio"), "description" => clienttranslate("Double value of all Athenian triremes in one battle."), "phase" => TRIREME),//TRIREME
  8 => array('name' => clienttranslate("Plague"), "description" => clienttranslate("Select a city. All players must remove half, rounded down, of their cubes."), "phase" => "influence"),//INFLUENCE
);

$this->combat_results_table = array(
  1 => array("odds" => "1:2", "attacker" => 10, "defender" => 5),
  2 => array("odds" => "-2", "attacker" => 9, "defender" => 6),
  3 => array("odds" => "1:1", "attacker" => 8, "defender" => 7),
  4 => array("odds" => "+2", "attacker" => 7, "defender" => 8),
  5 => array("odds" => "2:1", "attacker" => 6, "defender" => 9),
  6 => array("odds" => "3:1", "attacker" => 5, "defender" => 10),
);