<?php
namespace NUMDROP\States;
use NUMDROP\Core\Globals;
use NUMDROP\Core\StateMachine;
use NUMDROP\Managers\Players;

trait DropShapeTrait
{
  /*
   * The arg depends on the private state of each player
   */
  function argDropShape($player)
  {
    return [
      'dices' => Globals::getDices(),
      'tetromino' => Globals::getTetrominos()[$player->getId()] ?? null,
    ];
  }

  function actConstructTetromino($tetromino)
  {
    $player = Players::getCurrent();
    $tetrominos = Globals::getTetrominos();
    $tetrominos[$player->getId()] = $tetromino;
    Globals::setTetrominos($tetrominos);
  }

  function actConfirmTetromino()
  {
    $player = Players::getCurrent();
    $args = $this->argDropShape($player);
    $tetromino = $args['tetromino'];

    // Check shape
    if($args['dices'][4] != '*' && $args['dices'][4] != $tetromino['shape']){
      throw new \BgaVisibleSystemException('You can\'t select that shape');
    }

    // Check numbers
    $diceValues = array_slice($args['dices'], 0, 4);
    for($i = 0; $i < 4; $i++){
      $pos = \array_search($tetromino['numbers'][$i], $diceValues);
      if($pos === false){
        $pos = \array_search('*', $diceValues);
      }
      if($pos === false){
        throw new \BgaVisibleSystemException('A number of the tetromino is invalid');
      }

      unset($diceValues[$pos]);
    }

    // Write number while checking positions
    $blocks = $this->getShapeBlocks($tetromino);
    $row = $this->findLowestDropRow($player, $blocks, $tetromino['col']);

    foreach($blocks as $pos){
      $pos['row'] += $row;
      $pos['col'] += $tetromino['col'];
      $player->addNumber($pos['row'], $pos['col'], $pos['n']);
    }

    // Move on to next state
    StateMachine::nextState("scoreCombination");
  }


  /**
   * Same function as in js : convert a tetromino to array of blocks
   */
  function getShapeBlocks($tetromino)
  {
    $shape = $this->shapes[$tetromino['shape']][$tetromino['rotation']];
    $n = count($shape);

    $res = [];
    for($i = 0; $i < $n; $i++){
      for($j = 0; $j < $n; $j++){
        $y = $tetromino['flip'] == 0? $j : ($n - $j - 1);
        $id = $shape[$i][$y];
        if($id != ' '){
          $res[] = [
            'row' => $i,
            'col' => $j,
            'n' => $tetromino['numbers'][$id],
          ];
        }
      }
    }

    return $res;
  }


  /**
   * Same function as in js : given current shape and col, find lowest row before it's blocked
   */
  function findLowestDropRow($player, $tetrominoBlocks, $column) {
    $board = $player->getBoard();

    for ($i = 11; $i > -3; $i--) {
      $collision = false;
      foreach($tetrominoBlocks as $pos){
        $pos['row'] += $i;
        $pos['col'] += $column;
        if($board[$pos['row']][$pos['col']] !== null){
          $collision = true;
        }
      }

      if($collision){
        return $i + 1;
      }
    }

    return 0;
  }
}
