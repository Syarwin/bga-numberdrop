<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * NumberDrop implementation : © <Your name here> <Your email address here>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * numberdrop.game.php
 *
 * This is the main file for your game logic.
 *
 * In this PHP file, you are going to defines the rules of the game.
 *
 */

require_once APP_GAMEMODULE_PATH . 'module/table/table.game.php';

$swdNamespaceAutoload = function ($class) {
  $classParts = explode('\\', $class);
  if ($classParts[0] == 'NUMDROP') {
    array_shift($classParts);
    $file = dirname(__FILE__) . '/modules/php/' . implode(DIRECTORY_SEPARATOR, $classParts) . '.php';
    if (file_exists($file)) {
      require_once $file;
    } else {
      var_dump('Cannot find file : ' . $file);
    }
  }
};
spl_autoload_register($swdNamespaceAutoload, true, true);

use NUMDROP\Managers\Players;
use NUMDROP\Managers\Blocks;
use NUMDROP\Core\Globals;
use NUMDROP\Core\Preferences;
use NUMDROP\Core\Stats;
use NUMDROP\Helpers\Log;

class NumberDrop extends Table
{
  use NUMDROP\States\StartingNumberTrait;
  use NUMDROP\States\NewTurnTrait;
  use NUMDROP\States\DropShapeTrait;
  use NUMDROP\States\DropBlockTrait;
  use NUMDROP\States\ScoreCombinationTrait;
  use NUMDROP\States\ConfirmWaitTrait;
  use NUMDROP\States\EndOfGameTrait;
  use NUMDROP\States\SoloModeTrait;

  public static $instance = null;
  function __construct()
  {
    parent::__construct();
    self::$instance = $this;
    self::initGameStateLabels([]);
  }

  public static function get()
  {
    return self::$instance;
  }

  protected function getGameName()
  {
    return 'numberdrop';
  }

  /*
   * setupNewGame:
   */
  protected function setupNewGame($players, $options = [])
  {
    Players::setupNewGame($players, $options);
    Blocks::setupNewGame($players, $options);
    Stats::setupNewGame();
    Globals::setupNewGame($players, $options);
    Preferences::setupNewGame($players, $this->player_preferences);
    $this->gamestate->setAllPlayersMultiactive();
    $this->activeNextPlayer();
  }

  /*
   * getAllDatas:
   */
  public function getAllDatas()
  {
    $pId = self::getCurrentPId();
    return [
      'prefs' => Preferences::getUiData($pId),
      'players' => Players::getUiData($pId),
      'dices' => Globals::getDices(),
      'turn' => Globals::getCurrentTurn(),
      'shapes' => $this->shapes,
      'blockShapes' => $this->blockShapes,
      'canceledNotifIds' => Log::getCanceledNotifIds(),
      'blocks' => Globals::getBlocks(),
    ];
  }

  /*
   * getGameProgression:
   */
  function getGameProgression()
  {
    $maxTurn = 77 / 4;
    $turn = Globals::getCurrentTurn();
    return ($turn / $maxTurn) * 100;
  }

  function actChangePreference($pref, $value)
  {
    Preferences::set($this->getCurrentPId(), $pref, $value);
  }

  ///////////////////////////
  //// DEBUG FUNCTIONS //////
  ///////////////////////////

  ////////////////////////////////////
  ////////////   Zombie   ////////////
  ////////////////////////////////////
  /*
   * zombieTurn:
   *   This method is called each time it is the turn of a player who has quit the game (= "zombie" player).
   *   You can do whatever you want in order to make sure the turn of this player ends appropriately
   */
  public function zombieTurn($state, $activePlayer)
  {
    $statename = $state['name'];

    if ($state['type'] === 'activeplayer') {
      $this->gamestate->nextState('zombiePass');
    } elseif ($state['type'] === 'multipleactiveplayer') {
      // Make sure player is in a non blocking status for role turn
      $this->gamestate->setPlayerNonMultiactive($activePlayer, '');
    }
  }

  /////////////////////////////////////
  //////////   DB upgrade   ///////////
  /////////////////////////////////////
  // You don't have to care about this until your game has been published on BGA.
  // Once your game is on BGA, this method is called everytime the system detects a game running with your old Database scheme.
  // In this case, if you change your Database scheme, you just have to apply the needed changes in order to
  //   update the game database and allow the game to continue to run with your new version.
  /////////////////////////////////////
  /*
   * upgradeTableDb
   *  - int $from_version : current version of this game database, in numerical form.
   *      For example, if the game was running with a release of your game named "140430-1345", $from_version is equal to 1404301345
   */
  public function upgradeTableDb($from_version)
  {
    if($from_version <= 2108252342){
      $sql = "UPDATE `DBPREFIX_scribbles` SET number = -2 WHERE number = -1";
      self::applyDbUpgradeToAllDB( $sql );

      $sql = "UPDATE `DBPREFIX_scribbles` SET number = -1 WHERE number = 0";
      self::applyDbUpgradeToAllDB( $sql );
    }
  }

  /////////////////////////////////////////////////////////////
  // Exposing protected methods, please use at your own risk //
  /////////////////////////////////////////////////////////////

  // Exposing protected method getCurrentPlayerId
  public static function getCurrentPId()
  {
    return self::getCurrentPlayerId();
  }

  // Exposing protected method translation
  public static function translate($text)
  {
    return self::_($text);
  }
}
