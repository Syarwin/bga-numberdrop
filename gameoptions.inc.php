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
 * gameoptions.inc.php
 *
 * NumberDrop game options description
 *
 * In this file, you can define your game options (= game variants).
 *
 * Note: If your game has no variant, you don't have to modify this file.
 *
 * Note²: All options defined in this file should have a corresponding "game state labels"
 *        with the same ID (see "initGameStateLabels" in numberdrop.game.php)
 *
 * !! It is not a good idea to modify this file when a game is running !!
 *
 */

namespace NUMDROP;

require_once 'modules/php/constants.inc.php';

$game_options = [];

$game_preferences = [
  DARK_MODE => [
    'name' => totranslate('Dark mode'),
    'needReload' => false,
    'values' => [
      DARK_MODE_DISABLED => ['name' => totranslate('Disabled')],
      DARK_MODE_ENABLED => ['name' => totranslate('Enabled')],
    ],
  ],

  CONFIRM => [
    'name' => totranslate('Turn confirmation'),
    'needReload' => false,
    'values' => [
      CONFIRM_TIMER => ['name' => totranslate('Enabled with timer')],
      CONFIRM_ENABLED => ['name' => totranslate('Enabled')],
      CONFIRM_DISABLED => ['name' => totranslate('Disabled')],
    ],
  ],
];
