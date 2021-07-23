<?php
namespace NUMDROP\States;
use NUMDROP\Core\Globals;
use NUMDROP\Managers\Players;

trait DropShapeTrait
{
  /*
   * The arg depends on the private state of each player
   */
  function argDropShape($player)
  {
    return [
      'dices' => Globals::getDices(),
      'tetromino' => Globals::getTetrominos()[$player->getId()] ?? null,
    ];
  }

  function actConstructTetromino($tetromino)
  {
    $player = Players::getCurrent();
    $tetrominos = Globals::getTetrominos();
    $tetrominos[$player->getId()] = $tetromino;
    Globals::setTetrominos($tetrominos);
  }
}
