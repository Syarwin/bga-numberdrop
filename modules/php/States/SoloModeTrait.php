<?php
namespace NUMDROP\States;
use NUMDROP\Core\Globals;
use NUMDROP\Core\Notifications;
use NUMDROP\Core\StateMachine;
use NUMDROP\Managers\Players;
use NUMDROP\Managers\Blocks;

trait SoloModeTrait
{
  public function stSlideDown($player)
  {
    $args = $this->argSlideDown($player);
    if (count($args['tiles']) == 1) {
      $this->actChooseTile($args['tiles'][0]);
      return true;
    } elseif (empty($args['tiles'])) {
      StateMachine::nextState('play');
      return true;
    }
  }

  public function argSlideDown($player)
  {
    return [
      'tiles' => Blocks::getSelectableTiles(),
    ];
  }

  public function actChooseTile($tileId)
  {
    $player = Players::getCurrent();
    $tiles = Blocks::getSelectableTiles();
    if (!in_array($tileId, $tiles)) {
      throw new \BgaVisibleSystemException('You can\'t select that tile');
    }

    $block = Blocks::slideDown($tileId);
    Notifications::slideDown($player, $tileId, $block['col']);

    $status = Globals::getSoloStatus();
    Globals::incSoloStatus();

    if($block['col'] == 4 && $block['status'] == 0) {
      Notifications::blockTriggered($tileId);
      Blocks::trigger($tileId);
      StateMachine::nextState('block');
    }
    else if ($status == 0) {
      StateMachine::nextState('tile');
    } else {
      StateMachine::nextState('play');
    }
  }
}
