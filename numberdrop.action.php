<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * NumberDrop implementation : © Timothée Pecatte <tim.pecatte@gmail.com>
 *
 * This code has been produced on the BGA studio platform for use on https://boardgamearena.com.
 * See http://en.doc.boardgamearena.com/Studio for more information.
 * -----
 *
 * numberdrop.action.php
 *
 * NumberDrop main action entry point
 *
 */

class action_numberdrop extends APP_GameAction
{
  // Constructor: please do not modify
  public function __default()
  {
    if (self::isArg('notifwindow')) {
      $this->view = 'common_notifwindow';
      $this->viewArgs['table'] = self::getArg('table', AT_posint, true);
    } else {
      $this->view = 'numberdrop_numberdrop';
      self::trace('Complete reinitialization of board game');
    }
  }

  public function actChangePref()
  {
    self::setAjaxMode();
    $pref = self::getArg('pref', AT_posint, false);
    $value = self::getArg('value', AT_posint, false);
    $this->game->actChangePreference($pref, $value);
    self::ajaxResponse();
  }


  public function actChangeStartingNumber()
  {
    self::setAjaxMode();
    $this->game->actChangeStartingNumber();
    self::ajaxResponse();
  }

  public function actPlaceStartingNumber()
  {
    self::setAjaxMode();
    $col = self::getArg('col', AT_posint, false);
    $this->game->actPlaceStartingNumber($col);
    self::ajaxResponse();
  }

  public function actConstructTetromino()
  {
    self::setAjaxMode();
    $tetromino = self::getArg('tetromino', AT_json, true);
    $this->validateJSonAlphaNum($tetromino, 'tetromino');
    $this->game->actConstructTetromino($tetromino);
    self::ajaxResponse();
  }

  public function actConfirmTetromino()
  {
    self::setAjaxMode();
    $this->game->actConfirmTetromino();
    self::ajaxResponse();
  }
  public function actConfirmTetrominoBlock()
  {
    self::setAjaxMode();
    $this->game->actConfirmTetrominoBlock();
    self::ajaxResponse();
  }


  public function actPassScoreCombination()
  {
    self::setAjaxMode();
    $this->game->actPassScoreCombination();
    self::ajaxResponse();
  }

  public function actConstructCombination()
  {
    self::setAjaxMode();
    $combination = self::getArg('combination', AT_json, true);
    $this->validateJSonAlphaNum($combination, 'combination');
    $this->game->actConstructCombination($combination);
    self::ajaxResponse();
  }

  public function actConfirmCombination()
  {
    self::setAjaxMode();
    $this->game->actConfirmCombination();
    self::ajaxResponse();
  }

  /////////////////////////////
  //// Confirm / pass turn ////
  /////////////////////////////
  public function actRestart()
  {
    self::setAjaxMode();
    $this->game->actCancelTurn();
    self::ajaxResponse();
  }

  public function actConfirmTurn()
  {
    self::setAjaxMode();
    $this->game->actConfirmTurn();
    self::ajaxResponse();
  }



  //////////////////
  ///// UTILS  /////
  //////////////////
  public function validateJSonAlphaNum($value, $argName = 'unknown')
  {
    if (is_array($value)) {
      foreach ($value as $key => $v) {
        $this->validateJSonAlphaNum($key, $argName);
        $this->validateJSonAlphaNum($v, $argName);
      }
      return true;
    }
    if (is_int($value)) {
      return true;
    }
    $bValid = preg_match("/^[_0-9a-zA-Z- ]*$/", $value) === 1;
    if (!$bValid) {
      throw new feException("Bad value for: $argName", true, true, FEX_bad_input_argument);
    }
    return true;
  }
}
