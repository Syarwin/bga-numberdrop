<?php
namespace NUMDROP\States;
use NUMDROP\Core\Globals;
use NUMDROP\Core\StateMachine;
use NUMDROP\Core\Notifications;
use NUMDROP\Managers\Players;
use NUMDROP\Managers\Scribbles;

trait ScoreCombinationTrait
{
  /**
   * Enter the state, initialize empty scoring combination
   */
  function stScoreCombination($player)
  {
    $combinations = Globals::getCombinations();
    $combinations[$player->getId()] = [];
    Globals::setCombinations($combinations);
  }

  /**
   * Args: send the ongoing combination
   */
  function argScoreCombination($player)
  {
    $combinations = Globals::getCombinations();
    return [
      'combination' => $combinations[$player->getId()],
      'columns' => $player->getScoringColumns(),
    ];
  }

  /**
   * Action called whenever a player change its ongoing scoring combination
   */
  function actConstructCombination($combination)
  {
    $player = Players::getCurrent();
    $combinations = Globals::getCombinations();
    $combinations[$player->getId()] = $combination;
    Globals::setCombinations($combinations);
  }

  /**
   * Action called whenever a player confirm its scoring combination
   */
  function actConfirmCombination()
  {
    $player = Players::getCurrent();
    $board = $player->getBoard();
    $combinations = Globals::getCombinations();
    $combination = $combinations[$player->getId()];

    // Check the cells
    foreach ($combination as $cell) {
      $number = $board[$cell['row']][$cell['col']]['number'] ?? null;
      if ($number == null || $number != $cell['n']) {
        throw new \BgaVisibleSystemException('One of a cell is invalid/empty');
      }
    }

    // Check length
    $cSize = count($combination);
    if ($cSize < 3 || $cSize > 8) {
      throw new \BgaVisibleSystemException('Invalid combination size');
    }

    // Find and check the type of combination
    $type = null;
    if ($combination[0]['n'] == $combination[1]['n']) {
      // Should be a combination of identical numbers
      $type = COL_SAME;
      $number = $combination[0]['n'];
      foreach ($combination as $cell) {
        if ($cell['n'] != $number) {
          throw new \BgaVisibleSystemException('This is an invalid combination');
        }
      }
    } else {
      // Should be a increasing/decreasing sequence
      $type = COL_SEQUENCE;
      $inc = $combination[0]['n'] < $combination[1]['n'] ? 1 : -1;
      $number = $combination[0]['n'];
      foreach ($combination as $cell) {
        if ($cell['n'] != $number) {
          throw new \BgaVisibleSystemException('This is an invalid combination');
        }
        $number += $inc;
      }
    }
    $col = $type;

    // Special case of bonus combination
    $row = $cSize - 3;
    if ($cSize == 8) {
      $col = COL_BONUS;
      $row = 0;
    }

    // Check if this scoring spot is still available
    $scoringColumns = $player->getScoringColumns();
    if ($scoringColumns[$col][$row]) {
      throw new \BgaVisibleSystemException('You already scored this type/size of combination');
    }

    // Update DB
    $scribbles = [];
    foreach ($combination as $cell) {
      $scribbles[] = Scribbles::useCell($player, $cell);
    }
    $scribbles[] = Scribbles::useCell($player, [
      'row' => $row,
      'col' => $col,
    ]);
    $scoringColumns[$col][$row] = true;

    // Check unlocked DROP
    if ($scoringColumns[COL_SAME][$row] && $scoringColumns[COL_SEQUENCE][$row] && $scoringColumns[COL_BLOCK][$row] === false) {
      $scribbles[] = Scribbles::useCell($player, [
        'row' => $row,
        'col' => COL_BLOCK,
      ]);

      // Allow to see on DROP board the current status of DROPs
      $scribbles[] = Scribbles::useCell($player, [
        'row' => $row,
        'col' => COL_BLOCK_STATUS,
      ]);
    }

    // Notifications
    $scores = $player->getScores();
    Notifications::scoreCombination($player, $type, $cSize, Scribbles::get($scribbles), $scores);
    // TODO : updateScore

    // Move on to next state
    StateMachine::nextState('confirmWait');
  }

  function actPassScoreCombination()
  {
    $player = Players::getCurrent();
    StateMachine::nextState('confirmWait');
  }
}
