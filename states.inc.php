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
 * states.inc.php
 *
 * NumberDrop game states description
 *
 */

//    !! It is not a good idea to modify this file when a game is running !!

$machinestates = [
  // The initial state. Please do not modify.
  ST_GAME_SETUP => [
    'name' => 'gameSetup',
    'description' => '',
    'type' => 'manager',
    'action' => 'stGameSetup',
    'transitions' => ['' => ST_START],
  ],

  ST_START => [
    'name' => 'playerTurn',
    'description' => clienttranslate('${actplayer} must play a card or pass'),
    'descriptionmyturn' => clienttranslate('${you} must play a card or pass'),
    'type' => 'activeplayer',
    'possibleactions' => ['playCard', 'pass'],
    'transitions' => ['playCard' => ST_START, 'pass' => ST_START],
  ],

  // Final state.
  // Please do not modify (and do not overload action/args methods).
  ST_END_GAME => [
    'name' => 'gameEnd',
    'description' => clienttranslate('End of game'),
    'type' => 'manager',
    'action' => 'stGameEnd',
    'args' => 'argGameEnd',
  ],
];
