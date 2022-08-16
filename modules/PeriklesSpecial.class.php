<?php

/*
 * Manage Location Tiles.
 */
class PeriklesSpecial extends APP_GameClass
{
  private $game;
  private $specialcards = [];
  
  public function __construct($game)
  {
    $this->game = $game;
    $this->specialcards["perikles"] = array('name' => clienttranslate("Perikles"), "description" => clienttranslate("Place two cubes in Athens."), "phase" => "influence_phase");
    $this->specialcards["persianfleet"] = array('name' => clienttranslate("Persian Fleet"), "description" => clienttranslate("Start trireme combat with one victory marker."), "phase" => TRIREME."_battle");
    $this->specialcards["slaverevolt"] = array('name' => clienttranslate("Slave Revolt"), "description" => clienttranslate("Remove one Spartan hoplite counter from the board or from the controlling player."), "phase" => "commit_phase");
    $this->specialcards["brasidas"] = array('name' => clienttranslate("Brasidas"), "description" => clienttranslate("Double value of all Spartan hoplites in one battle."), "phase" => HOPLITE."_battle");
    $this->specialcards["thessalanianallies"] = array('name' => clienttranslate("Thessalanian Allies"), "description" => clienttranslate("Start hoplite combat with one victory marker."), "phase" => HOPLITE."_battle");
    $this->specialcards["alkibiades"] = array('name' => clienttranslate("Alkibiades"), "description" => clienttranslate("Move any two cubes from any city or cities to any other city or cities."), "phase" => "influence_phase");
    $this->specialcards["phormio"] = array('name' => clienttranslate("Phormio"), "description" => clienttranslate("Double value of all Athenian triremes in one battle."), "phase" => TRIREME."_battle");
    $this->specialcards["plague"] = array('name' => clienttranslate("Plague"), "description" => clienttranslate("Select a city. All players must remove half, rounded down, of their cubes."), "phase" => "influence_phase");
  }

  /**
   * Assign Special tile to each player at start of game.
   */
  public function setupNewGame() {
      $spec = array_keys($this->specialcards);
      shuffle($spec);
      //   testing
      $spec = ["perikles", "slaverevolt", "brasidas", "alkibiades", "plague", "phormio", "thessalanianallies", "persianfleet"];
      $players = $this->game->loadPlayersBasicInfos();
      foreach (array_keys($players) as $player_id) {
          $tile = array_pop($spec);
          self::DbQuery("UPDATE player SET special_tile = \"$tile\" WHERE player_id=$player_id");
      }
  }

  /**
   * Get Special Tile label owned by player.
   * @param {string} player_id
   * @return {string} label
   */
  public function getSpecialTile($player_id) {
      $label = $this->game->getUniqueValueFromDB("SELECT special_tile FROM player WHERE player_id=$player_id");
      return $label;
  }

  /**
   * Get the translateable name of player's Special Tile.
   * @param {string} player_id
   * @return {string} translated name
   */
  public function getSpecialTileName($player_id) {
    $special = $this->getSpecialTile($player_id);
    return $this->specialcards[$special]['name'];
  }

 /**
 * Return a list of players who are eligible to play a special tile now.
 * Note that it only checks for correct phase, not specific battle states.
 * @return {array} may be empty
 */
  public function playersWithSpecial($phase) {
      $canplay = [];
      $playertiles = $this->game->getCollectionFromDB("SELECT player_id, special_tile FROM player WHERE special_tile_used IS NOT TRUE", true);
      foreach ($playertiles as $player_id => $tileid) {
          $playable = true;
          if ($phase == $this->specialcards[$tileid]["phase"]) {
              // slaverevolt, only "commit" Special tile, can only be played on player's turn
              if ($phase == "commit_phase") {
                  $playable = ($player_id == $this->game->getActivePlayerId());
              }
              if ($playable) {
                  $canplay[] = $player_id;
              }
          }
      }
      return $canplay;
  }

  /**
   * Can a player play a Special Tile now?
   * @return true if player_id can play a Special now
   */
  public function canPlaySpecial($player_id, $phase) {
      $players = $this->playersWithSpecial($phase);
      $canplay = in_array($player_id, $players);
      self::debug("$player_id in phase $phase canplay=$canplay");
      return $canplay;
  }

  /**
   * Player played their Special Tile. Flip it and mark it used.
   * Does not send notification.
   * @param {string} player_id
   */
  function markUsed($player_id) {
      self::DbQuery("UPDATE player SET special_tile_used=1 WHERE player_id=$player_id");
  }

    /**
     * Do a validity check and if it passes, return the Special tile belonging to this player.
     * Checks that player's Special tile has not been used, and it's the correct game state.
     * 
     * @param player_id player_id player playing the tile
     * @param tile expected tile label (optional)
     * @param combat (optional)
     * @return {array} Special Tile object [tile, used]
     */
    function checkSpecialTile($player_id, $tile = null, $combat=null) {
      $special = $this->game->getObjectFromDB("SELECT special_tile tile, special_tile_used used FROM player WHERE player_id=$player_id", true);
      // sanity check
      if ($special == null) {
          // shouldn't happen!
          throw new BgaVisibleSystemException("No special tile found"); // NOI18N
      } else if ($special['used']) {
          // shenanigans
          throw new BgaVisibleSystemException("You have already used your special tile"); // NOI18N
      }
      $label = $special['tile'];
      $phase = $this->specialcards[$label]['phase'];
      if ($phase == "trireme_battle" || $phase == "hoplite_battle") {
        if ($phase != $combat."_battle") {
            throw new BgaVisibleSystemException("Special Tile $label cannot be used during the current phase"); // NOI18N
        }
      } elseif ($this->game->getGameStateValue($phase) == 0) {
            throw new BgaVisibleSystemException("Special Tile $label cannot be used during the current phase"); // NOI18N
      }
      if ($tile != null && $label != $tile) {
          throw new BgaVisibleSystemException(sprintf("You cannot play %s", $this->specialcards[$label]['name'])); // NOI18N
      }

      return $special;
  }

}