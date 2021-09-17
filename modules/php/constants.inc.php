<?php

/*
 * Game options
 */

/*
 * User preferences
 */
define('DARK_MODE', 100);
define('DARK_MODE_DISABLED', 1);
define('DARK_MODE_ENABLED', 2);

define('CONFIRM', 101);
define('CONFIRM_TIMER', 1);
define('CONFIRM_ENABLED', 2);
define('CONFIRM_DISABLED', 3);

/*
 * State constants
 */
define('ST_GAME_SETUP', 1);

define('ST_PLACE_STARTING_NUMBER', 2);
define('ST_FINISH_SETUP', 6);

define('ST_NEW_TURN', 3);
define('ST_PLAYER_TURN', 4);
define('ST_APPLY_TURNS', 5);

// Parallel flow
define('ST_DROP_SHAPE', 20);
define('ST_SCORE_COMBINATION', 21);

// Drop
define('ST_BLOCK_PLAYER_TURN', 30);
define('ST_DROP_BLOCK', 31);

define('ST_CONFIRM_TURN', 40);
define('ST_WAIT_OTHERS', 41);

define('ST_COMPUTE_SCORES', 90);
define('ST_END_GAME', 99);


/**
 * Others
 */
define('CIRCLE', -1); // -1 will be used as a "false" number to circle stuff
define('CROSS', -2); // -2 will be used as a "false" number to cross stuff

define('CROSSED', 2); // Useful to determine if a block is circled or crossed

define('COL_END_LINES', 10);
define('COL_SAME', 11);
define('COL_SEQUENCE', 12);
define('COL_BLOCK', 13);
define('COL_BONUS', 14);

define('COL_BLOCK_STATUS', 20);
