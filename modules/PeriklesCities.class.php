<?php

/*
 * Manage city status and influence cubes, as well as war status.
 */
class PeriklesCities extends APP_GameClass
{
  public $game;
  private $cities = [];
  private $persia = array("persia" => array("h2" => 2, "h3" => 4, "t2" => 2, "t3" => 2));

  public function __construct($game)
  {
    $this->game = $game;

    // "influence" is number of 2-shard tiles
    // h and t is number of hoplite/trireme counters of that class
    // war is a bitmask to check for war status
    $this->cities["athens"] = array("name" => clienttranslate("Athens"), "influence" => 3, "candidate" => 2, "h1" => 2, "h2" => 2, "h3" => 2, "t1" => 2, "t2" => 2, "t3" => 2, "t4" => 2, "war" => 0b100000);
    $this->cities["sparta"] = array("name" => clienttranslate("Sparta"), "influence" => 3, "candidate" => 2, "h1" => 2, "h2" => 3, "h3" => 3, "h4" => 2, "t1" => 1, "t2" => 2, "t3" => 1, "war" => 0b010000);
    $this->cities["argos"] = array("name" => clienttranslate("Argos"), "influence" => 2, "candidate" => 2, "h1" => 2, "h2" => 2, "h3" => 2, "t1" => 1, "t2" => 1, "t3" => 1, "war" => 0b001000);
    $this->cities["corinth"] = array("name" => clienttranslate("Corinth"), "influence" => 2, "candidate" => 2, "h1" => 1, "h2" => 3, "h3" => 1, "t1" => 2, "t2" => 2, "t3" => 1, "war" => 0b000100);
    $this->cities["thebes"] = array("name" => clienttranslate("Thebes"), "influence" => 2, "candidate" => 2, "h1" => 2, "h2" => 3, "h3" => 2, "t1" => 1, "t2" => 1, "war" => 0b000010);
    $this->cities["megara"] = array("name" => clienttranslate("Megara"), "influence" => 2, "candidate" => 1, "h1" => 1, "h2" => 1, "t1" => 1, "t2" => 1, "t3" => 1, "war" => 0b000001);
  }

  /**
   * Puts starting influence cubes and military in place.
   * @params players
   */
  public function setupNewGame($players)
  {
    $this->setupInfluenceCubes($players);

    $id = 1;
    foreach($this->cities as $cn => $city) {
        $id = $this->createMilitaryUnits($cn, $city, $id);
    }
    // and add the Persians
    $cn = "persia";
    $id = $this->createMilitaryUnits($cn, $this->persia[$cn], $id);
  }

    /**
     * Initial assignment of 2 cubes per city per player.
     */
    protected function setupInfluenceCubes($players) {
      foreach($this->cities() as $cn) {
          foreach(array_keys($players) as $player_id) {
              self::DbQuery("UPDATE player SET $cn=2 WHERE player_id=$player_id");
          }
      }
  }

  /**
   * Insert units into database
   */
  protected function createMilitaryUnits($cn, $city, $idct) {
      $units = array(
          "hoplite" => "h",
          "trireme" => "t",
      );
      foreach ($units as $unit => $u) {
          $strength = 1;
          for ($i = 1; $i <= 4; $i++) {
              $unittype = $u.$i;
              if (isset($city[$unittype])) {
                  for ($t = 0; $t < $city[$unittype]; $t++) {
                      self::DbQuery( "INSERT INTO MILITARY VALUES($idct,\"$cn\",\"$unit\",$strength,\"$cn\",0)" );
                      $idct++;
                  }
              }
              $strength++;
          }
      }
      return $idct;
  }

  /**
   * Array of city names (not including Persia)
   * @return array of strings
   */
  public function cities() {
    return array_keys($this->cities);
  }

  /**
   * Get the war bitmask for this city.
   * @return a binary war bitmask
   */
  public function getWar($city) {
    return $this->cities[$city]["war"];
  }

  /**
   * For setup: get the number of 2-shard Influence tiles for this city
   * @param city name
   * @return integer
   */
  public function getInfluenceNbr($city) {
    return $this->cities[$city]["influence"];
  }

  /**
   * For setup: get the number of Candidate Influence tiles for this city
   * @param city name
   * @return integer
   */
  public function getCandidateNbr($city) {
    return $this->cities[$city]["candidate"];
  }

  /**
   * Return the clienttranslation string for the city name
   * @return translate string
   */
  public function getNameTr($city) {
    return $this->cities[$city]['name'];
  }

  /**
   * Assign a leader to a city
   * @param player_id player id
   * @param city to be leader of
   */
  public function setLeader($player_id, $city) {
    $this->game->setGameStateValue($city."_leader", $player_id);
  }

  /**
   * Get current leader of a city
   * @return player id, or 0
   */
  public function getLeader($city) {
    return $this->game->getGameStateValue($city."_leader");
  }

  /**
   * Get all cities controlled by a player.
   * @param player_id
   * @return array of cities controlled by player
   */
  public function controlledCities($player_id) {
    $controlledcities = [];
    foreach ($this->cities() as $cn) {
      if ($this->isLeader($player_id, $cn)) {
        $controlledcities[] = $cn;
      }
    }
    return $controlledcities;
  }

  /**
   * Is this player the leader of this city?
   * @param player_id
   * @param city
   * @returns true if player_id is leader of city
   */
  public function isLeader($player_id, $city) {
    return ($this->getLeader($city) == $player_id);
  }

  /**
   * Set all city leaders back to 0.
   */
  public function clearLeaders() {
    foreach ($this->cities() as $cn) {
      $this->setLeader(0, $cn);
    }
  }

  /**
    * Return associative array: city => player_id
    * Omits cities without leaders
    */
  public function getLeaders() {
      $leaders = array();
      foreach ($this->cities() as $cn) {
        $ldr = $this->getLeader($cn);
        if (!empty($ldr)) {
          $leaders[$cn] = $ldr;
        }
      }
      return $leaders;
  }

  /**
   * @param {string} city
   * @param {string} slot a/b
   * @return {string} player_id or 0
   */
  public function getCandidate($city, $slot) {
      $c_val = $city."_".$slot;
      $candidate = $this->game->getGameStateValue($c_val);
      return $candidate;
  }

  /**
   * Assign a player as a candidate.
   * @param {string} player_id
   * @param {string} city
   * @param {string} a or b
   */
  public function setCandidate($player_id, $city, $slot) {
    $c_val = $city."_".$slot;
    $this->game->setGameStateValue($c_val, $player_id);
  }

  /**
   * Clear a candidate from a city.
   * @param {string} city
   * @param {string} slot a or b
   */
  public function clearCandidate($city, $slot) {
    $this->setCandidate(0, $city, $slot);
  }

  /**
   * For a city, set both a and b candidates to 0
   * @param {string} city
   */
  public function clearCandidates($city) {
    foreach(["a", "b"] as $c) {
      $this->clearCandidate($city, $c);
    }
  }

  /**
   * Get associative array a, b => player_ids for candidates of a city.
   * Does not return empty candidates.
   * @param {string} city
   * @return {"a" => player_id, "b" => player_id}
   */
  function getCandidates($city) {
    $candidates = [];
    foreach(["a", "b"] as $c) {
      $candidate = $this->getCandidate($city, $c);
      if (!empty($candidate)) {
        $candidates[$c] = $candidate;
      }
    }
    return $candidates;
  }

    /**
     * Return associative array: "city_a" and "city_b" => player_id
     */
    function getAllCandidates() {
      $candidates = array();
      foreach ($this->cities() as $cn) {
          foreach(["a", "b"] as $c) {
            $c_val = $cn."_".$c;
            $candidate = $this->game->getGameStateValue($c_val);
            if (!empty($candidate)) {
                  $candidates[$c_val] = $candidate;
              }
          }
      }
      return $candidates;
  }

    /**
     * Influence of a player in a city.
     * @param {string} player_id
     * @param {string} city
     * @return number of cubes (not counting candidates) player has in city, may be 0
     */
  public function influence($player_id, $city) {
      return $this->game->getUniqueValueFromDB("SELECT $city FROM player WHERE player_id=$player_id");
  }

  /**
   * Get all cubes a player has in a city, including candidate cube (if any).
   * @param {string} player_id
   * @param {string} city
   * @return {integer} total cubes player has in city 
   */
  public function cubesInCity($player_id, $city) {
    $cubes = $this->influence($player_id, $city);
    if ($this->isCandidate($player_id, $city)) {
      $cubes++;
    }
    return $cubes;
  }

  /**
   * Is a player currently a candidate in a city? a or b
   * @param {string} player_id
   * @param {string} city
   * @return true if player_id has a cube in a or b in the city
   */
  public function isCandidate($player_id, $city) {
      foreach(["a", "b"] as $c) {
        if ($this->getCandidate($city, $c) == $player_id) {
          return true;
        }
      }
      return false;
  }

    /**
     * Only does the DB adjustment for influence in city. Does not send notification.
     * @param {string} city
     * @param {string} player_id
     * @param {int} cubes may be negative
     */
    function changeInfluence($city, $player_id, $cubes) {
      $influence = $this->influence($player_id, $city);
      $influence += $cubes;
      if ($influence < 0) {
          throw new BgaVisibleSystemException("Cannot reduce influence below 0");
      }
      self::DbQuery("UPDATE player SET $city = $influence WHERE player_id=$player_id");
  }


    /**
     * Does this player have any influence in the city, including a candidate?
     */
    function hasInfluence($player_id, $city) {
      if ($this->isCandidate($player_id, $city)) {
        return true;
      }
      // not a candidate so check for influence cubes
      return ($this->influence($player_id, $city) > 0);
  }

    /**
     * Can player nominate in this city?
     * @param player_id
     * @param city
     * @return true if the player_id is eligible to nominate a candidate in this city
     */ 
    function canNominate($player_id, $city) {
      $open = false;
      // are either slots open?
      foreach(["a", "b"] as $c) {
          if (empty($this->getCandidate($city, $c))) {
              $open = true;
          }
          if ($open) {
              if ($this->hasInfluence($player_id, $city)) {
                  return true;
              }
          }
      }
      return false;
  }

    /**
     * Is there any city this player can nominate in?
     */
    function canNominateAny($player_id) {
      foreach ($this->cities() as $cn) {
          if ($this->canNominate($player_id, $cn)) {
              return true;
          }
      }
      return false;
  }

    /**
     * Are all the Candidate slots filled?
     * @return true if someone is able to nominate in at least one city, otherwise false
     */
    function canAnyoneNominate() {
      $players = $this->game->loadPlayersBasicInfos();
      foreach (array_keys($players) as $player_id) {
          if ($this->canNominateAny($player_id)) {
              return true;
          }
      }
      return false;
  }

    /**
     * There is a hard limit of 30 cubes on the board per player.
     * Returns all the cubes this player has on board (including candidates)
     * @param {string} player_id
     * @return {integer} cubes this player has on board
     */
    function allCubesOnBoard($player_id) {
      $cubes = 0;
      foreach( $this->cities() as $cn ) {
        $cubes += $this->cubesInCity($player_id, $cn);
      }
      return $cubes;
  }


}