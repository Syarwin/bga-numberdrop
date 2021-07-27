<?php
namespace NUMDROP\Managers;
use NUMDROP\Core\Game;
use NUMDROP\Core\Globals;

/*
 * Scribbles manager : allows to easily access number on the board and other thing on the scoresheet
 */
class Scribbles extends \NUMDROP\Helpers\DB_Manager
{
  protected static $table = 'scribbles';
  protected static $primary = 'id';
  protected static function cast($row)
  {
    // Set pId instead of player_id for shortness
    $row['pId'] = $row['player_id'];
    unset($row['player_id']);

    return $row;
  }

  public function addNumber($pId, $row, $col, $n, $turn = null)
  {
    $turn = $turn ?? Globals::getCurrentTurn();
    self::DB()->insert([
      'player_id' => $pId,
      'row' => $row,
      'col' => $col,
      'number' => $n,
      'turn' => $turn,
    ]);
  }

  /**
   * Mark a cell as used
   */
  public function useCell($pId, $cell)
  {
    $turn = Globals::getCurrentTurn();
    self::DB()->insert([
      'player_id' => $pId,
      'row' => $cell['row'],
      'col' => $cell['col'],
      'number' => CIRCLE,
      'turn' => $turn,
    ]);
  }


  public function getOfPlayer($player)
  {
    $pId = is_int($player) ? $player : $player->getId();
    $query = self::DB()->wherePlayer($player);

    try {
      // Filter out the scribbles of current turn if not current player
      if (Players::getCurrentId() != $pId) {
        $query = $query->where('turn', '<', Globals::getCurrentTurn());
      }
    } finally {
      return $query->get()->toArray();
    }
  }

  public function hasScribbleSomething($pId)
  {
    return self::DB()
      ->wherePlayer($pId)
      ->where('turn', Globals::getCurrentTurn())
      ->count() > 0;
  }
}
