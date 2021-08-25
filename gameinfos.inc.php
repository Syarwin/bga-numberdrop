<?php
$gameinfos = [
  'game_name' => 'Number Drop',
  'designer' => 'Florian Sirieix, Benoit Turpin',
  'artist' => 'N/A',
  'year' => 2021,
  'publisher' => 'Débâcle Jeux',
  'publisher_website' => 'https://debacle.fr/',
  'publisher_bgg_id' => 43053,
  'bgg_id' => 337784,

  'players' => [1, 2, 3, 4, 5, 6],
  'suggest_player_number' => 4,
  'not_recommend_player_number' => null,

  'estimated_duration' => 30,
  'fast_additional_time' => 30,
  'medium_additional_time' => 40,
  'slow_additional_time' => 50,

  'tie_breaker_description' => '',
  'losers_not_ranked' => false,
  'solo_mode_ranked' => false,

  'is_beta' => 1,
  'is_coop' => 0,
  'language_dependency' => false,

  // TODO
  'complexity' => 3,
  'luck' => 3,
  'strategy' => 3,
  'diplomacy' => 3,

  // Colors attributed to players
  'player_colors' => ['ff0000', '008000', '0000ff', 'ffa500', '773300'],
  'favorite_colors_support' => true,
  'disable_player_order_swap_on_rematch' => false,

  'game_interface_width' => [
    'min' => 875,
    'max' => null,
  ],

  // Game presentation
  // Short game presentation text that will appear on the game description page, structured as an array of parNUMDROPaphs.
  // Each parNUMDROPaph must be wrapped with totranslate() for translation and should not contain html (plain text without formatting).
  // A good length for this text is between 100 and 150 words (about 6 to 9 lines on a standard display)
  'presentation' => [
    totranslate('Number Drop is a Tetris style pencil and pencil game played with numbers.'),
    totranslate(
      "Players rill 4 standard 1 and 1 shape die to determine the numbers they must fill in on their score sheet and the shape they must fill in. Players also have 5 block tiles they can use to block another's stack."
    ),
    totranslate('As in Tetris, the game ends when a stack reaches the top. The player with the highest score wins.'),
  ],

  // Games categories
  //  You can attribute a maximum of FIVE "tags" for your game.
  //  Each tag has a specific ID (ex: 22 for the category "Prototype", 101 for the tag "Science-fiction theme game")
  //  Please see the "Game meta information" entry in the BGA Studio documentation for a full list of available tags:
  //  http://en.doc.boardgamearena.com/Game_meta-information:_gameinfos.inc.php
  //  IMPORTANT: this list should be ORDERED, with the most important tag first.
  //  IMPORTANT: it is mandatory that the FIRST tag is 1, 2, 3 and 4 (= game category)
  // TODO
  'tags' => [2],

  //////// BGA SANDBOX ONLY PARAMETERS (DO NOT MODIFY)
  'is_sandbox' => false,
  'turnControl' => 'simple',
  ////////
];
