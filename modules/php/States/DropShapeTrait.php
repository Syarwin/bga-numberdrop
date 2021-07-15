<?php
namespace NUMDROP\States;
use NUMDROP\Core\Globals;
use NUMDROP\Managers\Players;

trait DropShapeTrait
{
  /*
   * The arg depends on the private state of each player
   */
  function argDropShape()
  {
    return [
      'dies' => Globals::getDies(),
    ];
  }
}
