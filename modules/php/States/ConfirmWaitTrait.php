<?php
namespace NUMDROP\States;
use NUMDROP\Core\Globals;
use NUMDROP\Core\StateMachine;
use NUMDROP\Managers\Players;

/*
 * Handle the confirm/restart and wait
 */
trait ConfirmWaitTrait
{
  function actCancelTurn()
  {
    StateMachine::checkAction("actRestart");
    $player = Players::getCurrent();
    $player->restartTurn();
    // TODO : $player->updateScores();

    $this->gamestate->setPlayersMultiactive([$player->getId()], '');
    StateMachine::nextState("restart");
  }

  function actConfirmTurn()
  {
    StateMachine::checkAction("actConfirmTurn");
    StateMachine::nextState("confirm");
  }

  /*
   * Make the player inactive and wait for other
   */
  function stWaitOther($player)
  {
    return $this->gamestate->setPlayerNonMultiactive($player->getId(), "applyTurns");
  }


  /**
   * Notify everyone about the turns taken
   */
   function stApplyTurn()
   {
     // TODO

     $newState = $this->isEndOfGame()? "endGame" : "newTurn";
     $this->gamestate->nextState($newState);
   }
}
