<?php
namespace NUMDROP\Core;
use NUMDROP\Managers\Players;
use NUMDROP\Helpers\Utils;
use NUMDROP\Core\Globals;

class Notifications
{
  /*************************
   **** GENERIC METHODS ****
   *************************/
  protected static function notifyAll($name, $msg, $data)
  {
    self::updateArgs($data);
    Game::get()->notifyAllPlayers($name, $msg, $data);
  }

  protected static function notify($player, $name, $msg, $data)
  {
    $pId = is_int($player) ? $player : $player->getId();
    self::updateArgs($data);
    Game::get()->notifyPlayer($pId, $name, $msg, $data);
  }

  public static function message($txt, $args = [])
  {
    self::notifyAll('message', $txt, $args);
  }

  public static function messageTo($player, $txt, $args = [])
  {
    $pId = is_int($player) ? $player : $player->getId();
    self::notify($pId, 'message', $txt, $args);
  }

  public static function throwDices($dices, $turn)
  {
    self::notifyAll('throwDices', clienttranslate('Throwing the dice for turn ${turn}'), [
      'dices' => $dices,
      'turn' => $turn,
    ]);
  }

  public static function scoreCombination($player, $type, $size, $scribbles)
  {
    $msg =
      $type == COL_SAME
        ? clienttranslate('${player_name} scores ${size} identical numbers')
        : clienttranslate('${player_name} scores ${size} consecutive numbers');

    self::notify($player, 'scoreCombination', $msg, [
      'player' => $player,
      'size' => $size,
      'scribbles' => $scribbles->toArray(),
    ]);
  }

  public static function clearTurn($player, $notifIds)
  {
    self::notify($player, 'clearTurn', clienttranslate('${player_name} restart their turn'), [
      'player' => $player,
      'turn' => Globals::getCurrentTurn(),
      'notifIds' => $notifIds,
    ]);
  }

  /*********************
   **** UPDATE ARGS ****
   *********************/
  /*
   * Automatically adds some standard field about player and/or card
   */
  protected static function updateArgs(&$data)
  {
    if (isset($data['player'])) {
      $data['player_name'] = $data['player']->getName();
      $data['player_id'] = $data['player']->getId();
      unset($data['player']);
    }
  }
}
?>
