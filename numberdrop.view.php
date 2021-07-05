<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * NumberDrop implementation : © Timothée Pecatte <tim.pecatte@gmail.com>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * numberdrop.view.php
 *
 */

require_once APP_BASE_PATH . 'view/common/game.view.php';

class view_numberdrop_numberdrop extends game_view
{
  function getGameName()
  {
    return 'numberdrop';
  }
  function build_page($viewArgs)
  {
  }
}
