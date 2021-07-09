/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * NumberDrop implementation : © Timothée Pecatte <tim.pecatte@gmail.com>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * numberdrop.js
 *
 * NumberDrop user interface script
 *
 *
 */

var isDebug = window.location.host == 'studio.boardgamearena.com' || window.location.hash.indexOf('debug') > -1;
var debug = isDebug ? console.info.bind(window.console) : function () {};

define([
  'dojo',
  'dojo/_base/declare',
  'ebg/counter',
  g_gamethemeurl + 'modules/js/Core/game.js',
  g_gamethemeurl + 'modules/js/Core/modal.js',
], function (dojo, declare, noUiSlider, sortable) {
  let DARK_MODE = 100;
  let DARK_MODE_DISABLED = 1;
  let DARK_MODE_ENABLED = 2;


  return declare('bgagame.numberdrop', [customgame.game], {
    constructor() {
      this._activeStates = [];
      this._notifications = [
        /*
           ['placeFarmer', 1000],
           ['addFences', null],
           */
      ];

      // TODO
      // Fix mobile viewport (remove CSS zoom)
      // this.default_viewport = 'width=1000';
    },

    /**
     * Setup:
     *	This method set up the game user interface according to current game situation specified in parameters
     *	The method is called each time the game interface is displayed to a player, ie: when the game starts and when a player refreshes the game page (F5)
     *
     * Params :
     *	- mixed gamedatas : contains all datas retrieved by the getAllDatas PHP method.
     */
    setup(gamedatas) {
      debug('SETUP', gamedatas);
      // TODO
      dojo.place("<div id='numberdrop-topbar'></div>", 'topbar', 'after');

      this.place('tplScoreSheet', gamedatas.players[this.player_id], 'test');
      this.addDarkModeSwitch();
      this.inherited(arguments);
    },


    tplScoreSheet(player){
      let cells = '';
      for(let i = 13; i >= 0; i--){
        for(let j = 0; j < 7; j++){
          cells += `<div class='nd-cell' data-col='${j}' data-row='${i}' id='cell-${player.id}-${i}-${j}'></div>`;
        }
      }

      return `
      <div class="sheet-wrapper" id='sheet-${player.id}'>
        <div class="grid-wrapper">
          <div class="nd-grid">
            ${cells}
          </div>
        </div>
      </div>
      `;
    },



    onPreferenceChange(pref, value) {
      if(pref == DARK_MODE)
        this.toggleDarkMode(value == DARK_MODE_ENABLED, false);
    },

    toggleGrid(){
      this.setPreferenceValue(DISPLAY_GRID, $('chk-grid').checked? GRID_HIDDEN : GRID_VISIBLE)
    },

    toggleDarkMode(enabled){
      if(enabled){
        dojo.query("html").addClass("darkmode");
        $('chk-darkmode').checked = true;
      } else {
        dojo.query("html").removeClass("darkmode");
        $('chk-darkmode').checked = false;
      }
    },

    addDarkModeSwitch(){
      // Darkmode switch
      dojo.place(`
        <div class='upperrightmenu_item' id="darkmode-switch">
          <input type="checkbox" class="checkbox" id="chk-darkmode" />
          <label class="label" for="chk-darkmode">
            <div class="ball"></div>
          </label>
        </div>
        `, 'upperrightmenu', 'first');

      dojo.connect(
        $('chk-darkmode'),
        'onchange',
        () => this.setPreferenceValue(DARK_MODE, $('chk-darkmode').checked? DARK_MODE_ENABLED : DARK_MODE_DISABLED)
      );

      this.toggleDarkMode(this.prefs[DARK_MODE].value == DARK_MODE_ENABLED);
    },

  });
});
