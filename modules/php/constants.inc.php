<?php

/*
 * Game options
 */

/*
 * User preferences
 */
const DARK_MODE = 100;
const DARK_MODE_DISABLED = 1;
const DARK_MODE_ENABLED = 2;

const CONFIRM = 101;
const CONFIRM_TIMER = 1;
const CONFIRM_ENABLED = 2;
const CONFIRM_DISABLED = 3;

/*
 * State constants
 */
const ST_GAME_SETUP = 1;

const ST_PLACE_STARTING_NUMBER = 2;
const ST_FINISH_SETUP = 6;

const ST_NEW_TURN = 3;
const ST_PLAYER_TURN = 4;
const ST_APPLY_TURNS = 5;

// Parallel flow
const ST_DROP_SHAPE = 20;
const ST_SCORE_COMBINATION = 21;

// Drop
const ST_BLOCK_PLAYER_TURN = 30;
const ST_DROP_BLOCK = 31;

const ST_CONFIRM_TURN = 40;
const ST_WAIT_OTHERS = 41;

const ST_COMPUTE_SCORES = 90;
const ST_END_GAME = 99;

const ST_SOLO_PLAYER_TURN = 50;
const ST_SLIDE_DOWN = 51;

/**
 * Others
 */
const CIRCLE = -1; // -1 will be used as a "false" number to circle stuff
const CROSS = -2; // -2 will be used as a "false" number to cross stuff

const CROSSED = 2; // Useful to determine if a block is circled or crossed

const COL_END_LINES = 10;
const COL_SAME = 11;
const COL_SEQUENCE = 12;
const COL_BLOCK = 13;
const COL_BONUS = 14;

const COL_BLOCK_STATUS = 20;
