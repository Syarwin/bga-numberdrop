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
 * material.inc.php
 *
 * NumberDrop game material description
 *
 */

require_once 'modules/php/constants.inc.php';

$this->shapes = [
  'S' => [[' 0 ', ' 12', '  3'], [' 23', '01 ', '   '], ['3  ', '21 ', ' 0 '], ['   ', ' 10', '32 ']],

  'O' => [
    [' 03 ', ' 12 ', '    ', '    '],
    [' 32 ', ' 01 ', '    ', '    '],
    [' 21 ', ' 30 ', '    ', '    '],
    [' 10 ', ' 23 ', '    ', '    '],
  ],

  'T' => [['123', ' 0 ', '   '], ['3  ', '20 ', '1  '], ['   ', ' 0 ', '321'], ['  1', ' 02', '  3']],

  'I' => [
    ['2013', '    ', '    ', '    '],
    [' 3  ', ' 1  ', ' 0  ', ' 2  '],
    ['3102', '    ', '    ', '    '],
    [' 2  ', ' 0  ', ' 1  ', ' 3  '],
  ],

  'L' => [[' 12', ' 0 ', ' 3 '], ['2  ', '103', '   '], [' 3 ', ' 0 ', '21 '], ['   ', '301', '  2']],
];

$this->blockShapes = [
  0 => [['0 ', '12'], [' 2', '01'], ['21', ' 0'], ['10', '2 ']],
  1 => [[' 0 ', ' 1 ', ' 2 '], ['   ', '012', '   '], [' 0 ', ' 1 ', ' 2 '], ['   ', '012', '   ']],
  2 => [['304', '1 2', '   '], ['42 ', '0  ', '31 '], ['   ', '2 1', '403'], [' 13', '  0', ' 24']],
  3 => [[' 0 ', '314', ' 2 '], [' 4 ', '012', ' 3 '], [' 2 ', '413', ' 0 '], [' 3 ', '210', ' 4 ']],
  4 => [['304', ' 1 ', ' 2 '], ['4  ', '012', '3  '], [' 2 ', ' 1 ', '403'], ['  3', '210', '  4']],
];
