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
  g_gamethemeurl + 'modules/js/ShapeConstructor.js',
  g_gamethemeurl + 'modules/js/ScoreCombination.js',
], function (dojo, declare) {
  let DARK_MODE = 100;
  let DARK_MODE_DISABLED = 1;
  let DARK_MODE_ENABLED = 2;

  return declare('bgagame.numberdrop', [customgame.game, numberdrop.shapeConstructor, numberdrop.scoreCombination], {
    constructor() {
      this._activeStates = [];
      this._notifications = [
        ['throwDices', 2500],
        ['scoreCombination', 1500],
        ['scoreLine', 1500],
        ['clearTurn', 10],
      ];
      this._listeningCells = [];

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
      dojo.place("<div id='numberdrop-topbar'></div>", 'topbar', 'after');

      this.setupScoreSheets();
      this.setupBoard();
      this.setupDices();
      this.addDarkModeSwitch();
      this.updateTurnNumber();
      if (!this.isReadOnly()) {
        dojo.connect($('overall-content'), 'click', () => this.clearDial());
      }
      this.inherited(arguments);
    },

    clearPossible() {
      this.clearDial();
      this.toggleShapeConstructor(false);
      dojo.query('.nd-cell').removeClass('active selected');

      this._listeningCells.forEach((listener) => dojo.disconnect(listener));
      this._listeningCells = [];

      this.inherited(arguments);
    },

    updateTurnNumber() {
      $('game_play_area').setAttribute('data-turn', this.gamedatas.turn);
    },

    /**************************************
     ************** Board *****************
     **************************************/
    setupBoard() {
      this.place('tplBoard', {}, 'main-holder');

      // Add drop tiles
      this.gamedatas.drops.forEach((drop, i) => {
        dojo.place(`
          <div class='drop-tile ${drop.status? "flipped" : ""}' id='drop-tile-${i}' data-id='${drop.id}'></div>
        `, `drop-tile-holder-${i}`);
      })
    },

    tplBoard() {
      return (
        `
      <div id="board">
        <div id="dice-holder"></div>
        <div id="drops-container">
          ` +
        [0, 1, 2, 3, 4]
          .map(
            (i) => `
              <div id="drop-${i}" class="drop">
                <div class="drop-header">
                  <div class="drop-header-bg"></div>
                  <div class="drop-header-letter"></div>
                </div>
                <div id='drop-tile-holder-${i}' class="drop-tile-holder"></div>
              </div>
            `,
          )
          .join('') +
        `
        </div>
      </div>
      `
      );
    },

    /**************************************
     **************************************
     ************ Scoresheets *************
     **************************************
     **************************************/

    /**
     * Setup scoresheets
     */
    setupScoreSheets() {
      let players = Object.values(this.gamedatas.players);
      var nPlayers = players.length;
      var currentPlayerNo = players.reduce((carry, player) => (player.id == this.player_id) ? player.no : carry, 0);

      this.forEachPlayer((player) => {
        player.no = (player.no + nPlayers - currentPlayerNo) % nPlayers;

        this.place('tplScoreSheet', player, 'main-holder');
        player.scribbles.forEach((scribble) => this.addScribble(scribble));
        this.highlightScoringCombinations(player.id);
      });
    },


    /**
     * Add a scribble onto someone scoresheet
     */
    addScribble(scribble) {
      let cell = this.getCell(scribble);
      if (!cell) {
        console.error('Trying to get an undefined cell to place scribble', scribble);
        return;
      }

      // > 0 number => this is a number in the grid of the player
      if (scribble.number > 0) {
        this.setCellContent(cell, scribble.number, scribble.turn);
      }
      // 0 => circled stuff
      else if (scribble.number == 0) {
        cell.setAttribute('data-circled', scribble.turn);
      }
    },

    /**
     * ScoreSheet template
     */
    tplScoreSheet(player) {
      let cells = '';
      let endOfLines = '';
      for (let i = 13; i >= 0; i--) {
        for (let j = 0; j < 7; j++) {
          cells += `<div class='nd-cell' data-col='${j}' data-row='${i}' id='cell-${player.id}-${i}-${j}'></div>`;
        }

        let n = i < 11 ? '+2' : '-5';
        endOfLines += `
        <div class='nd-cell' data-col='10' data-row='${i}' id='cell-${player.id}-${i}-10' data-n='${n}'>
          <svg viewBox="100 0 700 512" class="scribble-circle hidden"><use class="scribble-path" href="#scribble-circle-svg" /></svg>
        </div>
        `;
      }
      let current = player.id == this.player_id ? 'current' : '';

      let shapeConstructor = '';
      if (player.id == this.player_id) {
        shapeConstructor = this.tplShapeConstructor();
      }

      let combinations = '';
      ['identical', 'sequence', 'drop'].forEach((type) => {
        combinations +=
          `
        <div class="sheet-bonus-${type}">
          <div class="sheet-bonus-header"></div>
          <div class="sheet-bonus-grid">
          ` +
          [0, 1, 2, 3, 4, 5]
            .map(
              (i) => `
              <div class="sheet-bonus-cell" id="cell-${player.id}-${i}-${type}">
                <svg viewBox="100 0 700 512" class="scribble-circle hidden"><use class="scribble-path" href="#scribble-circle-svg" /></svg>
              </div>`,
            )
            .join('') +
          `
          </div>
        </div>
        `;
      });

      return `
      <div class="sheet-wrapper ${current}" id='sheet-${player.id}' data-no='${player.no}'>
        <div class="sheet-top">
          <div class="grid-wrapper">
            <div class="nd-grid">
              ${cells}
            </div>
          </div>

          <div class="end-of-lines">
            ${endOfLines}
          </div>

          <div class="sheet-right-column">
            <div class="shape-constructor">
              ${shapeConstructor}
            </div>
            <div class="sheet-bonus">
              ${combinations}
            </div>
          </div>
        </div>

        <div class="sheet-bottom">
          <div class="sheet-player-name" style="color:#${player.color}">
            <span class="robot"></span> ${player.name}
          </div>
        </div>
      </div>
      `;
    },

    getGrid(pId = null) {
      pId = pId || this.player_id;
      return document.querySelector('#sheet-' + pId + ' .sheet-top .grid-wrapper .nd-grid');
    },

    getCell(row, col = null, pId = null) {
      if (col == null) {
        // We can also call with a single argument containing row and col and pId
        pId = pId || row.pId;
        col = row.col;
        row = row.row;
      }

      if (col > 10) {
        let cols = {
          11: 'identical',
          12: 'sequence',
          13: 'drop',
        };
        col = cols[col];
      }

      pId = pId || this.player_id;
      return $(`cell-${pId}-${row}-${col}`);
    },

    getCellObj(cell) {
      return {
        row: cell.getAttribute('data-row'),
        col: cell.getAttribute('data-col'),
        n: cell.getAttribute('data-n'),
      };
    },

    getCellContent(row, col = null) {
      let cell = this.getCell(row, col);
      if (cell == null) {
        return null;
      }
      return cell.getAttribute('data-n') || '';
    },

    setCellContent(cell, n, turn = 0) {
      cell.setAttribute('data-n', n);
      cell.setAttribute('data-turn', turn);
    },
    clearCellContent(cell) {
      this.setCellContent(cell, '', '', false);
    },

    /**************************************
     ******* Placing Starting Number *******
     **************************************/
    onEnteringStatePlaceStartingNumber(args) {
      if (this.isReadOnly()) return;

      this._selectedStartingCell = null;
      let n = args._private.n;
      let selectStartingCell = (cell, n, addButton) => {
        this.setCellContent(cell, n); // Important for mobile users
        this._selectedStartingCell = this.getCellObj(cell);
        dojo.addClass(cell, 'selected');

        if (addButton) {
          this.addPrimaryActionButton('btnConfirmStartingNumber', _('Confirm'), () =>
            this.takeAction('actPlaceStartingNumber', this._selectedStartingCell, false),
          );
        }
      };

      // If a cell was already chosen, select it
      if (args._private.col) {
        selectStartingCell(this.getCell(0, args._private.col), n, false);
      }

      // Add event listeners on all cell of first row
      [0, 1, 2, 3, 4, 5, 6].forEach((col) => {
        let cell = this.getCell(0, col);
        let oCell = this.getCellObj(cell);

        // Add/remove number when entering/leaving the cell with the mouse
        this.connect(cell, 'mouseenter', () => this.setCellContent(cell, n));
        this.connect(cell, 'mouseleave', () => {
          let c = this._selectedStartingCell;
          if (c == null || oCell.row != c.row || oCell.col != c.col) this.clearCellContent(cell);
        });

        // When clicking a cell
        this.onClick(cell, () => {
          dojo.empty('customActions');
          this.takeAction('actChangeStartingNumber', { lock: false }, false);

          // If the cell was already the one selected => unselect it
          let c = this._selectedStartingCell;
          if (c != null && c.row == oCell.row && c.col == oCell.col) {
            this._selectedStartingCell = null;
            dojo.removeClass(cell, 'selected');
          }
          // Otherwise, select it
          else {
            // First, unselect previously selected cell
            if (this._selectedStartingCell != null) {
              let previousCell = this.getCell(this._selectedStartingCell);
              dojo.removeClass(previousCell, 'selected');
              this.clearCellContent(previousCell);
            }
            selectStartingCell(cell, n, true);
          }
        });
      });
    },

    onUpdateActivityPlaceStartingNumber(args, active) {
      if (!active) {
        dojo.empty('customActions');
      }
    },

    /////////////////////////////////////
    //////   Display basic info   ///////
    /////////////////////////////////////
    displayBasicInfo(args) {
      // Add an UNDO button if there is something to cancel
      if (args.cancelable && !$('buttonCancelTurn')) {
        this.addSecondaryActionButton('buttonCancelTurn', _('Restart turn'), () => this.takeAction('actRestart'));
      }
    },

    ///////////////////////////////////////
    ///////////////////////////////////////
    /////////   Confirm/undo turn   ///////
    ///////////////////////////////////////
    ///////////////////////////////////////
    onEnteringStateConfirmTurn(args) {
      this.displayBasicInfo(args);
      this.addPrimaryActionButton('buttonConfirmAction', _('Confirm'), () => this.takeAction('actConfirmTurn'));

      /*
      // Launch timer on button depending on pref
      var pref = 1;
      if(this.prefs[CONFIRM].value == CONFIRM_DISABLED) pref = 0;
      if(this.prefs[CONFIRM].value == CONFIRM_ENABLED) pref = 2;
      debug(this.prefs, pref)
      this.startActionTimer('buttonConfirmAction', 10, pref);
*/
    },

    notif_clearTurn(n) {
      debug('Notif: clearing turn', n);
      let grid = $('sheet-' + this.player_id);
      [...grid.querySelectorAll(`[data-turn="${n.args.turn}"]`)].forEach((cell) => {
        cell.removeAttribute('data-turn');
        cell.removeAttribute('data-n');
      });

      [...grid.querySelectorAll(`[data-circled="${n.args.turn}"]`)].forEach((cell) => {
        cell.removeAttribute('data-circled');
      });
      //      this.cancelLogs(n.args.notifIds);
      this.highlightScoringCombinations(this.player_id);
    },

    onEnteringStateWaitOthers(args) {
      this.displayBasicInfo(args);
    },

    /**************************************
     *************** Dice *****************
     **************************************/

    /**
     * Create the dices and initialize them to their value
     */
    setupDices() {
      let dices = ['1*3457', '12*456', '234*67', '123567'];
      let shapeDice = ['*'];
      ['I', 'O', 'T', 'L', 'S'].forEach((shape) => {
        shapeDice.push(`<span class="tetromino tetromino-${shape}"></span>`);
      });
      dices.push(shapeDice);

      // Create the dice
      dices.forEach((dice, i) => {
        this.place('tplDice', { id: i, values: dice }, 'dice-holder');
      });
      dojo.place('nb-dice-4', 'dice-holder', 'first'); // Put the shape dice first

      // Rotate them if initialized already
      this.gamedatas.dices.forEach((face, i) => {
        this.rotateDice(i, face, false);
      });
    },

    /**
     * HTML template for a 3D dice
     */
    tplDice(dice) {
      return `
      <div class="nb-dice-wrap" id="nb-dice-${dice.id}">
          <div class="nb-dice">
              <div class="dice-front">${dice.values[0]}</div>
              <div class="dice-back">${dice.values[1]}</div>
              <div class="dice-top">${dice.values[2]}</div>
              <div class="dice-bottom">${dice.values[3]}</div>
              <div class="dice-left">${dice.values[4]}</div>
              <div class="dice-right">${dice.values[5]}</div>
          </div>
      </div>
      `;
    },

    /**
     * Rotate a dice with smooth animation to the targeted face
     */
    rotateDice(diceId, value, transition = true) {
      let dice = $('nb-dice-' + diceId).querySelector('.nb-dice');

      // Compute the index of the face depending on the diceId
      let dices = ['1*3457', '12*456', '234*67', '123567', '*IOTLS'];
      let faceToShow = dices[diceId].indexOf(value);

      // Compute the angles
      let angles = [
        { z: 0, y: 0, x: 0 },
        { z: 0, y: 180, x: 0 },
        { z: 0, y: 180, x: 90 },
        { z: 0, y: 180, x: -90 },
        { z: 90, y: 90, x: 90 },
        { z: 0, y: -90, x: 0 },
      ];
      let angle = angles[faceToShow];
      if (!transition) {
        dice.classList.add('no-transition');
        dice.offsetWidth;
      } else {
        // Add some random variations
        angle.x += (parseInt(Math.random() * 9) - 4) * 360;
        angle.y += (parseInt(Math.random() * 9) - 4) * 360;
        angle.z += (parseInt(Math.random() * 9) - 4) * 360;
      }
      dice.style.transform = `rotateZ(${angle.z}deg) rotateY(${angle.y}deg) rotateX(${angle.x}deg)`;
      dice.offsetWidth;
      dice.classList.remove('no-transition');
    },

    /**
     * Notif: new dice throw, make them roll
     */
    notif_throwDices(n) {
      debug('Notif: rolling dies', n);
      n.args.dices.forEach((value, i) => {
        this.rotateDice(i, value);
      });
      this.gamedatas.dices = n.args.dices;
      this.gamedatas.turn = n.args.turn;
      this.updateTurnNumber();
    },

    /**************************************
     ************ Dark Mode ***************
     **************************************/
    onPreferenceChange(pref, value) {
      if (pref == DARK_MODE) this.toggleDarkMode(value == DARK_MODE_ENABLED, false);
    },

    toggleDarkMode(enabled) {
      if (enabled) {
        dojo.query('html').addClass('darkmode');
        $('chk-darkmode').checked = true;
      } else {
        dojo.query('html').removeClass('darkmode');
        $('chk-darkmode').checked = false;
      }
    },

    addDarkModeSwitch() {
      // Darkmode switch
      dojo.place(
        `
        <div class='upperrightmenu_item' id="darkmode-switch">
          <input type="checkbox" class="checkbox" id="chk-darkmode" />
          <label class="label" for="chk-darkmode">
            <div class="ball"></div>
          </label>
        </div>
        `,
        'upperrightmenu',
        'first',
      );

      dojo.connect($('chk-darkmode'), 'onchange', () =>
        this.setPreferenceValue(DARK_MODE, $('chk-darkmode').checked ? DARK_MODE_ENABLED : DARK_MODE_DISABLED),
      );

      this.toggleDarkMode(this.prefs[DARK_MODE].value == DARK_MODE_ENABLED);
    },
  });
});
