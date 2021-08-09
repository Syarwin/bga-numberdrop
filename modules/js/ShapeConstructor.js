define(['dojo', 'dojo/_base/declare'], (dojo, declare) => {
  return declare('numberdrop.shapeConstructor', null, {
    toggleShapeConstructor(visible, pId = null) {
      pId = pId || this.player_id;
      let constructor = document.querySelector('#sheet-' + pId + ' .sheet-top .shape-constructor');
      constructor.classList.toggle('active', visible);
    },

    /**
     * HTML template for the shape constructor module
     */
    tplShapeConstructor() {
      // Allow to select the shape in case the shape dice is *
      let shapeSelectors = '';
      for (let i = 0; i < 5; i++) {
        shapeSelectors += `<div id='shape-selector-${i}'></div>`;
      }

      // 4x4 grid to construct the shape
      let shapeConstructorGrid = '';
      for (let i = 3; i >= 0; i--) {
        for (let j = 0; j < 4; j++) {
          shapeConstructorGrid += `<div id='shape-constructor-cell-${i}-${j}' class='nd-cell'></div>`;
        }
      }

      return `
        <div id='shape-selector'>
          ${shapeSelectors}
        </div>
        <div id="shape-constructor-holder">
          <div class="shape-constructor-controls">
            <div class="nd-cell" id="control-flip-horizontal"></div>
            <div class="nd-cell" id="control-move-left"></div>
            <div class="nd-cell" id="control-rotate-left"></div>
          </div>
          <div id='shape-constructor-grid'>
            ${shapeConstructorGrid}
          </div>
          <div class="shape-constructor-controls">
            <div class="nd-cell" id="control-clear"></div>
            <div class="nd-cell" id="control-move-right"></div>
            <div class="nd-cell" id="control-rotate-right"></div>
          </div>
        </div>
        `;
    },

    /***********************************
     ************ Drop Shape ***********
     ***********************************/
    onEnteringStateDropShape(args) {
      this.toggleShapeConstructor(true);
      let shapeDice = args.dices[4];
      let defaultTetromino = () => ({
        shape: shapeDice == '*' ? null : shapeDice,
        rotation: 0,
        flip: 0,
        col: 2,
        numbers: ['', '', '', ''],
      });

      // Init with DB entry if player already started building it, otherwise default
      this._tetromino = args.tetromino != null ? args.tetromino : defaultTetromino();

      // Connect listeners
      ['I', 'L', 'O', 'S', 'T'].forEach((shape, i) => {
        if (shapeDice == '*' || shapeDice == shape) {
          this.onClick('shape-selector-' + i, () => this.selectShape(shape));
        }
      });

      // Shape constructor controls
      let controls = {
        'rotate-left': () => (this._tetromino.rotation += this._tetromino.flip == 1 ? 1 : -1),
        'rotate-right': () => (this._tetromino.rotation += this._tetromino.flip == 1 ? -1 : 1),
        'flip-horizontal': () => (this._tetromino.flip = 1 - this._tetromino.flip),
        'move-left': () => this._tetromino.col--,
        'move-right': () => this._tetromino.col++,
        clear: () => {
          this._tetromino = defaultTetromino();
          this.updateRemeaningDices();
        },
      };

      Object.keys(controls).forEach((control) => {
        this.onClick('control-' + control, () => {
          controls[control]();
          this.updateShapeConstructor();
        });
      });

      // Cells
      for (let i = 0; i < 4; i++) {
        for (let j = 0; j < 4; j++) {
          this.onClick(`shape-constructor-cell-${i}-${j}`, (evt) => {
            evt.stopPropagation();
            this.onClickCellShapeConstructor(i, j);
          });
        }
      }

      this.updateShapeConstructor(false);
      this.updateRemeaningDices();
    },

    /**
     * Select a shape
     */
    selectShape(shapeId) {
      this._tetromino.shape = shapeId;
      this._tetromino.rotation = 0;
      this._tetromino.flip = 0;
      this.updateShapeConstructor();
    },

    /**
     * Update shape constructor
     */
    updateShapeConstructor(takeAction = true) {
      // Send the tetromino to server
      if (takeAction) {
        this.takeAction('actConstructTetromino', {
          lock: false,
          tetromino: JSON.stringify(this._tetromino),
        });
      }

      if (this._tetromino.shape == null) {
        // Clear everything
        dojo.query('#shape-constructor-grid .nd-cell').forEach((cell) => {
          cell.classList.remove('active');
          cell.setAttribute('data-n', '');
        });
        this.clearTetrominoShadow();
        return;
      }

      // Make sure the shape fit in the grid
      this.replaceTetrominoInsideGrid();

      // Update the shape constructor grid
      let shape = this.gamedatas.shapes[this._tetromino.shape][this._tetromino.rotation];
      let n = shape.length;
      dojo.attr('shape-constructor-grid', 'data-dim', n); // Resize the grid accordingly to the shape dim
      for (let i = 0; i < n; i++) {
        for (let j = 0; j < n; j++) {
          let y = this._tetromino.flip == 0 ? j : n - j - 1;

          let cell = $(`shape-constructor-cell-${i}-${j}`);
          if (shape[i][y] == ' ') {
            // Empty cell, make it inactive
            cell.classList.remove('active');
            cell.setAttribute('data-n', '');
          } else {
            // Shape block, make it active and put corresponding chosen number
            cell.classList.add('active');
            cell.setAttribute('data-n', this._tetromino.numbers[shape[i][y]]);
          }
        }
      }

      // Update tetromino "shadow" in the player's grid
      this.updateTetrominoShadow();

      // Handle action button
      dojo.destroy('btnConfirmTetromino');
      if (!this._tetromino.numbers.includes('')) {
        this.addActionButton('btnConfirmTetromino', _('Confirm tetromino drop'), () =>
          this.takeAction('actConfirmTetromino'),
        );
      }
    },

    /**
     * Replace the tetromino inside the grid if too far right or left
     */
    replaceTetrominoInsideGrid() {
      // Keep the rotation in [0,1,2,3] (*90deg)
      this._tetromino.rotation = (this._tetromino.rotation + 4) % 4;

      // Check current drop column against current rotation
      let minCol = 8,
        maxCol = 0;
      this.getCurrentShapeBlocks().forEach((pos) => {
        minCol = Math.min(minCol, pos.col);
        maxCol = Math.max(maxCol, pos.col);
      });
      if (minCol + this._tetromino.col < 0) {
        this._tetromino.col = -minCol;
      }
      $('control-move-left').classList.toggle('disabled', this._tetromino.col == -minCol);

      if (maxCol + this._tetromino.col > 6) {
        this._tetromino.col = 6 - maxCol;
      }
      $('control-move-right').classList.toggle('disabled', this._tetromino.col == 6 - maxCol);
    },

    /**
     * Return the array of blocks forming the current shape
     */
    getCurrentShapeBlocks() {
      let shape = this.gamedatas.shapes[this._tetromino.shape][this._tetromino.rotation];
      let n = shape.length;

      let res = [];
      for (let i = 0; i < n; i++) {
        for (let j = 0; j < n; j++) {
          let y = this._tetromino.flip == 0 ? j : n - j - 1;
          let id = shape[i][y];
          if (id != ' ') {
            res.push({
              row: i,
              col: j,
              n: id, // Each block is identified by an id : 0,1,2,3
            });
          }
        }
      }

      return res;
    },

    /*******************************
     ******** DIAL SELECTOR ********
     *******************************/

    /**
     * When a cell of shape constructor grid is clicked => add number dial
     */
    onClickCellShapeConstructor(i, j) {
      let shape = this.gamedatas.shapes[this._tetromino.shape][this._tetromino.rotation];
      let n = shape.length;
      let y = this._tetromino.flip == 0 ? j : n - j - 1;
      let id = shape[i][y];
      if (id == ' ') return; // Not a block of the current shape

      let cell = $(`shape-constructor-cell-${i}-${j}`);
      this.placeDial(cell, id);
    },

    /**
     * Construct a dial depending on the remeaining dices
     */
    placeDial(cell, id) {
      debug(cell, id);
      // Clear existing dial if any
      this.clearDial();

      // Create a new dial with correct enabled numbers
      let html = '<div id="shape-constructor-dial">';
      let possibleNumbers = this.updateRemeaningDices();
      for (let i = 1; i < 8; i++) {
        let status = possibleNumbers.includes(i) ? 'active' : 'disabled';
        html += `<div id="shape-constructor-dial-${i}" class="${status}">${i}</div>`;
      }
      html += '<div id="shape-constructor-clear"></div></div>';
      dojo.place(html, cell);

      // Connect listeners
      possibleNumbers.push(''); // add clear button

      possibleNumbers.forEach((i) => {
        let eltId = i == '' ? 'clear' : 'dial-' + i;
        dojo.connect($('shape-constructor-' + eltId), 'click', (evt) => {
          evt.stopPropagation();
          this.clearDial();
          this._tetromino.numbers[id] = '' + i;
          this.updateShapeConstructor();
          this.updateRemeaningDices();
        });
      });
    },

    /**
     * Clear existing dial
     */
    clearDial() {
      if ($('shape-constructor-dial')) {
        dojo.destroy('shape-constructor-dial');
      }
    },

    /**
     * Update remaining dices
     */
    updateRemeaningDices() {
      dojo.query('#dice-holder .nb-dice-wrap').removeClass('used');

      let dices = this.gamedatas.dices.slice(0, 4);
      this._tetromino.numbers.forEach((value) => {
        if (value == '') return;

        let pos = dices.indexOf(dices.includes(value) ? value : '*');
        dices[pos] = 'X';
        dojo.addClass('nb-dice-' + pos, 'used');
      });

      if (dices.includes('*')) return [1, 2, 3, 4, 5, 6, 7];
      else return dices.filter((value) => value != 'X').map((val) => parseInt(val));
    },

    /*******************************
     ******* TETROMINO SHADOW ******
     *******************************/

    /**
     * Given current shape and col, find lowest row before it's blocked
     */
    findLowestDropRow() {
      for (let i = 10; i > -3; i--) {
        let collision = false;
        this.getCurrentShapeBlocks().forEach((pos) => {
          pos.row += i;
          pos.col += this._tetromino.col;
          if (this.getCellContent(pos) != '') collision = true;
        });

        if (collision) {
          return i + 1;
        }
      }

      return 0;
    },

    /**
     * Update the tetromino's shadow
     */
    updateTetrominoShadow() {
      // Clear previous shadow
      this.clearTetrominoShadow();

      // Find lowest possible row
      let row = this.findLowestDropRow();
      if (row == 12) {
        // Only happens if too far right/left
        return;
      }

      // Draw tetromino shadow
      this.getCurrentShapeBlocks().forEach((pos) => {
        pos.row += row;
        pos.col += this._tetromino.col;

        let cell = this.getCell(pos);
        this.setCellContent(cell, this._tetromino.numbers[pos.n], this.gamedatas.turn);
        cell.classList.add('active', 'selectable');
        this._listeningCells.push(dojo.connect(cell, 'click', (evt) => {
          evt.stopPropagation();
          this.placeDial(cell, pos.n)
        }));
      });
    },

    /**
     * Clear the current tetromino's shadow
     */
    clearTetrominoShadow() {
      let grid = document.querySelector('.sheet-wrapper.current .sheet-top .grid-wrapper .nd-grid');
      let cells = [...grid.querySelectorAll('.nd-cell.active')];
      cells.forEach((cell) => {
        this.clearCellContent(cell);
        cell.classList.remove('active', 'selectable');
      });
      this._listeningCells.forEach((listener) => dojo.disconnect(listener));
    },
  });
});
