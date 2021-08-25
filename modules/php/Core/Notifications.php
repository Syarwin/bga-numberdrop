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

  public static function blockTriggered($block)
  {
    $letters = ['A', 'B', 'C', 'D', 'E'];
    self::notifyAll('blockTriggered', clienttranslate('Block ${letter} is triggered!'), [
      'letter' => $letters[$block],
      'block' => $block,
    ]);
  }

  public static function finishBlock($block, $scribbles)
  {
    self::notifyAll('finishBlock', '', [
      'block' => $block,
      'scribbles' => $scribbles->toArray(),
    ]);
  }

  public static function scoreCombination($player, $type, $size, $scribbles, $scores)
  {
    $msg =
      $type == COL_SAME
        ? clienttranslate('${player_name} scores ${size} identical numbers')
        : clienttranslate('${player_name} scores ${size} consecutive numbers');

    self::notify($player, 'scoreCombination', $msg, [
      'player' => $player,
      'size' => $size,
      'scribbles' => $scribbles->toArray(),
      'scores' => $scores,
    ]);
  }

  public static function scoreLine($player, $line, $scribble, $scores)
  {
    self::notify(
      $player,
      'scoreLine',
      clienttranslate('${player_name} scores 2 points for completing the line n°${i}'),
      [
        'player' => $player,
        'scores' => $scores,
        'i' => $line + 1,
        'scribble' => $scribble,
      ]
    );
  }

  public static function scoreNegativeLine($player, $line, $scribble, $scores)
  {
    self::notify(
      $player,
      'scoreLine',
      clienttranslate('${player_name} scores -5 points for reaching the line n°${i}'),
      [
        'player' => $player,
        'scores' => $scores,
        'i' => $line + 1,
        'scribble' => $scribble,
      ]
    );
  }

  public static function clearTurn($player, $notifIds, $scores)
  {
    self::notify($player, 'clearTurn', clienttranslate('${player_name} restart their turn'), [
      'player' => $player,
      'turn' => Globals::getCurrentTurn(),
      'notifIds' => $notifIds,
      'scores' => $scores,
    ]);
  }

  public static function updatePlayersData($scribbles, $scores)
  {
    self::notifyAll('updatePlayersData', '', [
      'scribbles' => $scribbles->toArray(),
      'scores' => $scores,
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
