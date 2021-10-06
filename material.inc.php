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



$this->cities = array(
    "athens" => array('name' => clienttranslate("Athens")),
    "sparta" => array('name' => clienttranslate("Sparta")),
    "argos" => array('name' => clienttranslate("Argos")),
    "corinth" => array('name' => clienttranslate("Corinth")),
    "thebes" => array('name' => clienttranslate("Thebes")),
    "megara" => array('name' => clienttranslate("Megara")),
);