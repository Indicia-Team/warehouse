/* Indicia, the OPAL Online Recording Toolkit.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see http://www.gnu.org/licenses/gpl.html.
 */

/**
 * @requires jquery.js, jquery.cookie.js, json2.js
 */

/**
 * indicia.locks
 * 
 * This is a library of support code for the indicia project. It implements the
 * javascript for lockable form controls. These allow users to lock controls so
 * the set values are reset as the control value when the form is redisplayed.
 * Locks are released either by user action or at the end of the browser
 * session.
 */

(function($) {
  // this function wraps our code so
  // 1) $ is privately defined as jQuery and doesn't conflict with other code
  // 2) we can use var to make our helper functions private. Note these local
  // functions must be declared before they are used.

  // establish namespace so we don't have name clashes with other javascript
  // libraries
  if (!window.indicia) {
    window.indicia = {};
  }
  if (!window.indicia.locks) {
    window.indicia.locks = {};
  }

  // variables to hold the tool-tips pumped in from PHP. This has to be done to
  // support I18n.
  var lockedTip = '';
  var unlockedTip = '';

  // variable to hold form mode, NEW, RELOAD or ERRORS.
  var formMode = '';

  // boolean variable to tell us if cookies are enabled in this browser. Note,
  // the anonymous function is invoked and cookiesEnabled is set to the result.
  var cookiesEnabled = function() {
    // returns true if cookies enabled, else false
    var result = false;
    $.cookie('indicia_locked_controls_cookie_test', 'test');
    if ($.cookie('indicia_locked_controls_cookie_test') === 'test') {
      result = true;
      $.cookie('indicia_locked_controls_cookie_test', null);
    }
    return result;
  }();

  /*
   * Helper functions
   */
  var esc4jq = function(selector) {
    // escapes the jquery metacharacters for jquery selectors
    return selector ? selector.replace(/([ #;&,.+*~\':"!^$[\]()=>|\/%])/g,
        '\\$1') : '';
  };

  var getOtherLocks = function(controlId) {
    // gets an array of locks for all locked controls other than the one
    // supplied
    var lockedArray = [];
    if ($.cookie('indicia_locked_controls')) {
      lockedArray = JSON.parse($.cookie('indicia_locked_controls'));
    }
    var i;
    for (i = 0; i < lockedArray.length; i++) {
      if (lockedArray[i].ctl_id && lockedArray[i].ctl_id === controlId
          && lockedArray[i].ctl_page
          && lockedArray[i].ctl_page === document.title) {
        lockedArray.splice(i, 1);
        break;
      }
    }
    return lockedArray;
  };

  var lockControl = function(controlId) {
    // create or update lock cookie for supplied control
    var lockedArray = getOtherLocks(controlId);
    var locked = {};
    var escControlId = esc4jq(controlId);
    locked.ctl_page = document.title;
    locked.ctl_id = controlId;
    locked.ctl_value = $('#' + escControlId).val();
    locked.ctl_caption = $('input[id*=' + escControlId + '\\:]').val();
    lockedArray.push(locked);
    $.cookie('indicia_locked_controls', JSON.stringify(lockedArray));
  };

  var unlockControl = function(controlId) {
    // update or delete lock cookie to reflect removing this control
    var lockedArray = getOtherLocks(controlId);
    if (lockedArray.length > 0) {
      $.cookie('indicia_locked_controls', JSON.stringify(lockedArray));
    } else {
      $.cookie('indicia_locked_controls', null);
    }
  };

  var getLockedValue = function(controlId) {
    // gets the locked value for the control id supplied, or returns false if
    // not found
    var value = false;
    if ($.cookie('indicia_locked_controls')) {
      var lockedArray = JSON.parse($.cookie('indicia_locked_controls'));
      var i;
      for (i = 0; i < lockedArray.length; i++) {
        if (lockedArray[i].ctl_id && lockedArray[i].ctl_id === controlId
            && lockedArray[i].ctl_page
            && lockedArray[i].ctl_page === document.title) {
          value = lockedArray[i].ctl_value;
          break;
        }
      }
    }
    return value;
  };

  var getLockedCaption = function(controlId) {
    // gets the locked caption for the control id supplied, or returns false if
    // not found. Only used for autocomplete.
    var caption = false;
    if ($.cookie('indicia_locked_controls')) {
      var lockedArray = JSON.parse($.cookie('indicia_locked_controls'));
      var i;
      for (i = 0; i < lockedArray.length; i++) {
        if (lockedArray[i].ctl_id && lockedArray[i].ctl_id === controlId
            && lockedArray[i].ctl_page
            && lockedArray[i].ctl_page === document.title
            && lockedArray[i].ctl_caption) {
          caption = lockedArray[i].ctl_caption;
          break;
        }
      }
    }
    return caption;
  };

  var isControlLocked = function(controlId) {
    // returns true if the control has a locked value, else returns false
    return getLockedValue(controlId) !== false;
  };

  var hasCaption = function(controlId) {
    // returns true if the control has a locked caption, else returns false
    return getLockedCaption(controlId) !== false;
  };

  var controlHasError = function(controlId) {
    // checks if control is displaying a validation error
    // returns true if in error, else false.
    var escControlId = esc4jq(controlId);
    return $('#' + escControlId).hasClass('ui-state-error');
  };

  var setControlValue = function(controlId, value) {
    // may need to do something more for certain controls, but this works for
    // text input, and also for textarea and select (I don't know why).
    var escControlId = esc4jq(controlId);
    $('#' + escControlId).val(value);
    // trigger change and blur events, may have to be selective about this?
    $('#' + escControlId).change().blur();
    // for autocomplete
    if (hasCaption(controlId)) {
      $('input[id*=' + escControlId + '\\:]').val(getLockedCaption(controlId))
          .change().blur();
    }
  };

  var setWriteStatus = function(id) {
    var escId = esc4jq(id);
    var escControlId = escId.replace('_lock', '');
    if ($('#' + escId).hasClass('locked-icon')) {
      if (typeof $.datepicker!=="undefined") {
        $('#' + escControlId).attr('readonly', 'readonly').attr('disabled',
            'disabled').filter('.hasDatepicker').datepicker('disable');
      }
      if (typeof $.autocomplete!=="undefined") {
        $('input[id*=' + escControlId + '\\:]').filter(
            '.ac_input, .ui-autocomplete').attr('readonly', 'readonly').attr(
            'disabled', 'disabled').autocomplete('disable');
      }
    } else {
      if (typeof $.datepicker!=="undefined") {
        $('#' + escControlId).removeAttr('readonly').removeAttr('disabled')
            .filter('.hasDatepicker').datepicker('enable');
      }
      if (typeof $.autocomplete!=="undefined") {
        $('input[id*=' + escControlId + '\\:]').filter(
            '.ac_input, .ui-autocomplete').removeAttr('readonly').removeAttr(
            'disabled').autocomplete('enable');
      }
    }
  };

  var setLockToolTip = function(id) {
    var escId = esc4jq(id);
    if ($('#' + escId).hasClass('locked-icon')) {
      $('#' + escId).attr('title', lockedTip);
      $('#' + escId).attr('alt', lockedTip);
    } else {
      $('#' + escId).attr('title', unlockedTip);
      $('#' + escId).attr('alt', unlockedTip);
    }
  };

  /**
   * initialises lock settings and set event handlers, called from indicia ready
   * handler.
   */
  indicia.locks.initControls = function(lockedToolTip, unlockedToolTip, mode) {
    // do nothing unless we have nice cookies
    if (cookiesEnabled) {
      // sets tool-tips passed in from PHP. Done to support I18n
      lockedTip = lockedToolTip;
      unlockedTip = unlockedToolTip;
      // set form mode
      formMode = mode;
      // configure lockable controls on page load to reflect lock status
      $('.unset-lock').each(function(n) {
        var id = this.id;
        var escId = esc4jq(id);
        var controlId = id.replace('_lock', '');
        var escControlId = escId.replace('_lock', '');
        // establish lock state and set class/value
        if (isControlLocked(controlId) && formMode !== 'RELOAD') {
          if (controlHasError(controlId)) {
            // release lock if validation error
            $('#' + escId).addClass('unlocked-icon');
            unlockControl(controlId);
          } else {
            // set to locked value
            $('#' + escId).addClass('locked-icon');
            setControlValue(controlId, getLockedValue(controlId));
          }
        } else {
          // lock is open and don't set value
          $('#' + escId).addClass('unlocked-icon');
        }
        $('#' + escId).removeClass('unset-lock');
        setWriteStatus(id);
        setLockToolTip(id);
      });
      // install the click handler for the lockable controls
      $('.locked-icon, .unlocked-icon').click(function(event) {
        var id = this.id;
        var escId = esc4jq(id);
        var controlId = id.replace('_lock', '');
        $('#' + escId).toggleClass('locked-icon');
        $('#' + escId).toggleClass('unlocked-icon');
        if ($('#' + escId).hasClass('locked-icon')) {
          lockControl(controlId);
        } else {
          unlockControl(controlId);
        }
        setWriteStatus(id);
        setLockToolTip(id);
      });
      // install the submit handler for the lockable forms to enable any locked
      // controls for submission
      $('form:has(span.locked-icon, span.unlocked-icon)').submit(
          function(event) {
            // if the form has no id, set one so we can use the id to select its
            // locked controls
            var form = this;
            var formIdAdded = false;
            if (!form.id) {
              form.id = 'tempId-5x667vvfd';
              formIdAdded = true;
            }
            var escFormId = esc4jq(form.id);
            // select all locked controls in this form and enable them
            $('#' + escFormId + ' span.locked-icon').each(
            // $(form + ' span.locked-icon').each(
                    function(n) {
                  var span = this;
                  var escId = esc4jq(span.id);
                  var escControlId = escId.replace('_lock', '');
                  $('#' + escControlId).removeAttr('disabled').filter(
                      '.hasDatepicker').datepicker('enable');
                  $('input[id*=' + escControlId + '\\:]').filter(
                      '.ac_input, .ui-autocomplete').autocomplete('enable');
                });
            if (formIdAdded) {
              form.id = '';
            }
          });
    }
  };

})(jQuery);
