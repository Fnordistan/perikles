<?php

/*
 * Manage cities and who's at war with who
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

  public function setupNewGame()
  {
    $id = 1;
    foreach($this->cities as $cn => $city) {
        $id = $this->createMilitaryUnits($cn, $city, $id);
    }
    // and add the Persians
    $cn = "persia";
    $id = $this->createMilitaryUnits($cn, $this->persia[$cn], $id);
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

}