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
  	
  	// TODO: defines your action entry points there

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
      $this->game->sendToBattle($units, $cube);
      self::ajaxResponse( );
    }

  }