define(['dojo', 'dojo/_base/declare'], (dojo, declare) => {
  const DIRS = [
    [1, 0],
    [0, 1],
    [-1, 0],
    [0, -1],
  ];

  const COL_SAME = 11;
  const COL_SEQUENCE = 12;
  const COL_DROP = 13;
  const COL_BONUS = 14;

  return declare('numberdrop.scoreCombination', null, {
    /**
     * Highligh existing scoring combinations of a player sheet by adding borders
     */
    highlightScoringCombinations(pId) {
      let grid = document.querySelector('#sheet-' + pId + ' .sheet-top .grid-wrapper .nd-grid');
      for (let i = 1; i <= this.gamedatas.turn; i++) {
        // Fetch all used cells of turn ${i}
        let cells = [...grid.querySelectorAll(`.nd-cell[data-circled="${i}"]`)].map((cell) => this.getCellObj(cell));

        // Add borders around cells depending on neighbours
        cells.forEach((cell) => {
          let cellElt = this.getCell(cell.row, cell.col, pId);

          // For each direction
          DIRS.forEach((dir, dirIndex) => {
            // Test if another cell of the same shape is in this direction
            let neighbour = cells.filter(
              (cell2) => cell2.row == parseInt(cell.row) + dir[0] && cell2.col == parseInt(cell.col) + dir[1],
            );
            if (neighbour.length == 0) {
              cellElt.classList.add('border-' + dirIndex);
            }
          });
        });
      }
    },

    /**
     * The player may score a new combination
     */
    onEnteringStateScoreCombination(args) {
      this.addPrimaryActionButton('nbtPassScoreCombination', _('Pass'), () =>
        this.takeAction('actPassScoreCombination'),
      );

      // Copy server data and update ongoing combination
      this._combination = args.combination;
      this._scoringColumns = args.columns;
      this.updatePossibleCellsForCombination(false);

      // Add event listeners
      let cells = this.getPossibleCellsForCombination(false);
      cells.forEach((cell) => {
        let cellElt = this.getCell(cell);
        this.connect(cellElt, 'click', () => this.onClickCellForScoreCombination(cell));
      });
    },

    /**
     * Check if two cells are adjacent
     */
    isAdjacent(cell1, cell2) {
      return Math.abs(cell1.row - cell2.row) + Math.abs(cell1.col - cell2.col) == 1;
    },

    /**
     * Compute the remeaning cells available to continue the current scoring combination
     */
    getPossibleCellsForCombination(filterCells = true) {
      // Compute all the unused cells
      let grid = document.querySelector('#sheet-' + this.player_id + ' .sheet-top .grid-wrapper .nd-grid');
      let unusedCells = [
        ...grid.querySelectorAll(`.nd-cell[data-n]:not([data-circled])` + (filterCells ? ':not(.selected)' : '')),
      ].map((cell) => this.getCellObj(cell));
      if (!filterCells) return unusedCells;

      // Filter them depending on ongoing combination constraint
      let result = [];
      // Only one cell => keep all neighbours with numbers +1, =, -1
      if (this._combination.length == 1) {
        let firstCell = this._combination[0];
        let M = parseInt(firstCell.n);
        result = unusedCells.filter(
          (cell) => this.isAdjacent(cell, firstCell) && [M - 1, M, M + 1].includes(parseInt(cell.n)),
        );
      }
      // More than one cell, check the current constraint
      else if (this._combination.length > 1) {
        let numbers = this._combination.map((cell) => cell.n);

        // Same numbers => keep all neighbours with this exact number
        if (numbers[0] == numbers[numbers.length - 1]) {
          this._combination.forEach((cCell) => {
            unusedCells.forEach((cell) => {
              if (this.isAdjacent(cell, cCell) && cell.n == numbers[0]) result.push(cell);
            });
          });
        }
        // Different numbers => keep neighbours with +1,-1 numbers depending on type of sequence
        else {
          let m = this._combination.length;
          let n2 = parseInt(numbers[m - 1]) + (numbers[0] < numbers[1] ? 1 : -1);
          unusedCells.forEach((cell) => {
            if (this.isAdjacent(cell, this._combination[m - 1]) && parseInt(cell.n) == n2) {
              result.push(cell);
            }
          });
        }
      }
      // Empty combination: return all unused cells
      else if (this._combination.length == 0) {
        result = unusedCells;
      }

      return result;
    },

    /**
     * Update the remeaning cells available to continue the current scoring combination
     */
    updatePossibleCellsForCombination(takeAction = true) {
      // Send the combination to server
      if (takeAction) {
        this.takeAction('actConstructCombination', {
          lock: false,
          combination: JSON.stringify(this._combination),
        });
      }

      // Make sure cells in the combination have selected class
      dojo.query('.nd-cell.selected').removeClass('selected');
      this._combination.forEach((cell) => {
        let oCell = this.getCell(cell);
        oCell.classList.add('selected');
      });

      this._possibleCells = this.getPossibleCellsForCombination();
      // Add 'selectable/selected' class
      dojo.query('.nd-cell.selectable').removeClass('selectable');
      this._possibleCells.forEach((cell) => {
        let oCell = this.getCell(cell);
        oCell.classList.add('selectable');
      });

      // Update action buttons
      const M = this._combination.length;
      dojo.destroy('btnClearCombination');
      if (M > 0) {
        this.addSecondaryActionButton('btnClearCombination', _('Clear'), () => {
          this._combination = [];
          dojo.query('.nd-cell').removeClass('selectable selected');
          this.updatePossibleCellsForCombination();
        });
      }

      dojo.destroy('btnConfirmCombination');
      if (M >= 3 && M <= 8) {
        // Check if corresponding combination is still available
        let scoringCol = this._combination[0].n == this._combination[1].n ? COL_SAME : COL_SEQUENCE;
        if ((M < 8 && !this._scoringColumns[scoringCol][M - 3]) || (M == 8 && !this._scoringColumns[COL_BONUS][0])) {
          this.addPrimaryActionButton('btnConfirmCombination', _('Confirm combination'), () =>
            this.takeAction('actConfirmCombination'),
          );
        }
      }
    },

    /**
     * When the player click on a cell => add it to combination if valid
     */
    onClickCellForScoreCombination(cell) {
      let cells = this.getPossibleCellsForCombination().filter(
        (cell2) => cell.row == cell2.row && cell.col == cell2.col,
      );
      if (cells.length == 0) {
        return; // Not a valid cell
      }

      this._combination.push(cell);
      this.updatePossibleCellsForCombination();
    },

    /**
     * Received once the player confirm its combination : useful for loging and replay
     */
    notif_scoreCombination(n) {
      debug('Notif: scoring a combination', n);
      n.args.scribbles.forEach((scribble) => this.addScribble(scribble));
    },
  });
});
