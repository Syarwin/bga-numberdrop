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
}
