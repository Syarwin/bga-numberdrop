<?php
namespace NUMDROP\Managers;
use NUMDROP\Core\Game;
use NUMDROP\Core\Globals;

/*
 * Players manager : allows to easily access players ...
 *  a player is an instance of Player class
 */
class Drops
{
  public function setupNewGame($players, $options)
  {
    $dropIds = [0, 1, 2, 3, 4];
    shuffle($dropIds);
    $drops = [];
    foreach ($dropIds as $id) {
      $drops[] = [
        'id' => $id,
        'status' => 0,
      ];
    }
    Globals::setDrops($drops);

    // TODO : debug only , remove before prod
    $player = Players::getAll()->first();
    Scribbles::useCell($player, [
      'row' => 1,
      'col' => COL_DROP,
    ]);
  }

  public function getNextActiveDrop()
  {
    $dropMin = 10;
    foreach (Players::getAll() as $player) {
      $columns = $player->getScoringColumns();
      foreach ($columns[COL_DROP] as $i => $state) {
        if ($state === true) {
          $dropMin = min($dropMin, $i);
        }
      }
    }

    return $dropMin == 10 ? null : $dropMin;
  }

  public function getTargets()
  {
    $dropId = self::getNextActiveDrop();
    $targets = [];
    foreach (Players::getAll() as $pId => $player) {
      $columns = $player->getScoringColumns();
      if ($columns[COL_DROP][$dropId] === false) {
        $targets[] = $pId;
      }
    }

    return $targets;
  }

  public function trigger($drop)
  {
    $drops = Globals::getDrops();
    $drops[$drop]['status'] = 1;
    Globals::setDrops($drops);
  }

  public function finish($drop)
  {
    $drops = Globals::getDrops();
    $drops[$drop]['status'] = 2;
    Globals::setDrops($drops);
  }


  public function getTriggered()
  {
    foreach (Globals::getDrops() as $i => $drop) {
      if ($drop['status'] == 1) {
        return $i;
      }
    }

    return null;
  }
}
