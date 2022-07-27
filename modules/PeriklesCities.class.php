<?php

DEFINE("WAR", -1);
DEFINE("ALLIED", 1);

/*
 * Manage city status and influence cubes, as well as war status.
 */
class PeriklesCities extends APP_GameClass
{
  private $game;
  private $cities = [];
  private $persia = array(PERSIA => array("h2" => 2, "h3" => 4, "t2" => 2, "t3" => 2));

  public function __construct($game)
  {
    $this->game = $game;

    // "influence" is number of 2-shard tiles
    // candidate is number of Candidate tiles
    // h and t is number of hoplite/trireme counters of that class
    // war is a bitmask to check for war status
    $this->cities["athens"] = array(
      "name" => clienttranslate("Athens"),
       "influence" => 3,
       "candidate" => 2,
       "h1" => 2, "h2" => 2, "h3" => 2, "t1" => 2, "t2" => 2, "t3" => 2, "t4" => 2);
    $this->cities["sparta"] = array(
      "name" => clienttranslate("Sparta"),
      "influence" => 3,
      "candidate" => 2,
      "h1" => 2, "h2" => 3, "h3" => 3, "h4" => 2, "t1" => 1, "t2" => 2, "t3" => 1);
    $this->cities["argos"] = array(
      "name" => clienttranslate("Argos"),
      "influence" => 2,
      "candidate" => 2,
      "h1" => 2, "h2" => 2, "h3" => 2, "t1" => 1, "t2" => 1, "t3" => 1);
    $this->cities["corinth"] = array(
      "name" => clienttranslate("Corinth"),
      "influence" => 2,
      "candidate" => 2,
      "h1" => 1, "h2" => 3, "h3" => 1, "t1" => 2, "t2" => 2, "t3" => 1);
    $this->cities["thebes"] = array(
      "name" => clienttranslate("Thebes"),
      "influence" => 2,
      "candidate" => 2,
      "h1" => 2, "h2" => 3, "h3" => 2, "t1" => 1, "t2" => 1);
    $this->cities["megara"] = array(
      "name" => clienttranslate("Megara"),
      "influence" => 2,
      "candidate" => 1,
      "h1" => 1, "h2" => 1, "t1" => 1, "t2" => 1, "t3" => 1);
  }

  /**
   * Puts starting influence cubes and military in place.
   * @params players
   */
  public function setupNewGame()
  {
    $this->setupInfluenceCubes();
    $this->initializeWars();

    $id = 1;
    foreach($this->cities as $cn => $city) {
        $id = $this->createMilitaryUnits($cn, $city, $id);
    }
    // and add the Persians
    $cn = PERSIA;
    $id = $this->createMilitaryUnits($cn, $this->persia[$cn], $id);
  }

  /**
   * Convenience function to load player_ids
   * @return {array} player ids
   */
  private function getPlayerIds() {
    $players = $this->game->loadPlayersBasicInfos();
    return array_keys($players);
  }

  /**
   * Initial assignment of 2 cubes per city per player.
   */
  private function setupInfluenceCubes() {
    foreach($this->cities() as $cn) {
        foreach($this->getPlayerIds() as $player_id) {
            self::DbQuery("UPDATE player SET $cn=2 WHERE player_id=$player_id");
        }
    }
  }

  /**
   * Set up all the war flags.
   */
  private function initializeWars() {
    $citystates = $this->cities();
    $citystates[] = PERSIA;
    $i = 1;
    foreach($citystates as $c) {
        self::DbQuery( "INSERT INTO WARS VALUES($i,\"$c\",0,0,0,0,0,0,0)" );
        $this->setAlly($c, $c);
        $i++;
    }
  }

  /**
   * At end of battle phase, reset all relationships.
   */
  public function clearWars() {
    $citystates = $this->cities();
    $citystates[] = PERSIA;
    foreach($citystates as $city1) {
      foreach($citystates as $city2) {
        if ($city1 != $city2) {
          $this->setRelationship($city1, $city2, 0);
        }
      }
    }
  }

  /**
   * Set two cities to WAR.
   * @param {string} city1
   * @param {string} city2
   */
  public function setWar($city1, $city2) {
    $this->setRelationship($city1, $city2, WAR);
  }

  /**
   * Set two cities to ALLIED.
   * @param {string} city1
   * @param {string} city2
   */
  public function setAlly($city1, $city2) {
    $this->setRelationship($city1, $city2, ALLIED);
  }

  /**
   * Are two cities at war?
   * @param {string} city1
   * @param {string} city2
   * @return {bool} true if city1 is at war with city2
   */
  public function atWar($city1, $city2) {
    return ($this->getRelationship($city1, $city2) == WAR);
  }

  /**
   * Are two cities allies?
   * @param {string} city1
   * @param {string} city2
   * @return {bool} true if city1 is allied with city2
   */
  public function isAlly($city1, $city2) {
    return ($this->getRelationship($city1, $city2) == ALLIED);
  }

  /**
   * Check whether a player can send a unit of a city to attack another.
   * Checks leadership and alliance status.
   * @param {string} player_id unit owner
   * @param {string} attcity city of the attacking unit
   * @param {string} defcity city of the defending location
   * @param {string} location
   * @return boolean true if attacker passes checks to attack defender
   */
  public function canAttack($player_id, $attcity, $defcity, $location) {
      if ($attcity == $defcity) {
        return false;
      }
      if ($this->isLeader($player_id, $defcity)) {
        return false;
      }
      // make sure we aren't already allied with any of the defenders
      $defenders = $this->getAllDefenders($location, $defcity);
      foreach($defenders as $defender) {
        if ($this->isAlly($attcity, $defender)) {
          return false;
        }
      }
      // is anyone else attacking this city?
      $attackers = $this->getAllAttackers($location);
      // make sure we aren't already at war with one of the other attackers
      foreach ($attackers as $attacker) {
        if ($this->atWar($attcity, $attacker)) {
          return false;
        }
      }

    return true;
  }

  /**
   * Get a list of all cities attacking at a battle tile.
   * @param {string} location
   */
  public function getAllAttackers($location) {
    return $this->getCitiesOnSide($location, ATTACKER);
  }

  /**
   * Get a list of all cities defending at a battle tile.
   * @param {string} location
   * @param {string} city owner of location
   */
  public function getAllDefenders($location, $city) {
    $defenders = $this->getCitiesOnSide($location, DEFENDER);
    // automatically include the owner
    $defenders[] = $city;
    return $defenders;
  }

  /**
   * Get list of all cities on attacking or defending side of a battle.
   * @param {string} location
   * @param {integer} ATTACKER or DEFENDER
   */
  private function getCitiesOnSide($location, $side) {
    $forces = $this->game->getObjectListFromDB("SELECT city FROM MILITARY WHERE location=\"$location\" AND (battlepos=".($side+MAIN)." OR battlepos=".($side+ALLIED).")", true);
    return $forces;
  }

  /**
   * Sets relationship between two cities to WAR, ALLIED, or 0
   */
  private function setRelationship($city1, $city2, $r) {
    if (!in_array($r, [WAR, ALLIED, 0])) {
      throw new BgaVisibleSystemException("Invalid relationship ($r): $city1, $city2"); // NOI18N
    }
    if ($city1 == $city2 && $r != ALLIED) {
      throw new BgaVisibleSystemException("City ($city1) cannot set relationship with itself: $r"); // NOI18N
    }
    self::DbQuery( "UPDATE WARS SET $city2=$r WHERE name=\"$city1\"");
    self::DbQuery( "UPDATE WARS SET $city1=$r WHERE name=\"$city2\"");
  }

  /**
   * Get relationship status (WAR, ALLIED, NEUTRAL) between two cities
   * @param {string} city1
   * @param {string} city2
   */
  private function getRelationship($city1, $city2) {
    return $this->game->getObjectListFromDB("SELECT $city2 FROM WARS WHERE name=\"$city1\"", true)[0];
  }

  /**
   * Insert units into database
   */
  private function createMilitaryUnits($cn, $city, $idct) {
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
   * Get number of defeats for a city.
   * @param {string} city
   * @return {integer} defeats (0 or more)
   */
  public function getDefeats($city) {
    $def = $city."_defeats";
    $defeats = $this->game->getGameStateValue($def);
    return $defeats;
  }

    /**
     * Return associative array (city => #defeats)
     * @return array
     */
    public function getAllDefeats() {
      $defeats = array();
      foreach ($this->cities() as $cn) {
          $defeats[$cn] = $this->getDefeats($cn);
      }
      return $defeats;
  }

  /**
   * Add one to a player's statue count for a city,
   * @param {string} player_id
   * @param {string} city
   */
  public function addStatue($player_id, $city) {
    $this->game->incStat(1, $city."_statues", $player_id);
  }

  /**
   * Get all statues for this player as an associative array.
   * Don't include cities with no statues.
   * @param {string} player_id
   * @return {array} (city => statues)
   */
  public function getStatues($player_id) {
    $statues = [];
    foreach($this->cities() as $cn) {
      $s = $this->game->getStat($cn."_statues", $player_id);
      if ($s > 0) {
        $statues[$cn] = $s;
      }
    }
    return $statues;
  }

  /**
   * Get a double-associative array of all statues.
   * @param {array} players
   * @return {array} (player_id => {city => statues})
   */
  public function getAllStatues() {
    $statues = array();
    foreach($this->getPlayerIds() as $player_id) {
      $s = $this->getStatues($player_id);
      if (!empty($s)) {
        $statues[$player_id] = $s;
      }
    }
    return $statues;
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
   * Return the clienttranslation string for the city name (may be PERSIA).
   * @return translate string
   */
  public function getNameTr($city) {
    $name = "";
    if ($city == PERSIA) {
      $name = clienttranslate("Persia");
    } else {
      $name = $this->cities[$city]['name'];
    }
    return $name;
  }

  /**
   * Assign a leader to a city, or add to Persian leaders.
   * @param player_id player id
   * @param city to be leader of
   */
  public function setLeader($player_id, $city) {
    if ($city == PERSIA) {
        self::DbQuery("UPDATE player SET persia=TRUE where player_id=$player_id");
    } else {
        $this->game->setGameStateValue($city."_leader", $player_id);
    }
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
   * @param city or persia
   * @returns true if player_id is leader of city
   */
  public function isLeader($player_id, $city) {
    $isleader = false;
    if ($city == PERSIA) {
        $isleader = $this->game->getUniqueValueFromDB("SELECT persia FROM player where player_id=$player_id");
    } else {
      $isleader = ($this->getLeader($city) == $player_id);
    }
    return $isleader;
  }

  /**
   * Set all city leaders back to 0.
   * Zero out all Persian leaders.
   */
  public function clearLeaders() {
    foreach ($this->cities() as $cn) {
      $this->setLeader(0, $cn);
    }
    self::DbQuery("UPDATE player SET persia=FALSE");
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
   * Get all players who are leaders of Persia.
   * Checks by collecting every player_id that does not control any cities.
   * @return {array} may be empty
   */
  public function getPersianLeaders() {
    $persianleaders = $this->game->getObjectListFromDB("SELECT player_id FROM player WHERE persia IS TRUE", true);
    return $persianleaders;
  }

  /**
   * Assumes leaders of cities have been set.
   * Flags all players without a city as a Persian leader.
   */
  public function assignPersianLeaders() {
    foreach ($this->getPlayerIds() as $player_id) {
      $cities = $this->controlledCities($player_id);
      if (empty($cities)) {
        $this->setLeader($player_id, PERSIA);
      }
    }
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
     * Get all influence in all cities by player, not including Candidates.
     * @return {array} double associative array of player_id => {city => influence}
     */
    public function getAllInfluence() {
      $influencecubes = array();

      foreach ($this->getPlayerIds() as $player_id) {
        $cubes = [];
        foreach($this->cities() as $city) {
          $influence = $this->influence($player_id, $city);
          $cubes[$city] = $influence;
        }
        $influencecubes[$player_id] = $cubes;
      }
      return $influencecubes;
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
     * Check whether this player can spend an influence cube to commit extra units.
     * Must have influence cube in the city, and be leader.
     * @param {string} player_id
     * @param {string} city
     * @return boolean true if player_id is able to spend a cube from this city
     */
    function canSpendInfluence($player_id, $city) {
      return ($this->influence($player_id, $city) > 0) && $this->isLeader($player_id, $city);
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
     * @param {string} player_id
     * @return true if player is able to propose a candidate somewhere
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
      foreach ($this->getPlayerIds() as $player_id) {
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