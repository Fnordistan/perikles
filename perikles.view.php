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
  	function build_page( $viewArgs )
  	{		
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
        $CORINTH_DEFEAT_Y = 716;
        $CORINTH_DEFEAT_X = [104, 155, 208, 258];
        $corinth_defeat_slot = 1;
        $this->page->begin_block($template, 'CORINTH_DEFEAT_BLOCK');
        foreach ($CORINTH_DEFEAT_X as $x) {
          $this->page->insert_block('CORINTH_DEFEAT_BLOCK', array(
            'i' => $corinth_defeat_slot,
            'L' => $x,
            'T' => $CORINTH_DEFEAT_Y
          ));
          $corinth_defeat_slot++;
        }

        /** END DEFEAT TOKEN SLOTS */


        /*********** Do not change anything below this line  ************/
  	}
  }
  

