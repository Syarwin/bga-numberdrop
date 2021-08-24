<?php
namespace NUMDROP\States;
use NUMDROP\Core\Globals;
use NUMDROP\Core\Notifications;
use NUMDROP\Core\StateMachine;
use NUMDROP\Managers\Players;
use NUMDROP\Managers\Scribbles;
use NUMDROP\Managers\Drops;

trait DropDropTrait
{
  /*
   * The arg depends on the private state of each player
   */
  function argDropDrop($player)
  {
    $drop = Drops::getNextActiveDrop();
    $dropId = Globals::getDrops()[$drop]['id'];
    return [
      'drop' => $dropId,
      'tetromino' => Globals::getTetrominos()[$player->getId()] ?? null,
    ];
  }

  function actConfirmTetrominoDrop()
  {
    $player = Players::getCurrent();
    $args = $this->argDropDrop($player);
    $tetromino = $args['tetromino'] ?? [
      'shape' => $args['drop'],
      'rotation' => 0,
      'flip' => 0,
      'col' => 2,
    ];

    // Write number while checking positions
    $blocks = $this->getShapeBlocks($tetromino, true);
    $row = $this->findLowestDropRow($player, $blocks, $tetromino['col']);

    foreach ($blocks as $pos) {
      $pos['row'] += $row;
      $pos['col'] += $tetromino['col'];
      $player->addNumber($pos['row'], $pos['col'], CROSS);
    }

    // Check completed lines
    $this->checkEndOfLines($player);

    // Move on to next state
    StateMachine::nextState('confirmWait');
  }
}
