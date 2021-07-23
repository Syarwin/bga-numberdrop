<?php
namespace NUMDROP\States;
use NUMDROP\Core\Globals;
use NUMDROP\Core\Notifications;
use NUMDROP\Core\StateMachine;
use NUMDROP\Managers\Players;

trait NewTurnTrait
{
  /**
   * Roll the dices
   */
  public function stNewTurn()
  {
    // Increase turn counters
    Globals::incCurrentTurn();
    Globals::setTetrominos([]);

    // Throw the dices
    $dices = ['1*3457', '12*456', '234*67', '123567', 'IOTLS*'];
    $result = [];
    foreach($dices as $dice){
      $r = bga_rand(0,5);
      $result[] = $dice[$r];
    }
    Globals::setDices($result);
    Notifications::throwDices($result);

    if(in_array('*', $result) && Players::getNextActiveDrop() != null){
      StateMachine::initPrivateStates(ST_PLAYER_TURN);
      $this->gamestate->nextState('drop');
    } else {
      $ids = Players::getAll()->getIds();
      $this->gamestate->setPlayersMultiactive($ids, '');
      StateMachine::initPrivateStates(ST_PLAYER_TURN);
      $this->gamestate->nextState('play');
    }
  }


  /*
   * The arg depends on the private state of each player
   */
  function argPlayerTurn()
  {
    return StateMachine::getArgs();
  }

  /*
   * Fetch the basic info a player should have no matter in which private state he is :
   *   - selected construction cards (if any)
   *   - cancelable flag on if an action was already done by user
   */
  function argPrivatePlayerTurn($player)
  {
    if($player->isZombie()){
      return [];
    }

    $data = [
      'selectedCards' => $player->getSelectedCards(),
      'selectedPlans' => $player->getSelectedPlans(),
      'cancelable' => $player->hasSomethingToCancel(),
    ];

    return $data;
  }
}
