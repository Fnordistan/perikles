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
 * perikles.view.php
 *
 * This is your "view" file.
 *
 * The method "build_page" below is called each time the game interface is displayed to a player, ie:
 * _ when the game starts
 * _ when a player refreshes the game page (F5)
 *
 * "build_page" method allows you to dynamically modify the HTML generated for the game interface. In
 * particular, you can set here the values of variables elements defined in perikles_perikles.tpl (elements
 * like {MY_VARIABLE_ELEMENT}), and insert HTML block elements (also defined in your HTML template file)
 *
 * Note: if the HTML of your game interface is always the same, you don't have to place anything here.
 *
 */
  
  require_once( APP_BASE_PATH."view/common/game.view.php" );
  
  class view_perikles_perikles extends game_view
  {
    function getGameName() {
        return "perikles";
    }
  
  	function build_page( $viewArgs ) {		
  	    // Get players & players number
        $players = $this->game->loadPlayersBasicInfos();
        $players_nbr = count( $players );

        /*********** Place your code below:  ************/
        $template = self::getGameName() . "_" . self::getGameName();

        /**** INFLUENCE BLOCK */
        $COLX = array(
          1 => 49,
          2 => 137,
          3 => 223,
          4 => 309
        );
        $ROWY = array(
          1 => 43,
          2 => 162,
          3 => 281
        );
        // COL,ROW
        $INFLUENCE_SLOTS = array(
          1 => [1,1],
          2 => [1,2],
          3 => [1,3],
          4 => [2,1],
          5 => [2,2],
          6 => [2,3],
          7 => [3,1],
          8 => [3,2],
          9 => [3,3],
          10 => [4,1],
          // pile
          0 => [4,3]
        );

        $this->page->begin_block($template, 'INFLUENCE_TILES_BLOCK');
        for ($i = 0; $i <= 10; $i++ ) {
          $x = $COLX[$INFLUENCE_SLOTS[$i][0]];
          $y = $ROWY[$INFLUENCE_SLOTS[$i][1]];
          $this->page->insert_block('INFLUENCE_TILES_BLOCK', array(
              'i' => $i,
              'L' => $x,
              'T' => $y
          ));
        }
        /**** END INFLUENCE BLOCK */


        /** BEGIN DEFEAT TOKEN SLOTS */
        $CITIES = array(
          "argos" => array(
            "defeats" => array(
              "y" => 989,
              "x" => [467, 416]
            ),
            "leader" => [445, 722],
          ),
          "athens" => array(
            "defeats" => array(
              "y" => 517,
              "x" => [1143, 1093, 1040, 989]
            ),
            "leader" => [1073, 252],
          ),
          "corinth" => array(
            "defeats" => array(
                "y" => 716,
                "x" => [258, 208, 155, 104]
              ),
              "leader" => [190, 447],
          ),
          "megara" => array(
              "defeats" => array(
                "y" => 654,
                "x" => [638, 587]
              ),
              "leader" => [616, 386],
          ),
          "sparta" => array(
              "defeats" => array(
                "y" => 1324,
                "x" => [327, 278, 225, 172]
              ),
              "leader" => [257, 1054],
          ),
          "thebes" => array(
              "defeats" => array(
                "y" => 325,
                "x" => [618, 566, 514]
              ),
              "leader" => [571, 55],
          ),
        );

        $this->page->begin_block($template, 'DEFEAT_BLOCK');
        $this->page->begin_block($template, 'CITY_BLOCK');
        $this->page->reset_subblocks('DEFEAT_BLOCK');
        foreach ($this->game->cities as $city => $cityname) {
          $this->page->reset_subblocks('DEFEAT_BLOCK');
 
          $defeats = $CITIES[$city]["defeats"];
          $defeat_slot = 1;
          foreach ($defeats["x"] as $x) {
            $this->page->insert_block('DEFEAT_BLOCK', array(
              'CITY' => $city,
              'i' => $defeat_slot,
              'L' => $x,
              'T' => $defeats["y"]
            ));
            $defeat_slot++;
          }

          $this->page->insert_block('CITY_BLOCK', array(
            'CITY' => $city,
            'LEADERX' => $CITIES[$city]["leader"][0],
            'LEADERY' => $CITIES[$city]["leader"][1]
          ));
        }

        /** END DEFEAT TOKEN SLOTS */


        /*********** Do not change anything below this line  ************/
  }
}