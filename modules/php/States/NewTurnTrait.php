<?php
namespace NUMDROP\States;
use NUMDROP\Core\Globals;
use NUMDROP\Core\Notifications;
use NUMDROP\Core\StateMachine;
use NUMDROP\Managers\Players;
use NUMDROP\Managers\Blocks;

trait NewTurnTrait
{
  /**
   * Roll the dices
   */
  public function stNewTurn()
  {
    // Increase turn counters
    $turn = Globals::getCurrentTurn();
    Globals::setTetrominos([]);

    // Throw the dices
    $dices = ['1*3457', '12*456', '234*67', '123567', 'IOTLS*'];
    $result = [];
    foreach ($dices as $dice) {
      $r = bga_rand(0, 5);
      $result[] = $dice[$r];
    }
    Globals::setDices($result);
    Notifications::throwDices($result, $turn);

    if(Globals::isSolo()){
      Globals::setSoloStatus(0);
      Globals::setBackupBlocks(Globals::getBlocks());
      $pId = self::getActivePlayerId();
      StateMachine::initPrivateStates(ST_SOLO_PLAYER_TURN);
      $this->gamestate->setPlayersMultiactive([$pId], '');
      self::giveExtraTime($pId);
      $this->gamestate->nextState('solo');
      return;
    }

    // Check block
    $block = Blocks::getNextActiveBlock();
    if (in_array('*', $result) && !is_null($block) && !empty(Blocks::getTargets())) {
      Notifications::blockTriggered($block);
      Blocks::trigger($block);
      StateMachine::initPrivateStates(ST_BLOCK_PLAYER_TURN);
      $ids = Blocks::getTargets();
      foreach ($ids as $pId) {
        self::giveExtraTime($pId);
      }
      $this->gamestate->setPlayersMultiactive($ids, '');
      $this->gamestate->nextState('block');
    } else {
      $ids = Players::getAll()->getIds();
      foreach ($ids as $pId) {
        self::giveExtraTime($pId);
      }
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
    if ($player->isZombie()) {
      return [];
    }

    $data = [
      'cancelable' => $player->hasSomethingToCancel(),
    ];

    return $data;
  }
}
