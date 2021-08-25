<?php
namespace NUMDROP\States;
use NUMDROP\Core\Globals;
use NUMDROP\Core\Notifications;
use NUMDROP\Core\StateMachine;
use NUMDROP\Managers\Players;
use NUMDROP\Managers\Scribbles;
use NUMDROP\Managers\Blocks;

trait DropBlockTrait
{
  /*
   * The arg depends on the private state of each player
   */
  function argDropBlock($player)
  {
    $block = Blocks::getNextActiveBlock();
    $blockId = Globals::getBlocks()[$block]['id'];
    return [
      'block' => $blockId,
      'tetromino' => Globals::getTetrominos()[$player->getId()] ?? null,
    ];
  }

  function actConfirmTetrominoBlock()
  {
    $player = Players::getCurrent();
    $args = $this->argDropBlock($player);
    $tetromino = $args['tetromino'] ?? [
      'shape' => $args['block'],
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
