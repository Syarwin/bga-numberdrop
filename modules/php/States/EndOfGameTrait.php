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
    return false;
  }


  /*
   *
   */
  function stComputeScores()
  {
    // TODO
    $this->gamestate->nextState("endGame");
  }
}
