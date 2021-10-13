<?php
namespace NUMDROP\States;
use NUMDROP\Core\Globals;
use NUMDROP\Core\Notifications;
use NUMDROP\Core\StateMachine;
use NUMDROP\Managers\Players;
use NUMDROP\Managers\Blocks;
use NUMDROP\Managers\Scribbles;

/*
 * Handle the confirm/restart and wait
 */
trait ConfirmWaitTrait
{
  function stConfirmTurn($player)
  {
    if ($player->getPref(CONFIRM) == CONFIRM_DISABLED) {
      StateMachine::nextState('confirm');
      return true; // Acknowledge we changed the state to make sure it won't send the args for this one
    }
  }

  function actCancelTurn()
  {
    StateMachine::checkAction('actRestart');
    $player = Players::getCurrent();
    $player->restartTurn();

    $this->gamestate->setPlayersMultiactive([$player->getId()], '');

    $block = Blocks::getTriggered();
    StateMachine::nextState($block === null? 'restart' : 'restartBlock');
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
    // Any block to finish ?
    $block = Blocks::getTriggered();
    if ($block !== null) {
      $this->disableBlock($block);
    }

    // Update scribbles
    $scribbles = Scribbles::getLastAdded();
    // Update scores
    $turn = Globals::incCurrentTurn();
    $scores = Players::getAll()->map(function ($player) {
      return $player->updateScore();
    });
    Notifications::updatePlayersData($scribbles, $scores);

    // Are there any blocks that are defended by everyone ?
    $uselessBlocks = Blocks::getUselessBlocks();
    foreach($uselessBlocks as $block){
      $this->disableBlock($block, $turn - 1);
    }


    $newState = $this->isEndOfGame() ? 'endGame' : 'newTurn';
    $this->gamestate->nextState($newState);
  }

  function disableBlock($block, $turn = null)
  {
    Blocks::finish($block);
    $scribbles = [];
    foreach (Players::getAll() as $player) {
      $scribbles[] = Scribbles::addNumber($player, $block, COL_BLOCK, CROSS, $turn);
    }
    $scribbles[] = Scribbles::addNumber(null, $block, COL_BLOCK_STATUS, CROSS, $turn);
    Notifications::finishBlock($block, Scribbles::get($scribbles));
  }
}
