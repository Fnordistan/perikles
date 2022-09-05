<?php

/*
 * Manage the Deadpool.
 */
class PeriklesDeadpool extends APP_GameClass
{
  private $game;

  public function __construct($game)
  {
    $this->game = $game;
  }

  /**
   * Sends a unit to the deadpool. (For Persians, sends them back to their stack.)
   * Does not send notifications.
   * @param {Object} counter
   */
  public function toDeadpool($counter) {
    $id = $counter['id'];
    $city = $counter['city'];
    $deadpool = ($city == PERSIA) ? PERSIA : DEADPOOL;
    self::DbQuery("UPDATE MILITARY SET location=\"$deadpool\", battlepos=0 WHERE id=$id");
  }

  /**
   * Get potential units to be retrieved from deadpool by city. Just determines which types exist for a city.
   * Returned array may be empty or contain HOPLITE and/or TRIREME.
   * @param string city
   * @return array array with HOPLITE? TRIREME?
   */
  public function getTypesInDeadpool($city) {
    $units = array();

    foreach([HOPLITE, TRIREME] as $type) {
      if ($this->inDeadpool($city, $type)) {
        $units[] = $type;
      }
    }
    return $units;  
  }

  /**
   * Assumes state has marked this city as being eligible for selection (not picked yet), and gets ids of eligible HOPLITE and TRIREME.
   * @param array cities
   * @return array {id => [id, type, strength, city]}
   */
  public function getDeadpoolChoices($cities) {
    $choices = array();
    foreach($cities as $city) {
        foreach([HOPLITE, TRIREME] as $u) {
            $choices[] = $this->getLowestDeadPoolUnit($city, $u);
        }
    }
    return $choices;
  }

  /**
   * Get a counter with the lowest strength by city and type in the deadpool.
   * @param string city
   * @param string HOPLITE or TRIREME
   * @return NULL or one counter
   */
  private function getLowestDeadPoolUnit($city, $type) {
    $counter = $this->game->getObjectFromDB("SELECT id, city, type, MIN(strength) as strength FROM MILITARY WHERE type=\"$type\" AND city=\"$city\" AND location=\"".DEADPOOL."\" LIMIT 1");
    return $counter;
  }

  /**
   * Takes one unit from the Deadpool and puts it with the player who controls it. Assumes validity checks have already been done.
   * Does DB change but not notification or state change.
   * @param string player_id owning player
   * @param string city
   * @param string type HOPLITE or TRIREME
   * @return the counter that was moved to city
   */
  public function takeFromDeadpool($player_id, $city, $type) {
    $counter = $this->getLowestDeadPoolUnit($city, $type);
    if ($counter == null) {
        // shouldn't happen
      throw new BgaVisibleSystemException("no $city $type unit in deadpool"); // NOI18N
    }
    $id = $counter['id'];
    self::DbQuery("UPDATE MILITARY SET location=\"$player_id\" WHERE id=$id");
    return $counter;
  }

  /**
   * Is there a unit of this type in the city?
   * @param {string} city
   * @param {string} type
   * @return {bool} true if there is a city/type unit in the deadpool
   */
  private function inDeadpool($city, $type) {
    $dead = $this->game->getUniqueValueFromDB("SELECT id FROM MILITARY WHERE type=\"$type\" AND city=\"$city\" AND location=\"".DEADPOOL."\" LIMIT 1");
    return ($dead != null);
  }

}