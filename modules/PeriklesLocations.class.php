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
    // Megara
    $this->locations["megara"] = array("name" => clienttranslate("Megara"), "city" => "megara", "rounds" => "TH", "vp" => 5, "intrinsic" => null);
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
   * Get the battle(s) to be fought at this location.
   * @param location
   * @return string code for battle rounds
   */
  private function getBattles($location) {
    return $this->locations[$location]['rounds'];    
  }

  /**
   * Get first or second battle for this location.
   * @param location
   * @param round 0 or 1 for first or second
   * @return HOPLITE or TRIREME or null if asked for second round and there isn't one (land battle)
   */
  public function getBattle($location, $round) {
    $battle = null;
    if ($round < 0 || $round > 1) {
        throw new BgaVisibleSystemException("invalid battle round: $round"); // NOI18N
    }
    $battles = $this->getBattles($location);
    if ($round == 0) {
        $battle = $battles[0];
    } elseif (strlen($battles) == 2) {
        $battle = $battles[1];
    }
    if ($battle != null) {
        if ($battle == "H") {
            $battle = HOPLITE;
        } elseif ($battle == "T") {
            $battle = TRIREME;
        } else {
            // should not happen!
            throw new BgaVisibleSystemException("Invalid battle type: $battle"); // NOI18N
        }
    }
    return $battle;
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
   * Get all the Location Tiles not currently in the deck (i.e. either on the board, in a player's hand, or unclaimed)
   * [id,city,battle,loc,slot]
   * @return array
   */
  public function getLocationTiles() {
    return $this->game->getObjectListFromDB("SELECT card_id id, card_type city, card_type_arg battle, card_location loc, card_location_arg slot FROM LOCATION WHERE card_location !='".DECK."'");
  }


}