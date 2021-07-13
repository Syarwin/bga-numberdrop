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
    $row['pId'] = $row['player_id'];
    return $row;
  }

  public function addNumber($pId, $row, $col, $n, $turn)
  {
    self::DB()->insert([
      'player_id' => $pId,
      'row' => $row,
      'col' => $col,
      'number' => $n,
      'turn' => $turn,
    ]);
  }
}
