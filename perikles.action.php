<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * Perikles implementation : © <David Edelstein> <david.edelstein@gmail.com>
 *
 * This code has been produced on the BGA studio platform for use on https://boardgamearena.com.
 * See http://en.doc.boardgamearena.com/Studio for more information.
 * -----
 * 
 * perikles.action.php
 *
 * Perikles main action entry point
 *
 *
 * In this file, you are describing all the methods that can be called from your
 * user interface logic (javascript).
 *       
 * If you define a method "myAction" here, then you can call it from your javascript code with:
 * this.ajaxcall( "/perikles/perikles/myAction.html", ...)
 *
 */
  
  
class action_perikles extends APP_GameAction
{ 
    // Constructor: please do not modify
   	public function __default()
  	{
  	    if( self::isArg( 'notifwindow') )
  	    {
            $this->view = "common_notifwindow";
  	        $this->viewArgs['table'] = self::getArg( "table", AT_posint, true );
  	    }
  	    else
  	    {
            $this->view = "perikles_perikles";
            self::trace( "Complete reinitialization of board game" );
      }
  	} 
  	
    /**
     * When player takes an Influence tile.
     */
    public function takeinfluence() {
        self::setAjaxMode();     
        $influence_id = self::getArg( "id", AT_posint, true );
        $this->game->takeInfluence($influence_id);
        self::ajaxResponse( );
    }

    /**
     * When player using Any card clicks a city to add a cube to.
     */
    public function placecube() {
      self::setAjaxMode();     
      $city = self::getArg( "city", AT_alphanum, true );
      $this->game->placeAnyCube($city);
      self::ajaxResponse( );
    }

    /**
     * Player chooses city and player to add Candidate.
     */
    public function selectcandidate() {
      self::setAjaxMode();     
      $city = self::getArg( "city", AT_alphanum, true );
      $player_id = self::getArg( "player", AT_alphanum, true );
      $this->game->proposeCandidate($city, $player_id);
      self::ajaxResponse( );
    }

    /**
     * Player chooses a cube to remove.
     */
    public function removecube() {
      self::setAjaxMode();     
      $city = self::getArg( "city", AT_alphanum, true );
      $player_id = self::getArg( "player", AT_alphanum, true );
      $cube = self::getArg( "cube", AT_alphanum, true );
      $this->game->chooseRemoveCube($player_id, $city, $cube);
      self::ajaxResponse( );
    }

    /**
     * Spartan player choose first player to go.
     */
    public function chooseplayer() {
      self::setAjaxMode();     
      $player_id = self::getArg( "player", AT_alphanum, true );
      $this->game->chooseNextPlayer($player_id);
      self::ajaxResponse( );
    }

    /**
     * Send all the units committed to battles.
     */
    public function commitUnits() {
      self::setAjaxMode();     
      $units = self::getArg( "units", AT_alphanum, true );
      $cube = self::getArg( "cube", AT_alphanum, true );
      $this->game->assignUnits($units, $cube);
      self::ajaxResponse( );
    }

    /**
     * Player selected unit to lose in battle
     */
    public function selectcasualty() {
      self::setAjaxMode();     
      $city = self::getArg( "city", AT_alphanum, true );
      $this->game->chooseLoss($city);
      self::ajaxResponse( );
    }

    public function selectdeadpool() {
      self::setAjaxMode();     
      $city = self::getArg( "city", AT_alphanum, true );
      $type = self::getArg( "type", AT_alphanum, true );
      $this->game->chooseDeadpool($city, $type);
      self::ajaxResponse( );
   }

    /**
     * Player passes on a Special Tile.
     */
    public function passspecialtile() {
      self::setAjaxMode();     
      $this->game->specialTilePass();
      self::ajaxResponse( );
    }

    /**
     * Player clicked the Perikles special tile button.
     */
    public function perikles() {
      self::setAjaxMode();     
      $this->game->playPerikles();
      self::ajaxResponse( );
    }

    /**
     * Player plays Special Tile during battle or declines
     */
    public function specialBattleTile() {
      self::setAjaxMode();     
      $player_id = self::getArg( "player", AT_alphanum, true );
      $use = self::getArg( "use", AT_bool, true );
      $side = self::getArg( "side", AT_alphanum, false );
      $this->game->useSpecialBattleTile($player_id, $use, $side);
      self::ajaxResponse( );
    }

    /**
     * Player chose cubes to move with Alkibiades.
     */
    public function alkibiades() {
      self::setAjaxMode();
      $player1 = self::getArg( "player1", AT_alphanum, true );
      $player2 = self::getArg( "player2", AT_alphanum, true );
      $from1 = self::getArg( "from1", AT_alphanum, true );
      $from2 = self::getArg( "from2", AT_alphanum, true );
      $to1 = self::getArg( "to1", AT_alphanum, true );
      $to2 = self::getArg( "to2", AT_alphanum, true );
      $this->game->playAlkibiades($player1, $from1, $to1, $player2, $from2, $to2);
      self::ajaxResponse( );
  }

  /**
     * Player chose a city for Plague
     */
    public function plague() {
      self::setAjaxMode();     
      $city = self::getArg( "city", AT_alphanum, true );
      $this->game->playPlague($city);
      self::ajaxResponse( );
    }

  /**
     * Player chose a stack for Slave Revolt
     */
    public function slaverevolt() {
      self::setAjaxMode();     
      $location = self::getArg( "location", AT_alphanum, true );
      $this->game->playSlaveRevolt($location);
      self::ajaxResponse( );
    }

    /**
     * Player send/revokes permission to defend a location.
     */
    public function setdefender() {
      self::setAjaxMode();     
      $location = self::getArg( "location", AT_alphanum, true );
      $defender = self::getArg( "defender", AT_alphanum, true );
      $bDefend =  self::getArg( "defend", AT_bool, true );
      $this->game->giveDefendPermission($location, $defender, $bDefend);
      self::ajaxResponse( );
    }
}