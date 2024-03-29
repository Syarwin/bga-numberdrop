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
    'descriptionmyturn' => clienttranslate('${you} must place your starting number (${n}) on the bottom line'),
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
      'block' => ST_BLOCK_PLAYER_TURN,
      'play' => ST_PLAYER_TURN,
      'solo' => ST_SOLO_PLAYER_TURN,
    ],
  ],

  ST_PLAYER_TURN => [
    'name' => 'playerTurn',
    'description' => clienttranslate('Waiting for other players to end their turn.'),
    'descriptionmyturn' => '', // Won't be displayed anyway
    'type' => 'multipleactiveplayer',
    'parallel' => ST_DROP_SHAPE, // Allow to have parallel flow for each player
    'args' => 'argPlayerTurn',
    'transitions' => ['applyTurns' => ST_APPLY_TURNS],
  ],

  /****************************
   ***** PARALLEL STATES *******
   ****************************/

  ST_DROP_SHAPE => [
    'name' => 'dropShape',
    'descriptionmyturn' => clienttranslate('${you} must construct and drop your tetromino'),
    'type' => 'private',
    'args' => 'argDropShape',
    'possibleactions' => ['actConstructTetromino', 'actConfirmTetromino', 'actRestart'],
    'transitions' => [
      'scoreCombination' => ST_SCORE_COMBINATION,
      'restart' => ST_DROP_SHAPE,
      'restartSolo' => ST_SLIDE_DOWN,
    ],
  ],

  ST_SCORE_COMBINATION => [
    'name' => 'scoreCombination',
    'descriptionmyturn' => clienttranslate('${you} may score a combination'),
    'type' => 'private',
    'action' => 'stScoreCombination',
    'args' => 'argScoreCombination',
    'possibleactions' => ['actConstructCombination', 'actConfirmCombination', 'actPassScoreCombination', 'actRestart'],
    'transitions' => [
      'confirmWait' => ST_CONFIRM_TURN,
      'restart' => ST_DROP_SHAPE,
      'restartSolo' => ST_SLIDE_DOWN,
    ],
  ],

  /****************************
   ******* DROP a DROP ********
   ****************************/
  ST_BLOCK_PLAYER_TURN => [
    'name' => 'playerTurn',
    'description' => clienttranslate('Waiting for other players to end their turn.'),
    'descriptionmyturn' => '', // Won't be displayed anyway
    'type' => 'multipleactiveplayer',
    'parallel' => ST_DROP_BLOCK, // Allow to have parallel flow for each player
    'args' => 'argPlayerTurn',
    'transitions' => ['applyTurns' => ST_APPLY_TURNS],
  ],

  ST_DROP_BLOCK => [
    'name' => 'dropBlock',
    'descriptionmyturn' => clienttranslate('${you} must drop the block'),
    'type' => 'private',
    'args' => 'argDropBlock',
    'possibleactions' => ['actConstructTetromino', 'actConfirmTetrominoBlock'],
    'transitions' => [
      'confirmWait' => ST_CONFIRM_TURN,
      'restartSolo' => ST_SLIDE_DOWN,
      'slide' => ST_SLIDE_DOWN,
      'play' => ST_DROP_SHAPE,
    ],
  ],

  /****************************
   ******** SOLO MODE *********
   ****************************/
  ST_SOLO_PLAYER_TURN => [
    'name' => 'playerTurn',
    'description' => clienttranslate('Waiting for other players to end their turn.'),
    'descriptionmyturn' => '', // Won't be displayed anyway
    'type' => 'multipleactiveplayer',
    'parallel' => ST_SLIDE_DOWN, // Allow to have parallel flow for each player
    'args' => 'argPlayerTurn',
    'transitions' => ['applyTurns' => ST_APPLY_TURNS],
  ],

  ST_SLIDE_DOWN => [
    'name' => 'slideDown',
    'descriptionmyturn' => clienttranslate('${you} must slide a tile'),
    'type' => 'private',
    'args' => 'argSlideDown',
    'action' => 'stSlideDown',
    'possibleactions' => ['actChooseTile'],
    'transitions' => [
      'play' => ST_DROP_SHAPE,
      'tile' => ST_SLIDE_DOWN,
      'block' => ST_DROP_BLOCK,
      'restartSolo' => ST_SLIDE_DOWN,
    ],
  ],

  //////////////////////////
  ///// CONFIRM / END //////
  //////////////////////////

  // Pre-end of parallel flow
  ST_CONFIRM_TURN => [
    'name' => 'confirmTurn',
    'descriptionmyturn' => clienttranslate('${you} must confirm or restart your turn'),
    'type' => 'private',
    'action' => 'stConfirmTurn',
    //    'args' => 'argPrivatePlayerTurn',
    'possibleactions' => ['actConfirmTurn', 'actRestart'],
    'transitions' => [
      'confirm' => ST_WAIT_OTHERS,
      'restart' => ST_DROP_SHAPE,
      'restartBlock' => ST_DROP_BLOCK,
      'restartSolo' => ST_SLIDE_DOWN,
    ],
  ],

  // Waiting other
  ST_WAIT_OTHERS => [
    'name' => 'waitOthers',
    'descriptionmyturn' => '',
    'type' => 'private',
    'action' => 'stWaitOther',
    'args' => 'argPrivatePlayerTurn',
    'possibleactions' => ['actRestart'],
    'transitions' => [
      'restart' => ST_DROP_SHAPE,
      'restartBlock' => ST_DROP_BLOCK,
      'restartSolo' => ST_SLIDE_DOWN,
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
