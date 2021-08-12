<?php
namespace NUMDROP\States;
use NUMDROP\Core\Globals;
use NUMDROP\Core\Notifications;
use NUMDROP\Core\StateMachine;
use NUMDROP\Managers\Players;
use NUMDROP\Managers\Scribbles;

trait DropDropTrait
{
  /*
   * The arg depends on the private state of each player
   */
  function argDropDrop($player)
  {
    return [];
  }
}
