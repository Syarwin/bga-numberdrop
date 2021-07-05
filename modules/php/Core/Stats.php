<?php
namespace NUMDROP\Core;

class Stats
{
  /*************************
   **** GENERIC METHODS ****
   *************************/
  protected static function init($type, $name, $value = 0)
  {
    Game::get()->initStat($type, $name, $value);
  }

  public static function inc($name, $player = null, $value = 1, $log = true)
  {
    $pId = is_null($player) ? null : (is_int($player) ? $player : $player->getId());
    Game::get()->incStat($value, $name, $pId);
  }

  protected static function get($name, $player = null)
  {
    Game::get()->getStat($name, $player);
  }

  protected static function set($value, $name, $player = null)
  {
    $pId = is_null($player) ? null : (is_int($player) ? $player : $player->getId());
    Game::get()->setStat($value, $name, $pId);
  }

  /*********************
   **********************
   *********************/
  public static function setupNewGame()
  {
    // TODO
    /*
    $stats = NUMDROPicola::get()->getStatTypes();

//    self::init('table', 'turns_number');

    foreach ($stats['player'] as $key => $value) {
      if($value['id'] > 10 && $value['type'] == 'int')
        self::init('player', $key);
    }
    */
  }
}

?>
