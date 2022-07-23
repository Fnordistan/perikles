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
   * Get counters currently at a battle location or in a player's pool.
   * As an array of [id,city,type,strength,location,battlepos] counters.
   * @param {string} location name of tile or player_id
   * @param {int} position (optional, if not set then returns all units)
   * @return array (may be empty)
   */
  private function getCounters($location, $pos=0) {
    $counters = [];
    if ($pos == 0) {
      $counters = $this->game->getObjectListFromDB("SELECT id, city, type, strength, location, battlepos FROM MILITARY WHERE location=\"$location\"");
    } else {
      $counters = $this->game->getObjectListFromDB("SELECT id, city, type, strength, location, battlepos FROM MILITARY WHERE location=\"$location\" AND battlepos=$pos");
    }
    return $counters;
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
   * @param {location} tile
   * @param {type} HOPLITE or TRIREME
   * @return total attack strength
   */
  public function getAttackStrength($location, $type) {
      $attackers = $this->getAttackingCounters($location, $type);
      $strength = $this->getCounterStrength($attackers);
      return $strength;
  }

  /**
   * Given a location and  battle type, add strength of all defending units of that type at the battle.
   * @param {location} tile
   * @param {type} HOPLITE or TRIREME
   * @return total attack strength
   */
  public function getDefenseStrength($location, $type) {
      $defenders = $this->getDefendingCounters($location, $type);
      $strength = $this->getCounterStrength($defenders);
      return $strength;
  }

  /**
   * Given an array of counters, add the strengths of all of them.
   * @param {array} counters
   * @return {int} total strength of all counters
   */
  private function getCounterStrength($counters) {
    $strength = 0;
    foreach($counters as $counter) {
      $strength += $counter['strength'];
    }
    return $strength;
  }

  /**
   * Return all counters from a locaton to their respective cities.
   * Only does db changes, not clientside notifications.
   * @param {string} location
   */
  public function returnCounters($location) {
    $counters = $this->getCounters($location);
    foreach($counters as $counter) {
      $id = $counter['id'];
      $city = $counter['city'];
      self::DbQuery("UPDATE MILITARY SET location=\"$city\", battlepos=0 WHERE id=$id");
    }
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