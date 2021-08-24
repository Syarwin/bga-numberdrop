<?php
namespace NUMDROP\States;
use NUMDROP\Core\Globals;
use NUMDROP\Core\Notifications;
use NUMDROP\Core\StateMachine;
use NUMDROP\Managers\Players;
use NUMDROP\Managers\Drops;
use NUMDROP\Managers\Scribbles;

/*
 * Handle the confirm/restart and wait
 */
trait ConfirmWaitTrait
{
  function actCancelTurn()
  {
    StateMachine::checkAction('actRestart');
    $player = Players::getCurrent();
    $player->restartTurn();
    // TODO : $player->updateScores();

    $this->gamestate->setPlayersMultiactive([$player->getId()], '');
    StateMachine::nextState('restart');
  }

  function actConfirmTurn()
  {
    StateMachine::checkAction('actConfirmTurn');
    StateMachine::nextState('confirm');
  }

  /*
   * Make the player inactive and wait for other
   */
  function stWaitOther($player)
  {
    return $this->gamestate->setPlayerNonMultiactive($player->getId(), 'applyTurns');
  }

  /**
   * Notify everyone about the turns taken
   */
  function stApplyTurn()
  {
    // Any drop to finish ?
    $drop = Drops::getTriggered();
    if ($drop !== null) {
      Drops::finish($drop);
      $scribbles = [];
      foreach (Players::getAll() as $player) {
        $scribbles[] = Scribbles::addNumber($player, $drop, COL_DROP, CROSS);
      }
      Notifications::finishDrop($drop, Scribbles::get($scribbles));
    }

    // Update scribbles
    $scribbles = Scribbles::getLastAdded();
    // Update scores
    Globals::incCurrentTurn();
    $scores = Players::getAll()->map(function($player){ return $player->updateScore(); });
    Notifications::updatePlayersData($scribbles, $scores);

    $newState = $this->isEndOfGame() ? 'endGame' : 'newTurn';
    $this->gamestate->nextState($newState);
  }
}
