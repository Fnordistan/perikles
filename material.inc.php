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

$this->ordinals = array(
        1 => clienttranslate("first"),
        2 => clienttranslate("second"),
        3 => clienttranslate("third"),
        4 => clienttranslate("fourth"),
        5 => clienttranslate("fifth"),
        6 => clienttranslate("sixth"),
        7 => clienttranslate("seventh"),
);