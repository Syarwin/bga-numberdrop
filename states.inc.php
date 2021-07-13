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
    'transitions' => ['' => ST_PLACE_STARTING_NUMBER],
  ],

  ST_PLACE_STARTING_NUMBER => [
    'name' => 'placeStartingNumber',
    'description' => clienttranslate('Waiting for other players to place their starting number'),
    'descriptionmyturn' => clienttranslate('${you} must place your starting number on the bottom line'),
    'type' => 'multipleactiveplayer',
    'args' => 'argsStartingNumber',
    'possibleactions' => ['actChangeStartingNumber', 'actPlaceStartingNumber'],
    'transitions' => ['' => ST_FINISH_SETUP],
  ],

  ST_FINISH_SETUP => [
    'name' => 'finishSetup',
    'description' => '',
    'type' => 'game',
    'action' => 'stFinishSetup',
    'transitions' => [
      '' => ST_NEW_TURN,
    ],
  ],

  ST_NEW_TURN => [
    'name' => 'newTurn',
    'description' => clienttranslate('A new turn is starting'),
    'type' => 'game',
    'updateGameProgression' => true,
    'action' => 'stNewTurn',
    'transitions' => [
      'playerTurn' => ST_PLAYER_TURN,
    ],
  ],

  ST_PLAYER_TURN => [
    'name' => 'playerTurn',
    'description' => clienttranslate('Waiting for other players to end their turn.'),
    'descriptionmyturn' => '', // Won't be displayed anyway
    'type' => 'multipleactiveplayer',
    'parallel' => ST_CHOOSE_CARDS, // Allow to have parallel flow for each player
    'action' => 'stPlayerTurn',
    'args' => 'argPlayerTurn',
    'possibleactions' => ['registerPlayerTurn'],
    'transitions' => ['applyTurns' => ST_APPLY_TURNS],
  ],

  /****************************
   ***** PARALLEL STATES *******
   ****************************/

  ST_CHOOSE_CARDS => [
    'name' => 'chooseCards',
    'descriptionmyturn' => clienttranslate('${you} must pick a pair of construction cards'),
    'type' => 'private',
    'args' => 'argChooseCards',
    'possibleactions' => ['chooseCards', 'refusal', 'roundabout', 'restart'],
    'transitions' => [],
  ],

  //////////////////////////
  ///// CONFIRM / END //////
  //////////////////////////

  // Pre-end of parallel flow
  ST_CONFIRM_TURN => [
    'name' => 'confirmTurn',
    'descriptionmyturn' => clienttranslate('${you} must confirm or restart your turn'),
    'type' => 'private',
    'args' => 'argPrivatePlayerTurn',
    'possibleactions' => ['confirm', 'restart'],
    'transitions' => [
      'confirm' => ST_WAIT_OTHERS,
      'restart' => ST_CHOOSE_CARDS,
    ],
  ],

  // Waiting other
  ST_WAIT_OTHERS => [
    'name' => 'waitOthers',
    'descriptionmyturn' => '',
    'type' => 'private',
    'action' => 'stWaitOther',
    'args' => 'argPrivatePlayerTurn',
    'possibleactions' => ['restart'],
    'transitions' => [
      'restart' => ST_CHOOSE_CARDS,
    ],
  ],

  /****************************
   ****************************/

  ST_APPLY_TURNS => [
    'name' => 'applyTurns',
    'description' => clienttranslate('Here is what each player has done during this turn.'),
    'type' => 'game',
    'action' => 'stApplyTurn',
    'transitions' => [
      'newTurn' => ST_NEW_TURN,
      'endGame' => ST_COMPUTE_SCORES,
    ],
  ],

  /****************************
   ********* END OF GAME *******
   ****************************/

  ST_COMPUTE_SCORES => [
    'name' => 'computeScores',
    'description' => clienttranslate('Let\'s compute the scores and tie breakes'),
    'type' => 'game',
    'action' => 'stComputeScores',
    'transitions' => [
      'endGame' => ST_END_GAME,
    ],
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