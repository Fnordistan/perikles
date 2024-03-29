<?php

/*
 * Manage Location Tiles.
 */
class PeriklesLocations extends APP_GameClass
{
  private $game;
  private $locations = [];

  public function __construct($game)
  {
    $this->game = $game;
    // Athens
    $this->locations["amphipolis"] = array("name" => clienttranslate("Amphipolis"), "city" => "athens", "rounds" => "TH", "vp" => 6, "intrinsic" => "dh");
    $this->locations["lesbos"] = array("name" => clienttranslate("Lesbos"), "city" => "athens", "rounds" => "HT", "vp" => 4, "intrinsic" => "aht");
    $this->locations["plataea"] = array("name" => clienttranslate("Plataea"), "city" => "athens", "rounds" => "H", "vp" => 4, "intrinsic" => "dh");
    $this->locations["naupactus"] = array("name" => clienttranslate("Naupactus"), "city" => "athens", "rounds" => "TH", "vp" => 4, "intrinsic" => null);
    $this->locations["potidea"] = array("name" => clienttranslate("Potidea"), "city" => "athens", "rounds" => "TH", "vp" => 5, "intrinsic" => "ah");
    $this->locations["acarnania"] = array("name" => clienttranslate("Acarnania"), "city" => "athens", "rounds" => "TH", "vp" => 3, "intrinsic" => "dh");
    $this->locations["attica"] = array("name" => clienttranslate("Attica"), "city" => "athens", "rounds" => "H", "vp" => 4, "intrinsic" => null);
    // Sparta
    $this->locations["melos"] = array("name" => clienttranslate("Melos"), "city" => "sparta", "rounds" => "HT", "vp" => 3, "intrinsic" => "dht");
    $this->locations["epidaurus"] = array("name" => clienttranslate("Epidaurus"), "city" => "sparta", "rounds" => "TH", "vp" => 4, "intrinsic" => null);
    $this->locations["pylos"] = array("name" => clienttranslate("Pylos"), "city" => "sparta", "rounds" => "TH", "vp" => 4, "intrinsic" => null);
    $this->locations["sicily"] = array("name" => clienttranslate("Sicily"), "city" => "sparta", "rounds" => "TH", "vp" => 7, "intrinsic" => "dht");
    $this->locations["cephallenia"] = array("name" => clienttranslate("Cephallenia"), "city" => "sparta", "rounds" => "HT", "vp" => 4, "intrinsic" => null);
    $this->locations["cythera"] = array("name" => clienttranslate("Cythera"), "city" => "sparta", "rounds" => "HT", "vp" => 3, "intrinsic" => null);
    $this->locations["spartolus"] = array("name" => clienttranslate("Spartolus"), "city" => "sparta", "rounds" => "TH", "vp" => 4, "intrinsic" => "ah");
    // Megara - add "battle" suffix because otherwise gets confused with Megara/city location
    $this->locations["megarabattle"] = array("name" => clienttranslate("Megara"), "city" => "megara", "rounds" => "TH", "vp" => 5, "intrinsic" => null);
    // Argos
    $this->locations["mantinea"] = array("name" => clienttranslate("Mantinea"), "city" => "argos", "rounds" => "H", "vp" => 5, "intrinsic" => null);
    // Thebes
    $this->locations["delium"] = array("name" => clienttranslate("Delium"), "city" => "thebes", "rounds" => "TH", "vp" => 5, "intrinsic" => null);
    $this->locations["aetolia"] = array("name" => clienttranslate("Aetolia"), "city" => "thebes", "rounds" => "TH", "vp" => 3, "intrinsic" => null);
    // Corinth
    $this->locations["corcyra"] = array("name" => clienttranslate("Corcyra"), "city" => "corinth", "rounds" => "HT", "vp" => 3, "intrinsic" => "aht");
    $this->locations["leucas"] = array("name" => clienttranslate("Leucas"), "city" => "corinth", "rounds" => "HT", "vp" => 4, "intrinsic" => null);
    $this->locations["solygeia"] = array("name" => clienttranslate("Solygeia"), "city" => "corinth", "rounds" => "HT", "vp" => 4, "intrinsic" => null);
  }

  /**
   * Puts starting influence cubes and military in place.
   * @params players
   */
  public function setupNewGame()
  {
    $locations = $this->createLocationTiles();
    $this->game->location_tiles->createCards($locations, DECK);
    $this->game->location_tiles->shuffle(DECK);
    for ($i = 1; $i <= 7; $i++) {
        $this->game->location_tiles->pickCardForLocation(DECK, BOARD, $i);
    }
  }

    /**
     * Fill location card database.
     */
    private function createLocationTiles() {
        $locations = array();
        foreach($this->locations as $location => $tile) {
            $locations[] = array('type' => $tile['city'], 'type_arg' => $location, 'location' => DECK, 'location_arg' => 0, 'nbr' => 1);
        }
        return $locations;
    }

  /**
   * Array of location names
   * @return array of strings
   */
  public function locations() {
    return array_keys($this->locations);
  }

  /**
   * Get the translateable name string
   * @param location
   * @return string
   */
  public function getName($location) {
    return $this->locations[$location]['name'];
  }

  /**
   * Get the city that owns this tile.
   * @param location
   * @return string city
   */
  public function getCity($location) {
    return $this->locations[$location]['city'];
  }

  /**
   * Get the Victory Points value for this tile.
   * @param location
   * @return VP value
   */
  public function getVictoryPoints($location) {
    return $this->locations[$location]['vp'];
  }

  /**
   * Get the battle(s) to be fought at this location.
   * @param location
   * @return string code for battle rounds
   */
  private function getBattles($location) {
    return $this->locations[$location]['rounds'];    
  }

  /**
   * Get first or second combat for this location.
   * @param location
   * @param round 1 or 2 for first or second, or 3+ meaning battle is over
   * @return HOPLITE or TRIREME or null if asked for second round and there isn't one (land battle)
   */
  public function getCombat($location, $round) {
    $combat = null;
    if ($round == 1 || $round == 2) {
      $battles = $this->getBattles($location);
      if ($round == 1) {
          $combat = $battles[0];
      } elseif (strlen($battles) == 2) {
          $combat = $battles[1];
      }
      if ($combat != null) {
          if ($combat == "H") {
              $combat = HOPLITE;
          } elseif ($combat == "T") {
              $combat = TRIREME;
          } else {
              // should not happen!
              throw new BgaVisibleSystemException("Invalid combat type: $combat"); // NOI18N
          }
      }
    }
    return $combat;
  }

  /**
   * Is this location a land battle (Hoplite battle only)?
   * @param location
   * @return boolean true if this location is a land battle
   */
  public function isLandBattle($location) {
    return ($this->getBattles($location) == "H");
  }

  /**
   * Get militia assigned as natural attackers or defenders, if any.
   * @param location
   * @return boolean code for militia type, or null
   */
  public function getMilitia($location) {
    return $this->locations[$location]["intrinsic"];
  }

  /**
   * Does this location have intrinsic defenders?
   * @param {string} location
   * @param {string} (optional) HOPLITE, TRIREME, if null then true if either is present
   * @return true if there are intrinsic defenders of the combat type
   */
  public function hasDefendingMilitia($location, $type=null) {
    $hasDef = false;
    $militia = $this->getMilitia($location);
    if ($militia != null && $militia[0] == "d") {
        if ($type == null) {
          $hasDef = true;
        } elseif ($type == HOPLITE) {
          // all defenders include Hoplites
          $hasDef = true;
        } elseif ($type == TRIREME) {
          $hasDef = ($militia == "dht");
        } else {
          throw new BgaVisibleSystemException("Invalid unit type: $type"); // NOI18N
        }
    }
    return $hasDef;
  }

  /**
   * Get a Tile by location.
   * @param {string} location
   * @return tile array
   */
  public function getTile($location) {
    return $this->game->getObjectFromDB("SELECT card_id id, card_type city, card_type_arg location, card_location loc, card_location_arg slot FROM LOCATION WHERE card_type_arg=\"$location\"");
  }

  /**
   * Get a tile by the active battle slot
   * @param {int} slot
   * @return {string} location name of tile in slot
   */
  public function getBattleTile($slot) {
        return $this->game->getUniqueValueFromDB("SELECT card_type_arg location FROM LOCATION WHERE card_location_arg=$slot AND card_location=\"".BOARD."\"");
  }

  /**
   * Get the current slot on the battle display where a location tile is.
   * @param {string} location
   * @return {int} slot
   */
  public function getSlot($location) {
    return $this->game->getUniqueValueFromDB("SELECT card_location_arg slot FROM LOCATION WHERE card_location=$location");
  }

  /**
   * Get all locations on the board where there is a battle.
   * @param {string} city to check (optional)
   * @return {array} of tiles
   */
  public function getBattleTiles($city=null) {
      $sql = "SELECT card_type city, card_type_arg location, attacker FROM LOCATION WHERE card_location=\"".BOARD."\"";
      if (!empty($city)) {
        $sql .= " AND card_type=\"$city\"";
      }
      $locations = self::getObjectListFromDB($sql);
      return $locations;
  }

  /**
   * Get all the Location Tiles not currently in the deck (i.e. either on the board, in a player's hand, or unclaimed)
   * [id,city,battle,loc,slot]
   * @return {array}
   */
  public function getLocationTiles() {
    return $this->game->getObjectListFromDB("SELECT card_id id, card_type city, card_type_arg location, card_location loc, card_location_arg slot, persia1, persia2, persia3, persia4 FROM LOCATION WHERE card_location !='".DECK."'");
  }

  /**
   * Get associative array of locations to current permissions setting.
   * @return {array} {location => permissions}
   */
  public function getAllPermissions() {
      $permissions = $this->game->getCollectionFromDB("SELECT card_type_arg location, permissions FROM LOCATION WHERE card_location=\"".BOARD."\"", true);
      return $permissions;
  }

  /**
   * Get the permissions currently set for a location.
   * @return {string} comma-separated cities with permissions to this location, may be null
   */
  public function getPermissions($location) {
    $permissions = $this->game->getUniqueValueFromDB("SELECT permissions FROM LOCATION WHERE card_type_arg=\"$location\"");
    return $permissions;
  }

  /**
   * Set/revoke permission for a city to defend a location.
   * @param {string} location
   * @param {string} city
   * @param {bool} bPermit true to give permission, false to revoke
   */
  public function setPermission($location, $city, $bPermit) {
    if ($bPermit) {
      $this->addPermission($location, $city);
    } else {
      $this->removePermission($location, $city);
    }
  }

  /**
   * Add a new city to the list of permissions to defend a location.
   * @param {string} location
   * @param {string} city
   */
  private function addPermission($location, $city) {
    $permissions = $this->getPermissions($location);
    if ($permissions == null) {
      $permissions = $city;
    } elseif (!str_contains($permissions, $city)) {
      $permissions .= ",".$city;
    }
    self::DbQuery("UPDATE LOCATION SET permissions=\"$permissions\" WHERE card_type_arg=\"$location\"");
  }

  /**
   * Remove permission from a city to defend a location. Does not check defenders were already there, must be done in game.php.
   * @param {string} location
   * @param {string} city
   */
  private function removePermission($location, $city) {
    $permissions = $this->getPermissions($location);
    if ($permissions && str_contains($permissions, $city)) {
      $permissions = str_replace($city, '', $permissions);
      self::DbQuery("UPDATE LOCATION SET permissions=\"$permissions\" WHERE card_type_arg=\"$location\"");
    }
  }

  /**
   * Does a city have permission to defend a location?
   * @param {string} city asking for permission
   * @param {string} location
   * @return {bool} true if this player_id has permission flag set
   */
  function hasDefendPermission($city, $location) {
    $hasPerm = false;
    $permissions = $this->game->getUniqueValueFromDB("SELECT permissions FROM LOCATION WHERE card_type_arg=\"$location\"");
    if (!empty($permissions)) {
        $perms = explode(",", $permissions);
        $hasPerm = in_array($city, $perms);
    }
    return $hasPerm;
}

  /**
   * Make a player the main attacker for a location.
   * @param string player_id
   * @param string location
   */
  public function setAttacker($player_id, $location) {
    $this->setRole($player_id, "attacker", $location);
  }

    /**
   * Make a player the main defender for a location.
   * @param string player_id
   * @param string location
   */
  public function setDefender($player_id, $location) {
    $this->setRole($player_id, "defender", $location);
  }

  /**
   * Assign an attacker or defender role to a location.
   * @param string player_id
   * @param string role "attacker" or "defender"
   * @param string location
   */
  private function setRole($player_id, $role, $location) {
    self::DbQuery("UPDATE LOCATION SET $role=$player_id WHERE card_type_arg=\"$location\"");  
  }


  /**
   * Clear all permissions, end of battle/turn.
   * @param {string} id optional for specific location (all locations if null)
   */
  public function clearBattleStatus($id=null) {
    $sql = "UPDATE LOCATION SET attacker=NULL,defender=NULL,permissions=NULL";
    if ($id != null) {
      $sql .= " WHERE card_id=$id";
    }
    $this->game->DbQuery($sql);
  }

}