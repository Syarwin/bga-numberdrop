<?php
namespace NUMDROP\States;
use NUMDROP\Core\Globals;
use NUMDROP\Core\Notifications;
use NUMDROP\Managers\Players;
use NUMDROP\Managers\Scribbles;

trait StartingNumberTrait
{
  /**
   * Fetch for each player its starting number = player's no (TODO : can be directly )
   */
  public function argsStartingNumber()
  {
    $choices = Globals::getStartingNumberChoices();
    $data = ['_private' => []];
    foreach (Players::getAll() as $pId => $player) {
      $data['_private'][$pId] = [
        'n' => $player->getNo(),
        'col' => $choices[$pId] ?? null,
      ];
    }
    return $data;
  }

  /**
   * If a player changed his mind about starting number => put it back active
   */
  public function actChangeStartingNumber()
  {
    $this->gamestate->checkPossibleAction('actChangeStartingNumber');
    $this->gamestate->setPlayersMultiactive([$this->getCurrentPlayerId()], 'error', false);
  }

  /**
   * A player confirm his choice for starting number
   */
  public function actPlaceStartingNumber($col)
  {
    $player = Players::getCurrent();
    if ($col < 0 || $col > 6) {
      throw new \BgaVisibleSystemException('You can\'t put a number here');
    }

    // Save choice
    $choices = Globals::getStartingNumberChoices();
    $choices[$player->getId()] = $col;
    Globals::setStartingNumberChoices($choices);

    $this->gamestate->setPlayerNonMultiactive($player->getId(), '');
  }


  /**
   * Everyone made his choice => notify and go to next step
   */
  public function stFinishSetup()
  {
    $choices = Globals::getStartingNumberChoices();
    foreach(Players::getAll() as $pId => $player){
      $player->addNumber(0, $choices[$pId], $player->getNo());
    }

    // Update scribbles
    $scribbles = Scribbles::getLastAdded();
    // Update scores
    Globals::incCurrentTurn();
    $scores = Players::getAll()->map(function($player){ return $player->updateScore(); });
    Notifications::updatePlayersData($scribbles, $scores);

    $this->gamestate->nextState();
  }
}
