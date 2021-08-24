<?php
namespace NUMDROP\States;
use NUMDROP\Core\Globals;
use NUMDROP\Core\StateMachine;
use NUMDROP\Managers\Players;

/*
 * Handle the end of game
 */

trait EndOfGameTrait
{
  function isEndOfGame()
  {
    foreach (Players::getAll() as $player) {
      $scoringColumns = $player->getScoringColumns();
      for ($i = 11; $i < 14; $i++) {
        if ($scoringColumns[COL_END_LINES][$i]) {
          return true;
        }
      }
    }
    return false;
  }

  /*
   *
   */
  function stComputeScores()
  {
    // TODO
    $this->gamestate->nextState('endGame');
  }
}
