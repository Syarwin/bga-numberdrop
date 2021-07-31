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
    return self::DB()->insert([
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
  public function useCell($player, $cell)
  {
    $pId = is_int($player) ? $player : $player->getId();
    $turn = Globals::getCurrentTurn();
    return self::DB()->insert([
      'player_id' => $pId,
      'row' => $cell['row'],
      'col' => $cell['col'],
      'number' => CIRCLE,
      'turn' => $turn,
    ]);
  }

  /**
   * Get all the scribbles of a player with a filter on ongoing scribbles if not current player
   *  => that way no-one will be able to spy onto another player by refreshing the page
   */
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

  /**
   * Useful to know if the player have something to cancel or not
   */
  public function hasScribbleSomething($pId)
  {
    return self::DB()
      ->wherePlayer($pId)
      ->where('turn', Globals::getCurrentTurn())
      ->count() > 0;
  }

  /**
   * clearTurn : remove all houses written by player during this turn
   */
  public function clearTurn($pId)
  {
    self::DB()
      ->wherePlayer($pId)
      ->where('turn', Globals::getCurrentTurn())
      ->delete()
      ->run();
  }


  /**
   * Get specific scribbles by id
   */
  public static function getMany($ids, $raiseExceptionIfNotEnough = true)
  {
    if (!is_array($ids)) {
      $ids = [$ids];
    }

    if (empty($ids)) {
      return new Collection([]);
    }

    $result = self::DB()
      ->whereIn(static::$primary, $ids)
      ->get();
    if ($result->count() != count($ids) && $raiseExceptionIfNotEnough) {
      throw new \feException('Class DB_Manager: getMany, some rows have not been found !' . json_encode($ids));
    }

    return $result;
  }

  public static function get($id, $raiseExceptionIfNotEnough = true)
  {
    $result = self::getMany($id, $raiseExceptionIfNotEnough);
    return $result->count() == 1 ? $result->first() : $result;
  }
}
