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

/*
 * State constants
 */
define('ST_GAME_SETUP', 1);

define('ST_PLACE_STARTING_NUMBER', 2);

define('ST_NEW_TURN', 3);
define('ST_PLAYER_TURN', 4);
define('ST_APPLY_TURNS', 5);

// Parallel flow
define('ST_CHOOSE_CARDS', 20);


define('ST_CONFIRM_TURN', 40);
define('ST_WAIT_OTHERS', 41);

define('ST_COMPUTE_SCORES', 90);
define('ST_END_GAME', 99);
