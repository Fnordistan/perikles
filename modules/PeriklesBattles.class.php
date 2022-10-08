<?php

/*
 * Manage battles. Interface for getting battle-related data.
 */
class PeriklesBattles extends APP_GameClass
{
  private $game;
  private $combat_results_table = array(
    1 => array("odds" => "1:2", "attacker" => 10, "defender" => 5),
    2 => array("odds" => "-2", "attacker" => 9, "defender" => 6),
    3 => array("odds" => "1:1", "attacker" => 8, "defender" => 7),
    4 => array("odds" => "+2", "attacker" => 7, "defender" => 8),
    5 => array("odds" => "2:1", "attacker" => 6, "defender" => 9),
    6 => array("odds" => "3:1", "attacker" => 5, "defender" => 10),
  );

  public function __construct($game)
  {
    $this->game = $game;
  }

  /**
   * Get all counters belonging to a player.
   * Includes check for Persian counters.
   * @param {string} player_id
   * @return {array} of counters
   */
  public function getPlayerCounters($player_id) {
    $counters = [];
    // is this player leading the Persians?
    if ($this->game->Cities->isLeader($player_id, PERSIA)) {
      $counters = $this->getCounters(CONTROLLED_PERSIANS);
    } else {
      $counters = $this->getCounters($player_id);
    }
    return $counters;
  }

  /**
   * Get counters currently at a battle location.
   * As an array of [id,city,type,strength,location,battlepos] counters.
   * @param {string} location name of tile
   * @param {int} position (optional, if not set then returns all units)
   * @return array (may be empty)
   */
  public function getLocationCounters($location, $pos=0) {
    return $this->getCounters($location, $pos);
  }

  /**
   * Returns a single counter by id.
   * @param {string} id
   * @return a counter
   */
  public function getCounter($id) {
    $counter = $this->game->getObjectFromDB("SELECT id, city, type, strength, location, battlepos FROM MILITARY WHERE id=$id");
    return $counter;
  }

  /**
   * Get all counters for a city at a location.
   * @param {string} city
   * @param {string} location
   * @return {array} all counters of city at the location
   */
  public function getCountersByCity($city, $location) {
    $counters = $this->game->getObjectListFromDB("SELECT id, city, type, strength, location, battlepos FROM MILITARY WHERE city=\"$city\" AND location=\"$location\"");
    return $counters;
  }

  /**
   * Get counters currently at a battle location or in a player's pool.
   * As an array of [id,city,type,strength,location,battlepos] counters.
   * @param {string} location name of tile or player_id
   * @param {int} position (optional, if not set then returns all units)
   * @param {string} type (optional) if not null, select TRIREMES or HOPLITES
   * @return array (may be empty)
   */
  private function getCounters($location, $pos=0, $type=null) {
    $counters = [];
    $sql = "SELECT id, city, type, strength, location, battlepos FROM MILITARY WHERE location=\"$location\"";
    if ($pos != 0) {
      $sql .= " AND battlepos=$pos";
    }
    if ($type != null) {
      $sql .= " AND type=\"$type\"";
    }
    $counters = $this->game->getObjectListFromDB($sql);
    return $counters;
  }

  /**
   * Are there counters of a given type at a battle tile?
   * @param {string} location tile to check
   * @param {string} HOPLITE or TRIREME
   * @param {string} city (optional)
   */
  private function hasCounters($location, $type, $city=null) {
    $counters = [];
    $sql = "SELECT id FROM MILITARY WHERE location=\"$location\" AND type=\"$type\"";
    if ($city != null) {
      $sql .= " AND city=\"$city\"";
    }
    $counters = $this->game->getObjectListFromDB($sql, true);
    return !empty($counters);
  }

  /**
   * Are there Hoplites at the battle tile?
   * @param {string} location tile to check
   * @param {string} city (optional)
   */
  private function hasHoplites($location, $city=null) {
    return $this->hasCounters($location, HOPLITE, $city);
  }

  /**
   * Are there Triremes at the battle tile?
   * @param {string} location tile to check
   * @param {string} city (optional)
   */
  private function hasTriremes($location, $city=null) {
    return $this->hasCounters($location, TRIREME, $city);
  }

  /**
   * Are there Spartan Hoplites at the battle tile?
   * @param {string} location tile to check
   */
  private function hasSpartanHoplites($location) {
    return $this->hasHoplites($location, "sparta");
  }

  /**
   * Are there Athenian Triremes at the battle tile?
   * @param {string} location tile to check
   */
  private function hasAthenianTriremes($location) {
    return $this->hasTriremes($location, "athens");
  }

  /**
   * Given a Special tile name, check whether it can be used at the current battle.
   * Only checks unit types, not victory tokens (for Thessalanians and Persian Fleet).
   * We already know there is a battle (i.e., fighters on both sides).
   * @param {string} name of tile
   * @return true if this tile is eligible to use at this combat
   */
  public function mayUseBattleSpecial($tilename) {
    $mayuse = false;
    $tile = $this->nextBattle();
    $location = $tile['location'];

    switch ($tilename) {
      case BRASIDAS:
        // are there Spartan Hoplites at this battle?
        $mayuse = $this->hasSpartanHoplites($location);
        break;
      case THESSALANIANALLIES:
        $mayuse = $this->hasHoplites($location);
        break;
      case PERSIANFLEET:
        $mayuse = $this->hasTriremes($location);
        break;
      case PHORMIO:
        $mayuse = $this->hasAthenianTriremes($location);
        break;
      default:
        throw new BgaVisibleSystemException("not a valid Special Tile: $tilename"); // NOI18N
    }
    return $mayuse;
  }

  /**
   * Get all attacking counters.
   * As an array of [id,city,type,strength,location,battlepos] counters.
   * @param {string} location name of tile
   * @param {string} type optional, HOPLITE or TRIREME
   * @return array (may be empty)
   */
  public function getAttackingCounters($location, $type=null) {
    return $this->queryForCounters($location, ATTACKER, $type);
  }

  /**
   * Get all defending counters.
   * As an array of [id,city,type,strength,location,battlepos] counters.
   * @param {string} location name of tile
   * @param {string} type optional, HOPLITE or TRIREME
   * @return array (may be empty)
   */
  public function getDefendingCounters($location, $type=null) {
    return $this->queryForCounters($location, DEFENDER, $type);
  }

  /**
   * Actual db query for counters by ATTACKER/DEFENDER (+type)
   */
  private function queryForCounters($location, $side, $type=null) {
    $sql = "SELECT id, city, type, strength, location, battlepos FROM MILITARY WHERE location=\"$location\" AND (battlepos=".($side+MAIN)." OR battlepos=".($side+ALLY).")";
    if ($type != null) {
      $sql .= " AND type=\"".$type."\"";
    }
    $counters = $this->game->getObjectListFromDB($sql);
    return $counters;
  }

  /**
   * Get the player_id of the main attacker for a location.
   * @param location name
   * @return player_id or null
   */
  public function getAttacker($location) {
    return $this->game->getUniqueValueFromDB("SELECT attacker FROM LOCATION WHERE card_type_arg=\"$location\"");
  }

  /**
   * Get the player_id of the main defender for a location.
   * @param location name
   * @return player_id or null
   */
  public function getDefender($location) {
    return $this->game->getUniqueValueFromDB("SELECT defender FROM LOCATION WHERE card_type_arg=\"$location\"");
  }

  /**
   * Given a location and  battle type, add strength of all attacking units of that type at the battle.
   * (Does not add intrinsic attackers.)
   * @param {location} tile
   * @param {type} HOPLITE or TRIREME
   * @param {bool} bonus true if PHORMIO or BRASIDAS bonus
   * @return total attack strength
   */
  public function getAttackStrength($location, $type, $bonus=false) {
      return $this->totalCounterStrength(ATTACKER, $location, $type, $bonus);
  }

  /**
   * Given a location and  battle type, add strength of all defending units of that type at the battle.
   * (Does not add intrinsic defenders.)
   * @param {location} tile
   * @param {type} HOPLITE or TRIREME
   * @param {bool} bonus true if PHORMIO or BRASIDAS bonus
   * @return total attack strength
   */
  public function getDefenseStrength($location, $type, $bonus=false) {
    return $this->totalCounterStrength(DEFENDER, $location, $type, $bonus);
  }

  /**
   * Get total counter strength for one side. Applies BRASIDAS or PHORMIO bonus if applicable.
   * @param {int} ATTACKER or DEFENDER
   * @param {location} tile
   * @param {type} HOPLITE or TRIREME
   * @param {string} bonus true if PHORMIO or BRASIDAS bonus in effect
   */
  private function totalCounterStrength($side, $location, $type, $bonus) {
    $counters = ($side == ATTACKER) ? $this->getAttackingCounters($location, $type) : $this->getDefendingCounters($location, $type);
    $double = null;
    if ($bonus) {
      if ($type == HOPLITE) {
        $double = "sparta";
      } elseif ($type == TRIREME) {
        $double = "athens";
      }
    }
    $strength = $this->getCounterStrength($counters, $double);
    return $strength;
  }

  /**
   * Given an array of counters, add the strengths of all of them.
   * @param {array} counters
   * @param {string} double if not null, athens or sparta bonus
   * @return {int} total strength of all counters
   */
  private function getCounterStrength($counters, $double=null) {
    $strength = 0;
    foreach($counters as $counter) {
      $s = $counter['strength'];
      if ($counter['city'] == $double) {
        $s *= 2;
      }
      $strength += $s;
    }
    return $strength;
  }

  /**
   * Return all counters from a location to their respective cities.
   * Only does db changes, not clientside notifications.
   * @param {string} location
   * @param {string} (optional) type HOPLITE or TRIREME (all if null)
   * @return {array} the counters that were returned
   */
  public function returnCounters($location, $type=null) {
    $counters = $this->getCounters($location, 0, $type);
    foreach($counters as $counter) {
      $id = $counter['id'];
      $city = $counter['city'];
      $this->toLocation($id, $city);
    }
    return $counters;
  }

    /**
     * Get a list of the lowest value counters at a losing location.
     * Tries to get from main, if that is empty, then allied.
     * @param {string} loser ATTACKER or DEFENDER
     * @param {string} location
     * @param {string} type HOPLITE or TRIREME
     * @return {array} counters with the lowest strength, may be empty for militia only
     */
    public function getCasualties($loser, $location, $type) {
      $counters = ($loser == ATTACKER) ? $this->getAttackingCounters($location, $type) : $this->getDefendingCounters($location, $type);
      // are there any in main?
      $main = [];
      $ally = [];
      $mainpos = ($loser == ATTACKER) ? ATTACKER+MAIN : DEFENDER+MAIN;
      $allypos = ($loser == ATTACKER) ? ATTACKER+ALLY : DEFENDER+ALLY;
      foreach($counters as $counter) {
          $pos = $counter['battlepos'];
          if ($pos == $mainpos) {
              $main[] = $counter;
          } elseif ($pos == $allypos) {
              $ally[] = $counter;
          } else {
              throw new BgaVisibleSystemException("invalid position value: $pos"); //
          }
      }
      // must come from main if possible
      $lowest = empty($main) ? $this->getLowestCounters($ally) : $this->getLowestCounters($main);
      return $lowest;
  }

  /**
   * Get list of cities represented in a list of counters.
   * @param {array} counters
   * @return {array} city names (may be empty)
   */
  public function getCounterCities($counters) {
    $cities = [];
    foreach ($counters as $counter) {
        $from = $counter['city'];
        if (!in_array($from, $cities)) {
            $cities[] = $from;
        }
    }
    return $cities;
  }


    /**
     * Return the counters with the lowest strength.
     * @param {array} counters
     * @param {return} array all with lowest values
     */
  public function getLowestCounters($counters) {
      $min = 99;
      $buckets = [];
      foreach ($counters as $counter) {
          $s = $counter['strength'];
          if (!array_key_exists($s, $buckets)) {
              $buckets[$s] = array();
          }
          $buckets[$s][] = $counter;
          if ($s < $min) {
              $min = $s;
          }
      }
      return empty($buckets) ? [] : $buckets[$min];
  }

  /**
   * Set a counter's location by its id and unset battlepos.
   * @param string id of counter
   * @param string location
   * @param int position optional, defaults to 0
   */
  public function toLocation($id, $location, $position=0) {
    self::DbQuery("UPDATE MILITARY SET location=\"$location\", battlepos=$position WHERE id=$id");
  }

  /**
   * When a player takes leadership of a city. Sets all counters in the city to his ownership.
   * Returns all the counters just claimed.
   * @param {string} player_id
   * @param {string} city
   * @return {array} of counters
   */
  public function claimCountersInCity($player_id, $city) {
    self::DbQuery("UPDATE MILITARY SET location=\"$player_id\", battlepos=0 WHERE location=\"$city\"");
    $units = $this->getCountersByCity($city, $player_id);
    return $units;
  }

  /**
   * Same as claimCountersInCity, but claims all the Persians and flags them with special "controlled" flag (not player_id)
   * @return {array} of counters
   */
  public function claimPersians() {
      $persians = $this->claimCountersInCity(CONTROLLED_PERSIANS, PERSIA);
      return $persians;
  }

    /**
     * Return Location tile where the next battleis, or null if there are no more.
     * Retrieves next in queue.
     * associative array: [id,city,location,slot,attack,defender]
     * @return {array} tile, or null
     */
    public function nextBattle() {
      $battle = null;
      $battles = $this->game->getObjectListFromDB("SELECT card_id id, card_type city, card_type_arg location, card_location_arg slot, attacker, defender FROM LOCATION WHERE card_location = \"".BOARD."\" ORDER BY card_location_arg ASC LIMIT 1");
      if (!empty($battles)) {
          $battle = array_pop($battles);
      }
      return $battle;
  }

  /**
   * Check whether the location slot is set to the current battle.
   * @param location
   * @return true if location is the current active battle
   */
  public function isActiveBattleLocation($location) {
      $slot = $this->game->getUniqueValueFromDB("SELECT card_location_arg slot FROM LOCATION WHERE card_type_arg=\"$location\" AND card_location=\"".BOARD."\"");
      return ($slot == $this->game->getGameStateValue("active_battle"));
  }

  /**
   * Get the location of a battle by its slot.
   * @param {int} slot
   * @return {string} location
   */
  public function getBattleLocation($slot) {
    $location = $this->game->getUniqueValueFromDB("SELECT card_type_arg location FROM LOCATION WHERE card_location_arg=$slot");
    return $location;
  }

  /**
   * Get the number one side needs to roll.
   * @param {int} ATTACKER or DEFENDER
   * @param {int} crtcol column on CRT
   * @return target number to roll
   */
  public function getTargetNumber($side, $crtcol) {
    $role = "";
    if ($side === ATTACKER) {
      $role = "attacker";
    } elseif ($side == DEFENDER) {
      $role = "defender";
    } else {
      throw new BgaVisibleSystemException("invalid side: $side"); //NOI18N
    }
    return $this->combat_results_table[$crtcol][$role];
  }

  /**
   * Given the CRT odds column, get the odds label
   * @param {int} column
   * @return {string} odds "1:1" etc.
   */
  public function getOdds($col) {
    return $this->combat_results_table[$col]['odds'];
  }
  
  /**
   * Calculate which column on the CRT to use.
   * @param $att attack strength
   * @param $def defense strength
   * @return {int} column 1-6 on CRT
   */
  public function getCRTColumn($att, $def) {
      if ($att >= ($def*3)) {
          // 3:1
          return 6;
      } else if ($att >= ($def*2)) {
          // 2:1
          return 5;
      } else if ($att >= ($def+2)) {
          // +2
          return 4;
      } else if ($att >= $def || ($att > 1 && $att >= ($def-1))) {
          // 1:1
          return 3;
      } else if (($att*2) > $def) {
          // -2
          return 2;
      } else {
          // 1:2
          return 1;
      }
  }

}