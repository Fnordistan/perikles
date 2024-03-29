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
      $this->tpl['PERSIA_X'] = 72;
      $this->tpl['PERSIA_Y'] = 888;
      $this->tpl['DEADPOOL'] = self::_("Dead Pool");
      $this->tpl['MILITARY'] = self::_("My Military Units");
      $this->tpl['PLAYER_OPTIONS'] = self::_("Player Options");
      $this->tpl['AUTOPASS'] = self::_("Special Tile: Automatically pass");

      // Get players & players number
        $players = $this->game->loadPlayersBasicInfos();
        // $players_nbr = count( $players );

        /*********** Place your code below:  ************/
        $template = self::getGameName() . "_" . self::getGameName();

        /**** INFLUENCE BLOCK */
        $COLX = array(
          1 => 53,
          2 => 141,
          3 => 227,
          4 => 313
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


        /** BEGIN CITY BLOCK */
        $CITIES = array(
          "argos" => array(
            "xy" => [308, 769],
            "defeats" => array(
              "y" => 989,
              "x" => [467, 416]
            ),
            "leader" => [445, 722],
            "statues" => [317, 871],
            "military" => [566, 846],
            "alpha" => [439, 799],
            "beta" => [478, 799],
            "cubes" => [390, 965],
          ),
          "athens" => array(
            "xy" => [880, 295],
            "defeats" => array(
              "y" => 517,
              "x" => [1143, 1093, 1040, 989]
            ),
            "leader" => [1073, 252],
            "statues" => [943, 399],
            "military" => [1188, 374],
            "alpha" => [1061, 327],
            "beta" => [1100, 327],
            "cubes" => [1016, 490],
          ),
          "corinth" => array(
            "xy" => [111, 493],
            "defeats" => array(
                "y" => 716,
                "x" => [258, 208, 155, 104]
              ),
              "leader" => [190, 447],
              "statues" => [59, 594],
              "military" => [306, 570],
              "alpha" => [180, 521],
              "beta" => [219, 521],
              "cubes" => [133, 687],
          ),
          "megara" => array(
            "xy" => [478, 433],
            "defeats" => array(
                "y" => 654,
                "x" => [638, 587]
              ),
              "leader" => [616, 386],
              "statues" => [488, 531],
              "military" => [736, 510],
              "alpha" => [609, 463],
              "beta" => [648, 463],
              "cubes" => [560, 629],
              ),
          "sparta" => array(
            "xy" => [57, 1101],
            "defeats" => array(
                "y" => 1324,
                "x" => [327, 278, 225, 172]
              ),
              "leader" => [257, 1054],
              "statues" => [127, 1199],
              "military" => [376, 1176],
              "alpha" => [247, 1129],
              "beta" => [286, 1129],
              "cubes" => [202, 1295],
              ),
          "thebes" => array(
            "xy" => [455, 103],
            "defeats" => array(
                "y" => 325,
                "x" => [618, 566, 514]
              ),
              "leader" => [571, 55],
              "statues" => [439, 200],
              "military" => [687, 176],
              "alpha" => [562, 131],
              "beta" => [601,131],
              "cubes" => [516, 295],
            ),
        );

        $this->page->begin_block($template, 'DEFEAT_BLOCK');
        $this->page->begin_block($template, 'CUBES_BLOCK');
        $this->page->begin_block($template, 'CITY_BLOCK');
        foreach ($this->game->Cities->cities() as $city) {
          $this->page->reset_subblocks('DEFEAT_BLOCK');
          $this->page->reset_subblocks('CUBES_BLOCK');
 
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

          $COL_GAP = 33;
          $COL_HEIGHT = 124;
          $col = 0;
          foreach ($players as $player_id => $player) {
            $this->page->insert_block('CUBES_BLOCK', array(
              'CITY' => $city,
              'player_id' => $player_id,
              'L' => $CITIES[$city]["cubes"][0] + ($COL_GAP*$col) - $CITIES[$city]["xy"][0],
              'T' => $CITIES[$city]["cubes"][1] - $COL_HEIGHT - $CITIES[$city]["xy"][1],
            ));
            $col++;
          }


          $this->page->insert_block('CITY_BLOCK', array(
            'CITY' => $city,
            'LEADERX' => $CITIES[$city]["leader"][0],
            'LEADERY' => $CITIES[$city]["leader"][1],
            'STATUEX' => $CITIES[$city]["statues"][0],
            'STATUEY' =>$CITIES[$city]["statues"][1],
            'MILX' => $CITIES[$city]["military"][0],
            'MILY' => $CITIES[$city]["military"][1],
            'ALPHAX' => $CITIES[$city]["alpha"][0],
            'ALPHAY' => $CITIES[$city]["alpha"][1],
            'BETAX' => $CITIES[$city]["beta"][0],
            'BETAY' => $CITIES[$city]["beta"][1],
            'CITYY' => $CITIES[$city]["xy"][1],
            'CITYX' => $CITIES[$city]["xy"][0],
          ));
        }

        // LOCATION BLOCK
        $this->page->begin_block($template, 'LOCATION_BLOCK');
        $LOC_Y = 676;
        $LOC_H = 115;
        for ($l = 0; $l < 7; $l++) {
            $this->page->insert_block('LOCATION_BLOCK', array(
              'LOC' => $l+1,
              'T' => $LOC_Y + ($l*$LOC_H)-$l,
              'TT' => $LOC_Y + ($l*$LOC_H)-$l+55,
              'L' => 1000,
              'LA' => 899,
              'LAA' => 802,
              'LD' => 1078,
              'LDA' => 1174,
            ));
        }

        // CRT BLOCK
        $this->page->begin_block($template, 'CRT_BLOCK');
        $CRTX = [966, 1012, 1056, 1101, 1145, 1189];
        $CRTY = 58;
        $c = 1;
        foreach ($CRTX as $crtx) {
          $this->page->insert_block('CRT_BLOCK', array(
              'COL' => $c++,
              'CRTY' => $CRTY,
              'CRTX' => $crtx,
          ));
        }

        /*********** Do not change anything below this line  ************/
  }
}