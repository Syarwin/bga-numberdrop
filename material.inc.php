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
  'S' => [
    ['    ', '  4 ', ' 23 ', ' 1  '],
    ['    ', '    ', '12  ', ' 34 '],
    ['    ', '  1 ', ' 32 ', ' 4  '],
    ['    ', '    ', ' 43 ', '  21'],
  ],

  'O' => [
    ['    ', '    ', ' 23 ', ' 14 '],
    ['    ', '    ', ' 12 ', ' 43 '],
    ['    ', '    ', ' 41 ', ' 32 '],
    ['    ', '    ', ' 34 ', ' 21 '],
  ],

  'T' => [
    ['    ', '    ', ' 1  ', '234 '],
    ['    ', '2   ', '31  ', '4   '],
    ['    ', '432 ', ' 1  ', '    '],
    ['    ', '  4 ', ' 13 ', '  2 '],
  ],

  'I' => [
    ['    ', '    ', '    ', '3124'],
    [' 3  ', ' 1  ', ' 2  ', ' 4  '],
    ['    ', '    ', '    ', '4213'],
    [' 4  ', ' 2  ', ' 1  ', ' 3  '],
  ],

  'L' => [
    ['    ', ' 4  ', ' 1  ', ' 23 '],
    ['    ', '    ', '214 ', '3   '],
    ['    ', '32  ', ' 1  ', ' 4  '],
    ['    ', '    ', '  3 ', '412 '],
  ],
];
