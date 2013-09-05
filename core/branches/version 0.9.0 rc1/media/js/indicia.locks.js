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

  // 'constant' for the base cookie name for locks
  var COOKIE_NAME = 'indicia_locked_controls';
  
  // variable to indicate if locking initialised.
  var initialised = false;
  
  // variables to hold the tool-tips pumped in from PHP. This has to be done to
  // support I18n.
  var lockedTip = '';
  var unlockedTip = '';

  // variable to hold form mode, NEW, RELOAD or ERRORS.
  var formMode = '';

  // variable to hold simpleHash of title
  var hash = 0;

  // variables to hold reference to map div and spatial ref input
  var mapDiv, srefId;
  
  // variable to hold user name, or empty string for anonymous
  var user = '';

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

  var simpleHash = function(str) {
    // returns the sum of bytes in the string, terrible hash function but we don't need much,
    // the point is just to get a short but probably unique form identifier from the title
    if (hash>0) {
      return hash;
    }
    for (var i=0; i<str.length; i++) {
      hash += str.charCodeAt(i);
    }
    return hash;
  };

  var housekeepLocks = function() {
    // remove any locks for the page which don't exist on the page
    var pageHash = simpleHash(document.title);
    var lockedArray = [];
    if ($.cookie(COOKIE_NAME + user)) {
      lockedArray = JSON.parse($.cookie(COOKIE_NAME + user));
    } else {
      return;
    }
    var locks = [];
    $('.unset-lock, .locked-icon, .unlocked-icon').each(function(n) {
      locks.push(this.id.replace('_lock', ''));
    });
    for (var i = 0; i < lockedArray.length; i++) {
      if (lockedArray[i].ctl_id
          && lockedArray[i].ctl_page
          && lockedArray[i].ctl_page === pageHash) {
        var found = false;
        for (var j = 0; j < locks.length; j++) {
          if (lockedArray[i].ctl_id===locks[j]) {
            found = true;
            break;
          }
        }
        if (!found) {
          lockedArray.splice(i, 1);
        }
      }
    }
    $.cookie(COOKIE_NAME + user, JSON.stringify(lockedArray));
  };

  var getOtherLocks = function(controlId) {
    // gets an array of locks for all locked controls other than the one
    // supplied
    var lockedArray = [];
    if ($.cookie(COOKIE_NAME + user)) {
      lockedArray = JSON.parse($.cookie(COOKIE_NAME + user));
    }
    var i;
    for (i = 0; i < lockedArray.length; i++) {
      if (lockedArray[i].ctl_id && lockedArray[i].ctl_id === controlId
          && lockedArray[i].ctl_page
          && lockedArray[i].ctl_page === simpleHash(document.title)) {
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
    locked.ctl_page = simpleHash(document.title);
    locked.ctl_id = controlId;
    if ($('#' + escControlId).length==1) {
      locked.ctl_value = $('#' + escControlId).val();
      locked.ctl_caption = $('input[id*=' + escControlId + '\\:]').val();
    } else {
      var control$;
      var checked$;
      control$ = $('input[name^=' + escControlId + ']');
      if (control$.length>0) {
        switch (control$[0].type) {
        case 'radio':
          checked$ = control$.filter(':checked');
          locked.ctl_value = (checked$.length == 1) ? checked$.val() : '';
          locked.ctl_caption = '';
          break;
        case 'checkbox':
          checked$ = control$.filter(':checked');
          locked.ctl_value = [];
          checked$.each(function(n) {
            locked.ctl_value[n] = this.value;
          });
          locked.ctl_caption = '';
          break;
        default:
          locked.ctl_value = '';
          locked.ctl_caption = '';
          break;
        }
      } else {
        // don't know what control this is
        locked.ctl_value = '';
        locked.ctl_caption = '';
      }
    }
    lockedArray.push(locked);
    $.cookie(COOKIE_NAME + user, JSON.stringify(lockedArray));
  };

  var unlockControl = function(controlId) {
    // update or delete lock cookie to reflect removing this control
    var lockedArray = getOtherLocks(controlId);
    if (lockedArray.length > 0) {
      $.cookie(COOKIE_NAME + user, JSON.stringify(lockedArray));
    } else {
      $.cookie(COOKIE_NAME + user, null);
    }
  };

  var getLockedValue = function(controlId) {
    // gets the locked value for the control id supplied, or returns false if
    // not found
    var value = false;
    if ($.cookie(COOKIE_NAME + user)) {
      var lockedArray = JSON.parse($.cookie(COOKIE_NAME + user));
      var i;
      for (i = 0; i < lockedArray.length; i++) {
        if (lockedArray[i].ctl_id && lockedArray[i].ctl_id === controlId
            && lockedArray[i].ctl_page
            && lockedArray[i].ctl_page === simpleHash(document.title)) {
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
    if ($.cookie(COOKIE_NAME + user)) {
      var lockedArray = JSON.parse($.cookie(COOKIE_NAME + user));
      var i;
      for (i = 0; i < lockedArray.length; i++) {
        if (lockedArray[i].ctl_id && lockedArray[i].ctl_id === controlId
            && lockedArray[i].ctl_page
            && lockedArray[i].ctl_page === simpleHash(document.title)
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

  var setControlValue = function(controlId, value, caption) {
    // may need to do something more for certain controls, but this works for
    // text input, and also for textarea and select (I don't know why).
    var escControlId = esc4jq(controlId);
    if ($('#' + escControlId).length==1) {
      if ($('#' + escControlId).attr('type')=='checkbox'){
        var values = [];
        values[0] = value;
        $('#' + escControlId).val(values);
      } else {
        $('#' + escControlId).val(value);
      }
      // trigger change and blur events, may have to be selective about this?
      $('#' + escControlId).change().blur();
    } else {
      var control$;
      control$ = $('input[name^=' + escControlId + ']');
      if (control$.length>0) {
        var values = [];
        switch (control$[0].type) {
        case 'radio':
          values[0] = value;
          control$.val(values);
          break;
        case 'checkbox':
          control$.val(value);
          break;
        default:
          break;
        }
      } else {
        // don't know what control this is
      }
    }
    // for autocomplete
    if (caption) {
      $('input[id*=' + escControlId + '\\:]').val(caption)
          .change().blur();
    }
  };

  var setWriteStatus = function(id) {
    var escId = esc4jq(id);
    var escControlId = escId.replace('_lock', '');
    var control$ = $('#' + escControlId);
    if (control$.length===0) {
      control$ = $('input[name^=' + escControlId + ']');
    }
    if ($('#' + escId).hasClass('locked-icon')) {
      control$.attr('readonly', 'readonly').attr('disabled', 'disabled').addClass('locked-control');
      if (typeof $.fn.datepicker!=="undefined") {
        control$.filter('.hasDatepicker').datepicker('disable');
      }
      if (typeof $.fn.autocomplete!=="undefined") {
        $('input[id*=' + escControlId + '\\:]').filter(
            '.ac_input, .ui-autocomplete').attr('readonly', 'readonly').attr(
            'disabled', 'disabled').addClass('locked-control');
      }
      if (srefId!==null && mapDiv!==null && escControlId===srefId) {
        $(mapDiv).before('<div id="mapLockMask" style="position: absolute;"/>');
        $('#mapLockMask').css({"opacity": "0.25", "background-color": "white",
            "left":$(mapDiv).position().left + "px", 
            "top":$(mapDiv).position().top + "px", 
            "z-index":9999}) 
            .width($(mapDiv).width())
            .height($(mapDiv).height());
      }
    } else {
      control$.removeAttr('readonly').removeAttr('disabled').removeClass('locked-control');
      if (typeof $.fn.datepicker!=="undefined") {
        control$.filter('.hasDatepicker').datepicker('enable');
      }
      if (typeof $.fn.autocomplete!=="undefined") {
        $('input[id*=' + escControlId + '\\:]').filter(
            '.ac_input, .ui-autocomplete').removeAttr('readonly').removeAttr(
            'disabled').removeClass('locked-control');
      }
      if (srefId!==null && mapDiv!==null && escControlId===srefId) {
        $('#mapLockMask').remove();
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

  var setControlFromLock = function(id, mode) {
    var escId = esc4jq(id);
    var controlId = id.replace('_lock', '');
    var escControlId = escId.replace('_lock', '');
    // establish lock state and set class/value
    if (isControlLocked(controlId) && mode !== 'RELOAD') {
      if (controlHasError(controlId)) {
        // release lock if validation error
        $('#' + escId).addClass('unlocked-icon');
        unlockControl(controlId);
      } else {
        // set to locked value
        $('#' + escId).addClass('locked-icon');
        setControlValue(controlId, getLockedValue(controlId), getLockedCaption(controlId));
      }
    } else {
      // lock is open and don't set value
      $('#' + escId).addClass('unlocked-icon');
    }
    $('#' + escId).removeClass('unset-lock');
    setWriteStatus(id);
    setLockToolTip(id);
  };

  /**
   * Forms can optionally call this to set a user name so the user has their own set of lock values.
   * The user name is appended to the coockie name so there is a cookie for each user plus one for anonymous users.
   * This should be called before initControls.
   * @param pUser - any string to identify the user.
   */
  indicia.locks.setUser = function(pUser) {
    // sets user name to be used in cookie name to make locks personal to this user
    user = encodeURIComponent(pUser+'');
  };

  /**
   * unlock lock settings for all controls within the specified region of the page
   * @param region jQuery selector for the page region to unlock
   */
  indicia.locks.unlockRegion = function(region) {
    $('.locked-icon', region).each(function(n) {
      $(this).click();
    });
  };

  /**
   * copy lock settings and state from one set of controls to another matching set.
   * @param fromSelector jQuery selector for the part of the form to copy from
   * @param toSelector jQuery selector for the matching part of the form to copy to
   */
  indicia.locks.copyLocks = function(fromSelector, toSelector) {
    // do nothing unless initialised
    if (initialised) {
      var fromLock$ = $('.unset-lock, .locked-icon, .unlocked-icon', fromSelector);
      var toLock$ = $('.unset-lock, .locked-icon, .unlocked-icon', toSelector);
      var fromLocked$ = $('.locked-icon', fromSelector);
      // ensure all 'to' locks initially unset
      toLock$.not('.unset-lock').each(function(n) {
        $(this).removeClass('locked-icon').removeClass('unlocked-icon').addClass('unset-lock');
      });
      // for each locked 'from' control, create a corresponding 'to' lock
      fromLocked$.each(function(n) {
        for (var i=0; (i<fromLock$.length && i<toLock$.length); i++) {
          if (this.id===fromLock$[i].id) {
            var fromControlId = fromLock$[i].id.replace('_lock', '');
            var toControlId = toLock$[i].id.replace('_lock', '');
            // copy value
            setControlValue(toControlId, getLockedValue(fromControlId), getLockedCaption(fromControlId));
            // set lock values in cookie
            lockControl(toControlId);
          }
        }
      });
      // configure lockable controls on page load to reflect lock status from cookie
      $('.unset-lock', toSelector).each(function(n) {
        setControlFromLock(this.id, 'NEW');
      });
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
      // tidy up any dynamically created locks for this page
      housekeepLocks();
      // configure lockable controls on page load to reflect lock status
      $('.unset-lock').each(function(n) {
        setControlFromLock(this.id, formMode);
      });
      // install the live click handler for the lockable controls
      $('.locked-icon, .unlocked-icon').live('click', function(event) {
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
      $('form:has(span.locked-icon, span.unlocked-icon)').submit(function(event) {
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
                function(n) {
              var span = this;
              var escId = esc4jq(span.id);
              var escControlId = escId.replace('_lock', '');
              $('#' + escControlId).removeAttr('disabled').filter(
                  '.hasDatepicker').datepicker('enable');
              $('input[id*=' + escControlId + '\\:]').filter(
                  '.ac_input, .ui-autocomplete').removeAttr('disabled');
            });
        if (formIdAdded) {
          form.id = '';
        }
      });
      if (typeof mapInitialisationHooks !== 'undefined') {
        mapInitialisationHooks.push(function(div) {
          // Capture the map div so when locking the sref control, set
          // div.settings.clickForSpatialRef to false, and back again on unlock
          // to stop users updating locked spatial refs by clicking on map
          mapDiv = div;
          srefId = mapDiv.settings.srefId;
          var lock$ = $('#' + srefId+'_lock');
          if (lock$.length !== 0) {
            // Only need to do this if a lock has been put on the sref control.
            setWriteStatus(srefId+'_lock');
          }
        });
      }
      initialised = true;
    }
  };

})(jQuery);
