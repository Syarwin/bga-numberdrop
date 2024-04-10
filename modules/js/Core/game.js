var isDebug = window.location.host == 'studio.boardgamearena.com' || window.location.hash.indexOf('debug') > -1;
var debug = isDebug ? console.info.bind(window.console) : function () {};

define(['dojo', 'dojo/_base/declare', 'ebg/core/gamegui'], (dojo, declare) => {
  return declare('customgame.game', ebg.core.gamegui, {
    /*
     * Constructor
     */
    constructor() {
      this._notifications = [];
      this._activeStates = [];
      this._connections = [];
      this._selectableNodes = [];
      this._activeStatus = null;
      this._helpMode = false;

      this.canceledNotifFeature = true;
      this._notif_uid_to_log_id = {};
      this._last_notif = null;
    },

    getPlayer(pId = null) {
      pId = pId || this.player_id;
      return this.gamedatas.players[pId];
    },

    // Utils to iterate over players array/object
    forEachPlayer(callback) {
      Object.values(this.gamedatas.players).forEach(callback);
    },

    getPlayerColor(pId) {
      return this.gamedatas.players[pId].color;
    },

    showMessage(msg, type) {
      if (type == 'error') {
        console.error(msg);
      }
      return this.inherited(arguments);
    },

    /*
     * [Undocumented] Override BGA framework functions to call onLoadingComplete when loading is done
     */
    setLoader(value, max) {
      this.inherited(arguments);
      if (!this.isLoadingComplete && value >= 100) {
        this.isLoadingComplete = true;
        this.onLoadingComplete();
      }
    },

    onLoadingComplete() {
      debug('Loading complete');
      if (this.canceledNotifFeature) this.cancelLogs(this.gamedatas.canceledNotifIds);
      if (this._blockToTrigger !== undefined) this.triggerBlock(this._blockToTrigger);
    },

    /*
     * Setup:
     */
    setup(gamedatas) {
      // Create a new div for buttons to avoid BGA auto clearing it
      dojo.place("<div id='customActions' style='display:inline-block'></div>", $('generalactions'), 'after');

      this.setupNotifications();
      this.initPreferencesObserver();
      if (!this.isReadOnly()) {
        this.checkPreferencesConsistency(gamedatas.prefs);
      }
      dojo.connect(this.notifqueue, 'addToLog', () => {
        this.checkLogCancel(this._last_notif);
      });
    },

    /*
     * Detect if spectator or replay
     */
    isReadOnly() {
      return this.isSpectator || typeof g_replayFrom != 'undefined' || g_archive_mode;
    },

    /*
     * Make an AJAX call with automatic lock
     */
    takeAction(action, data, check = true) {
      if (check && !this.checkAction(action)) return false;

      data = data || {};
      if (data.lock === undefined) {
        data.lock = true;
      } else if (data.lock === false) {
        delete data.lock;
      }
      return new Promise((resolve, reject) => {
        this.ajaxcall(
          '/' + this.game_name + '/' + this.game_name + '/' + action + '.html',
          data,
          this,
          (data) => resolve(data),
          (isError, message, code) => {
            if (isError) reject(message, code);
          },
        );
      });
    },

    /*
     * onEnteringState:
     * 	this method is called each time we are entering into a new game state.
     *
     * params:
     *  - str stateName : name of the state we are entering
     *  - mixed args : additional information
     */
    onEnteringState(stateName, args) {
      debug('Entering state: ' + stateName, args);

      if (this._activeStates.includes(stateName) && !this.isCurrentPlayerActive()) return;

      // Private state machine
      if (args.parallel) {
        if (args.args._private) this.setupPrivateState(args.args._private.state, args.args._private.args);
        return;
      }

      // Call appropriate method
      var methodName = 'onEnteringState' + stateName.charAt(0).toUpperCase() + stateName.slice(1);
      if (this[methodName] !== undefined) this[methodName](args.args);
    },

    /*
     * Private state
     */
    setupPrivateState(state, args) {
      if (this.gamedatas.gamestate.parallel) delete this.gamedatas.gamestate.parallel;
      this.gamedatas.gamestate.name = state.name;
      this.gamedatas.gamestate.descriptionmyturn = state.descriptionmyturn;
      this.gamedatas.gamestate.possibleactions = state.possibleactions;
      this.gamedatas.gamestate.transitions = state.transitions;
      this.gamedatas.gamestate.args = args;
      this.updatePageTitle();
      this.onEnteringState(state.name, this.gamedatas.gamestate);
    },

    notif_newPrivateStateNumberDrop(n) {
      this.onLeavingState(this.gamedatas.gamestate.name);
      this.setupPrivateState(n.args.state, n.args.args);
    },

    /**
     * onLeavingState:
     * 	this method is called each time we are leaving a game state.
     *
     * params:
     *  - str stateName : name of the state we are leaving
     */
    onLeavingState(stateName) {
      debug('Leaving state: ' + stateName);
      this.clearPossible();

      // Call appropriate method
      var methodName = 'onLeavingState' + stateName.charAt(0).toUpperCase() + stateName.slice(1);
      if (this[methodName] !== undefined) this[methodName]();
    },
    clearPossible() {
      this.removeActionButtons();
      dojo.empty('customActions');

      this._connections.forEach(dojo.disconnect);
      this._connections = [];
      this._selectableNodes.forEach((node) => {
        if ($(node)) dojo.removeClass(node, 'selectable selected');
      });
      this._selectableNodes = [];
      dojo.query('.unselectable').removeClass('unselectable');
    },

    /**
     * Check change of activity
     */
    onUpdateActionButtons(stateName, args) {
      let status = this.isCurrentPlayerActive();
      if (status != this._activeStatus) {
        debug('Update activity: ' + stateName, status);
        this._activeStatus = status;

        // Call appropriate method
        var methodName = 'onUpdateActivity' + stateName.charAt(0).toUpperCase() + stateName.slice(1);
        if (this[methodName] !== undefined) this[methodName](args, status);
      }
    },

    /*
     * setupNotifications
     */
    setupNotifications() {
      // Private state
      this._notifications.push(['newPrivateStateNumberDrop', 1]);

      this._notifications.forEach((notif) => {
        var functionName = 'notif_' + notif[0];

        dojo.subscribe(notif[0], this, functionName);
        if (notif[1] !== undefined) {
          if (notif[1] === null) {
            this.notifqueue.setSynchronous(notif[0]);
          } else {
            this.notifqueue.setSynchronous(notif[0], notif[1]);

            // xxxInstant notification runs same function without delay
            dojo.subscribe(notif[0] + 'Instant', this, functionName);
            this.notifqueue.setSynchronous(notif[0] + 'Instant', 10);
          }
        }

        if (notif[2] != undefined) {
          this.notifqueue.setIgnoreNotificationCheck(notif[0], notif[2]);
        }
      });
    },

    /*
     * Add a timer on an action button :
     * params:
     *  - buttonId : id of the action button
     *  - time : time before auto click
     *  - pref : 0 is disabled (auto-click), 1 if normal timer, 2 if no timer and show normal button
     */

    startActionTimer(buttonId, time, pref, autoclick = false) {
      var button = $(buttonId);
      var isReadOnly = this.isReadOnly();
      if (button == null || isReadOnly || pref == 2) {
        debug('Ignoring startActionTimer(' + buttonId + ')', 'readOnly=' + isReadOnly, 'prefValue=' + pref);
        return;
      }

      // If confirm disabled, click on button
      if (pref == 0) {
        if (autoclick) button.click();
        return;
      }

      this._actionTimerLabel = button.innerHTML;
      this._actionTimerSeconds = time;
      this._actionTimerFunction = () => {
        var button = $(buttonId);
        if (button == null) {
          this.stopActionTimer();
        } else if (this._actionTimerSeconds-- > 1) {
          button.innerHTML = this._actionTimerLabel + ' (' + this._actionTimerSeconds + ')';
        } else {
          debug('Timer ' + buttonId + ' execute');
          button.click();
        }
      };
      this._actionTimerFunction();
      this._actionTimerId = window.setInterval(this._actionTimerFunction, 1000);
      debug('Timer #' + this._actionTimerId + ' ' + buttonId + ' start');
    },

    stopActionTimer() {
      if (this._actionTimerId != null) {
        debug('Timer #' + this._actionTimerId + ' stop');
        window.clearInterval(this._actionTimerId);
        delete this._actionTimerId;
      }
    },

    /*
     * Play a given sound that should be first added in the tpl file
     */
    playSound(sound, playNextMoveSound = true) {
      playSound(sound);
      playNextMoveSound && this.disableNextMoveSound();
    },

    /*
     * Manage alternative page titles
     */
    resetPageTitle() {
      this.changePageTitle();
    },

    changePageTitle(suffix = null, save = false) {
      if (suffix == null) {
        suffix = 'generic';
      }

      if (!this.gamedatas.gamestate['descriptionmyturn' + suffix]) return;

      if (save) {
        this.gamedatas.gamestate.descriptionmyturngeneric = this.gamedatas.gamestate.descriptionmyturn;
        this.gamedatas.gamestate.descriptiongeneric = this.gamedatas.gamestate.description;
      }

      this.gamedatas.gamestate.descriptionmyturn = this.gamedatas.gamestate['descriptionmyturn' + suffix];
      if (this.gamedatas.gamestate['description' + suffix])
        this.gamedatas.gamestate.description = this.gamedatas.gamestate['description' + suffix];
      this.updatePageTitle();
    },

    /*
     * Remove non standard zoom property
     */
    onScreenWidthChange() {
      dojo.style('page-content', 'zoom', '');
      dojo.style('page-title', 'zoom', '');
      dojo.style('right-side-first-part', 'zoom', '');
    },

    /*
     * Add a blue/grey button if it doesn't already exists
     */
    addPrimaryActionButton(id, text, callback, zone = 'customActions') {
      if (!$(id)) this.addActionButton(id, text, callback, zone, false, 'blue');
    },

    addSecondaryActionButton(id, text, callback, zone = 'customActions') {
      if (!$(id)) this.addActionButton(id, text, callback, zone, false, 'gray');
    },

    addDangerActionButton(id, text, callback, zone = 'customActions') {
      if (!$(id)) this.addActionButton(id, text, callback, zone, false, 'red');
    },

    clearActionButtons() {
      dojo.empty('customActions');
    },

    /*
     * Preference polyfill
     */
    setPreferenceValue(number, newValue) {
      var optionSel = 'option[value="' + newValue + '"]';
      dojo
        .query(
          '#preference_control_' + number + ' > ' + optionSel + ', #preference_fontrol_' + number + ' > ' + optionSel,
        )
        .attr('selected', true);
      var select = $('preference_control_' + number);
      if (dojo.isIE) {
        select.fireEvent('onchange');
      } else {
        var event = document.createEvent('HTMLEvents');
        event.initEvent('change', false, true);
        select.dispatchEvent(event);
      }
    },

    initPreferencesObserver() {
      dojo.query('.preference_control, preference_fontrol').on('change', (e) => {
        var match = e.target.id.match(/^preference_[fc]ontrol_(\d+)$/);
        if (!match) {
          return;
        }
        var pref = match[1];
        var newValue = e.target.value;
        this.prefs[pref].value = newValue;
        $('preference_control_' + pref).value = newValue;
        $('preference_fontrol_' + pref).value = newValue;

        data = { pref: pref, lock: false, value: newValue, player: this.player_id };
        this.takeAction('actChangePref', data, false, false);
        this.onPreferenceChange(pref, newValue);
      });
    },

    checkPreferencesConsistency(backPrefs) {
      backPrefs.forEach((prefInfo) => {
        let pref = prefInfo.pref_id;
        if (this.prefs[pref] != undefined && this.prefs[pref].value != prefInfo.pref_value) {
          data = { pref: pref, lock: false, value: this.prefs[pref].value, player: this.player_id };
          this.takeAction('actChangePref', data, false, false);
        }
      });
    },

    onPreferenceChange(pref, newValue) {},

    wait(n) {
      return new Promise((resolve, reject) => {
        setTimeout(() => resolve(), n);
      });
    },

    /*
     * slideTemporary: a wrapper of slideTemporaryObject using Promise
     */
    slideTemporary(template, data, container, sourceId, targetId, duration, delay) {
      return new Promise((resolve, reject) => {
        var animation = this.slideTemporaryObject(
          this.format_block(template, data),
          container,
          sourceId,
          targetId,
          duration,
          delay,
        );
        setTimeout(() => {
          resolve();
        }, duration + delay);
      });
    },

    slide(mobileElt, targetElt, options = {}) {
      let config = Object.assign(
        {
          duration: 800,
          delay: 0,
          destroy: false,
          attach: true,
          changeParent: true, // Change parent during sliding to avoid zIndex issue
          pos: null,
          className: 'moving',
          from: null,
          clearPos: true,

          phantom: false,
        },
        options,
      );
      config.phantomStart = config.phantomStart || config.phantom;
      config.phantomEnd = config.phantomEnd || config.phantom;

      // Handle phantom at start
      mobileElt = $(mobileElt);
      let mobile = mobileElt;
      if (config.phantomStart) {
        mobile = dojo.clone(mobileElt);
        dojo.attr(mobile, 'id', mobileElt.id + '_animated');
        dojo.place(mobile, 'game_play_area');
        this.placeOnObject(mobile, mobileElt);
        dojo.addClass(mobileElt, 'phantom');
        config.from = mobileElt;
      }

      // Handle phantom at end
      targetElt = $(targetElt);
      let targetId = targetElt;
      if (config.phantomEnd) {
        targetId = dojo.clone(mobileElt);
        dojo.attr(targetId, 'id', mobileElt.id + '_afterSlide');
        dojo.addClass(targetId, 'phantomm');
        dojo.place(targetId, targetElt);
      }

      const newParent = config.attach ? targetId : $(mobile).parentNode;
      dojo.style(mobile, 'zIndex', 5000);
      dojo.addClass(mobile, config.className);
      if (config.changeParent) this.changeParent(mobile, 'game_play_area');
      if (config.from != null) this.placeOnObject(mobile, config.from);
      return new Promise((resolve, reject) => {
        const animation =
          config.pos == null
            ? this.slideToObject(mobile, targetId, config.duration, config.delay)
            : this.slideToObjectPos(mobile, targetId, config.pos.x, config.pos.y, config.duration, config.delay);

        dojo.connect(animation, 'onEnd', () => {
          dojo.style(mobile, 'zIndex', null);
          dojo.removeClass(mobile, config.className);
          if (config.phantomStart) {
            dojo.place(mobileElt, mobile, 'replace');
            dojo.removeClass(mobileElt, 'phantom');
            mobile = mobileElt;
          }
          if (config.changeParent) {
            if (config.phantomEnd) dojo.place(mobile, targetId, 'replace');
            else this.changeParent(mobile, newParent);
          }
          if (config.destroy) dojo.destroy(mobile);
          if (config.clearPos && !config.destroy) dojo.style(mobile, { top: null, left: null, position: null });
          resolve();
        });
        animation.play();
      });
    },

    changeParent(mobile, new_parent, relation) {
      if (mobile === null) {
        console.error('attachToNewParent: mobile obj is null');
        return;
      }
      if (new_parent === null) {
        console.error('attachToNewParent: new_parent is null');
        return;
      }
      if (typeof mobile == 'string') {
        mobile = $(mobile);
      }
      if (typeof new_parent == 'string') {
        new_parent = $(new_parent);
      }
      if (typeof relation == 'undefined') {
        relation = 'last';
      }
      var src = dojo.position(mobile);
      dojo.style(mobile, 'position', 'absolute');
      dojo.place(mobile, new_parent, relation);
      var tgt = dojo.position(mobile);
      var box = dojo.marginBox(mobile);
      var cbox = dojo.contentBox(mobile);
      var left = box.l + src.x - tgt.x;
      var top = box.t + src.y - tgt.y;
      this.positionObjectDirectly(mobile, left, top);
      box.l += box.w - cbox.w;
      box.t += box.h - cbox.h;
      return box;
    },

    positionObjectDirectly(mobileObj, x, y) {
      // do not remove this "dead" code some-how it makes difference
      dojo.style(mobileObj, 'left'); // bug? re-compute style
      // console.log("place " + x + "," + y);
      dojo.style(mobileObj, {
        left: x + 'px',
        top: y + 'px',
      });
      dojo.style(mobileObj, 'left'); // bug? re-compute style
    },

    /*
     * Wrap a node inside a flip container to trigger a flip animation before replacing with another node
     */
    flipAndReplace(target, newNode, duration = 1000) {
      return new Promise((resolve, reject) => {
        // Wrap everything inside a flip container
        let container = dojo.place(
          `<div class="flip-container flipped">
            <div class="flip-inner">
              <div class="flip-front"></div>
              <div class="flip-back"></div>
            </div>
          </div>`,
          target,
          'after',
        );
        dojo.place(target, container.querySelector('.flip-back'));
        dojo.place(newNode, container.querySelector('.flip-front'));

        // Trigget flip animation
        container.offsetWidth;
        dojo.removeClass(container, 'flipped');

        // Clean everything once it's done
        setTimeout(() => {
          dojo.place(newNode, container, 'replace');
          resolve();
        }, duration);
      });
    },

    /*
     * Return a span with a colored 'You'
     */
    coloredYou() {
      var color = this.gamedatas.players[this.player_id].color;
      var color_bg = '';
      if (this.gamedatas.players[this.player_id] && this.gamedatas.players[this.player_id].color_back) {
        color_bg = 'background-color:#' + this.gamedatas.players[this.player_id].color_back + ';';
      }
      var you =
        '<span style="font-weight:bold;color:#' +
        color +
        ';' +
        color_bg +
        '">' +
        __('lang_mainsite', 'You') +
        '</span>';
      return you;
    },

    coloredPlayerName(name) {
      const player = Object.values(this.gamedatas.players).find((player) => player.name == name);
      if (player == undefined) return '<!--PNS--><span class="playername">' + name + '</span><!--PNE-->';

      const color = player.color;
      const color_bg = player.color_back
        ? 'background-color:#' + this.gamedatas.players[this.player_id].color_back + ';'
        : '';
      return (
        '<!--PNS--><span class="playername" style="color:#' + color + ';' + color_bg + '">' + name + '</span><!--PNE-->'
      );
    },

    /*
     * Overwrite to allow to more player coloration than player_name and player_name2
     */
    format_string_recursive(log, args) {
      try {
        if (log && args) {
          let player_keys = Object.keys(args).filter((key) => key.substr(0, 11) == 'player_name');
          player_keys.forEach((key) => {
            args[key] = this.coloredPlayerName(args[key]);
          });
        }
      } catch (e) {
        console.error(log, args, 'Exception thrown', e.stack);
      }

      return this.inherited(arguments);
    },

    place(tplMethodName, object, container) {
      if ($(container) == null) {
        console.error('Trying to place on null container', container);
        return;
      }

      if (this[tplMethodName] == undefined) {
        console.error('Trying to create a non-existing template', tplMethodName);
        return;
      }

      return dojo.place(this[tplMethodName](object), container);
    },

    /* Helper to work with local storage */
    getConfig(value, v) {
      return localStorage.getItem(value) == null ? v : localStorage.getItem(value);
    },

    /**********************
     ****** HELP MODE ******
     **********************/
    /**
     * Toggle help mode
     */
    toggleHelpMode(b) {
      if (b) this.activateHelpMode();
      else this.desactivateHelpMode();
    },

    activateHelpMode() {
      this._helpMode = true;
      dojo.addClass('ebd-body', 'help-mode');
      this._displayedTooltip = null;
      document.body.addEventListener('click', this.closeCurrentTooltip.bind(this));
    },

    desactivateHelpMode() {
      this.closeCurrentTooltip();
      this._helpMode = false;
      dojo.removeClass('ebd-body', 'help-mode');
      document.body.removeEventListener('click', this.closeCurrentTooltip.bind(this));
    },

    closeCurrentTooltip() {
      if (!this._helpMode) return;

      if (this._displayedTooltip == null) return;
      else {
        this._displayedTooltip.close();
        this._displayedTooltip = null;
      }
    },

    /*
     * Custom connect that keep track of all the connections
     *  and wrap clicks to make it work with help mode
     */
    connect(node, action, callback) {
      this._connections.push(dojo.connect($(node), action, callback));
    },

    onClick(node, callback, temporary = true) {
      let safeCallback = (evt) => {
        if (this._helpMode) return false;
        callback(evt);
      };

      if (temporary) {
        this.connect($(node), 'click', safeCallback);
        dojo.removeClass(node, 'unselectable');
        dojo.addClass(node, 'selectable');
        this._selectableNodes.push(node);
      } else {
        dojo.connect($(node), 'click', safeCallback);
      }
    },

    /**
     * Tooltip to work with help mode
     */
    addCustomTooltip(id, html, delay) {
      if (this.tooltips[id]) {
        this.tooltips[id].destroy();
      }

      html = '<div class="midSizeDialog">' + html + '</div>';
      delay = delay || 400;
      let tooltip = new dijit.Tooltip({
        //        connectId: [id],
        label: html,
        position: this.defaultTooltipPosition,
        showDelay: delay,
      });
      this.tooltips[id] = tooltip;
      dojo.addClass(id, 'tooltipable');
      dojo.place(
        `
        <div class='help-marker'>
          <svg><use href="#help-marker-svg" /></svg>
        </div>
      `,
        id,
      );

      dojo.connect($(id), 'click', (evt) => {
        if (!this._helpMode) {
          tooltip.close();
        } else {
          evt.stopPropagation();

          if (tooltip.state == 'SHOWING') {
            this.closeCurrentTooltip();
          } else {
            this.closeCurrentTooltip();
            tooltip.open($(id));
            this._displayedTooltip = tooltip;
          }
        }
      });

      tooltip.showTimeout = null;
      dojo.connect($(id), 'mouseenter', () => {
        if (!this._helpMode) {
          if (tooltip.showTimeout != null) clearTimeout(tooltip.showTimeout);

          tooltip.showTimeout = setTimeout(() => tooltip.open($(id)), delay);
        }
      });

      dojo.connect($(id), 'mouseleave', () => {
        if (!this._helpMode) {
          tooltip.close();
          if (tooltip.showTimeout != null) clearTimeout(tooltip.showTimeout);
        }
      });
    },

    /*
     * [Undocumented] Called by BGA framework on any notification message
     * Handle cancelling log messages for restart turn
     */
    onPlaceLogOnChannel(msg) {
      var currentLogId = this.notifqueue.next_log_id;
      var res = this.inherited(arguments);
      if (this.canceledNotifFeature) {
        this._notif_uid_to_log_id[msg.uid] = currentLogId;
        this._last_notif = msg.uid;
      }
      return res;
    },

    /*
     * cancelLogs:
     *   strikes all log messages related to the given array of notif ids
     */
    checkLogCancel(notifId) {
      if (!this.canceledNotifFeature) return;

      if (this.gamedatas.canceledNotifIds != null && this.gamedatas.canceledNotifIds.includes(notifId)) {
        this.cancelLogs([notifId]);
      }
    },

    cancelLogs(notifIds) {
      if (!this.canceledNotifFeature) return;

      notifIds.forEach((uid) => {
        if (this._notif_uid_to_log_id.hasOwnProperty(uid)) {
          let logId = this._notif_uid_to_log_id[uid];
          if ($('log_' + logId)) dojo.addClass('log_' + logId, 'cancel');
        }
      });
    },
  });
});
