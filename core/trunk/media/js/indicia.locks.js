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

/*
 * @requires jquery.js, jquery.cookie.js, json2.js
 */

/*
 * indicia.locks
 * 
 * This is a library of support code for the indicia project.  
 * It implements the javascript for lockable form controls. These allow users to
 * lock controls so the set values become the default setting when the form is
 * redisplayed. Locks are released either by user action or at the end of the browser session.
 */

// establish namespace so we don't have name clashes with other javascript libraries
if (!this.indicia) {
	indicia = {};
}
if (!this.indicia.locks) {
	indicia.locks = {};
}

/* 
 * variables to hold the tool-tips pumped in from PHP. This has to be done to support I18n.
 */
indicia.locks.lockedTip = '';
indicia.locks.unlockedTip = '';

/* 
 * initialises tool-tips from PHP. This has to be done to support I18n. Must be called first.
 */
indicia.locks.initToolTips = function (lockedTip, unlockedTip) {
	indicia.locks.lockedTip = lockedTip;
	indicia.locks.unlockedTip = unlockedTip;
};
/* 
 * initialises lock settings, called from indicia ready handler.
 */
indicia.locks.initControls = function () {
  // install the click handler for the lockable controls
  $('.locked_icon, .unlocked_icon').click(function (event) {
    var id = this.id.replace(':', '\\\:');
    var unescapedControlId = this.id.replace('_lock', '');
    $('#'+id).toggleClass('locked_icon');
    $('#'+id).toggleClass('unlocked_icon');
    if ($('#'+id).hasClass('locked_icon')) {
      indicia.locks.lockControl(unescapedControlId);
    } else {
      indicia.locks.unlockControl(unescapedControlId);
    }
    indicia.locks.setWriteStatus(id);
    indicia.locks.setLockToolTip(id);
  });
  // configure lockable controls on page load to reflect lock status
  $('.locked_icon, .unlocked_icon').each(function(n) {
    var id = this.id.replace(':', '\\\:');
    indicia.locks.setWriteStatus(id);
    indicia.locks.setLockToolTip(id);
  });
};
/*
 * Helper functions
 */
indicia.locks.lockControl = function (controlId) {
// create or update lock cookie for supplied control
var lockedArray = indicia.locks.getOtherLocks(controlId);
var locked = {};
locked.ctl_id = controlId;
locked.ctl_value = document.getElementById(controlId).value;
lockedArray.push(locked);
jQuery.cookie('indicia_locked_controls', JSON.stringify(lockedArray));
};
indicia.locks.unlockControl = function (controlId) {
// update or delete lock cookie to reflect removing this control
var lockedArray = indicia.locks.getOtherLocks(controlId);
if (lockedArray.length > 0) {
  jQuery.cookie('indicia_locked_controls', JSON.stringify(lockedArray));
} else {
  jQuery.cookie('indicia_locked_controls', null);
}
};
indicia.locks.getOtherLocks = function (controlId) {
// gets an array of locks for all locked controls other than the one supplied 
var lockedArray = [];
if (jQuery.cookie('indicia_locked_controls')) {
  lockedArray = JSON.parse(jQuery.cookie('indicia_locked_controls'));
}
var i;
for (i=0; i<lockedArray.length; i++) {
  if (lockedArray[i].ctl_id && lockedArray[i].ctl_id===controlId) {
    lockedArray.splice(i, 1);
    break;
  }
}
return lockedArray;
};
indicia.locks.setWriteStatus = function (id) {
var controlId = id.replace('_lock', '');
if ($('#'+id).hasClass('locked_icon')) {
  $('#'+controlId).attr('readonly','readonly');
} else {
  $('#'+controlId).removeAttr('readonly');
}
};
indicia.locks.setLockToolTip = function (id) {
if ($('#'+id).hasClass('locked_icon')) {
  $('#'+id).attr('title',indicia.locks.lockedTip);
  $('#'+id).attr('alt',indicia.locks.lockedTip);
} else {
  $('#'+id).attr('title',indicia.locks.unlockedTip);
  $('#'+id).attr('alt',indicia.locks.unlockedTip);
}
};
