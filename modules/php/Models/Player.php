<?php
namespace NUMDROP\Models;
use NUMDROP\Core\Globals;
use NUMDROP\Core\Notifications;
use NUMDROP\Core\Preferences;
use NUMDROP\Helpers\Utils;
use NUMDROP\Helpers\Log;
use NUMDROP\Managers\Scribbles;

/*
 * Player: all utility functions concerning a player
 */

class Player extends \NUMDROP\Helpers\DB_Manager implements \JsonSerializable
{
  protected static $table = 'player';
  protected static $primary = 'player_id';

  protected $id;
  protected $no; // natural order
  protected $name; // player name
  protected $color;
  protected $eliminated = false;
  protected $score = 0;
  protected $zombie = false;
  protected $state = null;

  public function __construct($row)
  {
    if ($row != null) {
      $this->id = (int) $row['player_id'];
      $this->no = (int) $row['player_no'];
      $this->name = $row['player_name'];
      $this->color = $row['player_color'];
      $this->eliminated = $row['player_eliminated'] == 1;
      $this->score = $row['player_score'];
      $this->zombie = $row['player_zombie'] == 1;
      $this->state = $row['player_state'];
    }
  }

  /*
   * Getters
   */
  public function getId()
  {
    return $this->id;
  }
  public function getNo()
  {
    return $this->no;
  }
  public function getName()
  {
    return $this->name;
  }
  public function getColor()
  {
    return $this->color;
  }
  public function isEliminated()
  {
    return $this->eliminated;
  }
  public function isZombie()
  {
    return $this->zombie;
  }
  public function getState()
  {
    return $this->state;
  }

  public function getPref($prefId)
  {
    return Preferences::get($this->id, $prefId);
  }

  public function jsonSerialize($currentPlayerId = null)
  {
    $current = $this->id == $currentPlayerId;
    $data = [
      'id' => $this->id,
      'eliminated' => $this->eliminated,
      'no' => $this->no,
      'name' => $this->name,
      'color' => $this->color,
      'score' => $this->score,
      'scribbles' => $this->getScribbles(),
    ];
    return $data;
  }

  public function getScribbles()
  {
    return Scribbles::getOfPlayer($this->id);
  }

  public function addNumber($row, $col, $n, $turn = null)
  {
    Scribbles::addNumber($this->id, $row, $col, $n, $turn);
  }

  public function getScoringColumns()
  {
    $result = [
      COL_END_LINES => [false, false, false, false, false, false, false, false, false, false, false, false, false, false],
      COL_SAME => [false, false, false, false, false],
      COL_SEQUENCE => [false, false, false, false, false],
      COL_BONUS => [false],
    ];
    foreach ($this->getScribbles() as $scribble) {
      if (in_array($scribble['col'], [COL_END_LINES, COL_SAME, COL_SEQUENCE, COL_BONUS])) {
        $result[$scribble['col']][$scribble['row']] = true;
      }
    }

    return $result;
  }

  public function getBoard()
  {
    $board = [];
    for ($i = 0; $i < 14; $i++) {
      for ($j = 0; $j < 7; $j++) {
        $board[$i][$j] = null;
      }
    }

    foreach ($this->getScribbles() as $scribble) {
      $board[$scribble['row']][$scribble['col']] = $scribble;
    }

    return $board;
  }

  /*
   * Boolean value needed to know if we display the "restart turn" button
   */
  public function hasSomethingToCancel()
  {
    //    return !empty(Log::getLastActions($this->id)) || Scribbles::hasScribbleSomething($this->id);
    return Scribbles::hasScribbleSomething($this->id);
  }

  // Restart the turn by clearing all log, houses, scribbles.
  public function restartTurn()
  {
    $notifIds = Log::clearTurn($this->id);
    Scribbles::clearTurn($this->id);
    Notifications::clearTurn($this, $notifIds);
  }
}
