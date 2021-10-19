<?php
namespace NUMDROP\Managers;
use NUMDROP\Core\Game;
use NUMDROP\Core\Globals;

/*
 * Players manager : allows to easily access players ...
 *  a player is an instance of Player class
 */
class Blocks
{
  public function setupNewGame($players, $options)
  {
    $blockIds = [0, 1, 2, 3, 4];
    shuffle($blockIds);
    $blocks = [];
    foreach ($blockIds as $id) {
      $blocks[] = [
        'id' => $id,
        'status' => 0,
        'col' => 0,
      ];
    }
    Globals::setBlocks($blocks);
  }

  public function getNextActiveBlock()
  {
    $blockMin = 10;
    foreach (Players::getAll() as $player) {
      $columns = $player->getScoringColumns();
      foreach ($columns[COL_BLOCK] as $i => $state) {
        if ($state === true) {
          $blockMin = min($blockMin, $i);
        }
      }
    }

    return $blockMin == 10 ? null : $blockMin;
  }

  public function getUselessBlocks()
  {
    $blocks = [];
    foreach ([0, 1, 2, 3, 4] as $blockId) {
      $useless = true;
      foreach (Players::getAll() as $player) {
        $columns = $player->getScoringColumns();
        if ($columns[COL_BLOCK][$blockId] !== true) {
          $useless = false;
        }
      }
      if ($useless) {
        $blocks[] = $blockId;
      }
    }

    return $blocks;
  }

  public function getTargets()
  {
    $blockId = self::getNextActiveBlock();
    $targets = [];
    foreach (Players::getAll() as $pId => $player) {
      $columns = $player->getScoringColumns();
      if ($columns[COL_BLOCK][$blockId] === false) {
        $targets[] = $pId;
      }
    }

    return $targets;
  }

  public function trigger($block)
  {
    $blocks = Globals::getBlocks();
    $blocks[$block]['status'] = 1;
    Globals::setBlocks($blocks);
  }

  public function finish($block)
  {
    $blocks = Globals::getBlocks();
    $blocks[$block]['status'] = 2;
    Globals::setBlocks($blocks);
  }

  public function getTriggered()
  {
    foreach (Globals::getBlocks() as $i => $block) {
      if ($block['status'] == 1) {
        return $i;
      }
    }

    return null;
  }

  public function getSelectableTiles()
  {
    $t = [];
    $target = null;
    if (Globals::getSoloStatus() == 1) {
      $dice = Globals::getDices();
      if ($dice[4] != '*') {
        $keys = ['S', 'O', 'T', 'I', 'L'];
        $target = array_search($dice[4], $keys);
      }
    }

    foreach (Globals::getBlocks() as $i => $block) {
      if ($block['col'] < 4 && (is_null($target) || $target == $block['id'])) {
        $t[] = $i;
      }
    }

    return $t;
  }

  public function slideDown($tileId)
  {
    $blocks = Globals::getBlocks();
    $blocks[$tileId]['col']++;
    Globals::setBlocks($blocks);
    return $blocks[$tileId];
  }
}
