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
 
var wizardProgressIndicator, initTabAddressing, scrollTopIntoView, setupButtons;
 
(function ($) {

setupButtons = function (tabs, index) {
  var wizList = $("#" + tabs.attr('id') + "-wiz-prog");
  var wizLis = $("li", wizList);
  var prevLi = $(".wiz-selected", wizList);
  
  prevLi.removeClass('wiz-selected');
  
    // declare local scope, so accessible in nested functions
  var tabsvar = tabs;
      
  // update classes on the arrow bodyy
  $(wizLis[index*2]).addClass('wiz-selected');
  $(wizLis[index*2]).removeClass('wiz-enabled');
  $(wizLis[index*2]).removeClass('wiz-disabled');
  // update classes on the arrow header
  $(wizLis[index*2+1]).addClass('wiz-selected');
  $(wizLis[index*2+1]).removeClass('wiz-enabled');
  $(wizLis[index*2+1]).removeClass('wiz-disabled');

  var nextLi = $(".wiz-disabled:first", wizList);
  $.merge(nextLi, nextLi.next());
  var enabledLis = $($.merge( $.merge([],prevLi), nextLi));

  enabledLis.click(function(e){
    var wizList = $(this).parent();
    var tabs = wizList.parent();
    // first, validate
    var current=wizList.parent().children('.ui-tabs').tabs('option', 'selected');
    var tabinputs = $('#entry_form div > .ui-tabs-panel:eq('+current+')').find('input,select').not(':disabled,[name=],.scTaxonCell,.inactive');
    if (typeof tabinputs.valid !== "undefined" && tabinputs.length>0 && !tabinputs.valid()) {
      return;
    }
    var wizLis = wizList.children("li");
    var index = wizLis.index($(this));
    
    //transfer the click to the tab anchor
    var tabAnchor = $("ul.ui-tabs-nav a", tabs)[index/2]; // /2 because there is an arrow header li after every li
    $(tabAnchor).click();
  });
  enabledLis.addClass('wiz-enabled');
  enabledLis.hover(
    function()
    {
      $(this).addClass('wiz-hover');
      $(this).next().addClass('wiz-hover');
    },
    function(){
      $(this).removeClass('wiz-hover');
      $(this).next().removeClass('wiz-hover');
    }
  );
  if (nextLi.length===0) {
    // got to the end of thw wizard, so (re)bind an event for clicking the submit in the progress
    $('.wiz-complete').unbind('click');
    $('.wiz-complete').click(function() {
      var wizList = $(this).parent();
      var tabs = wizList.parent();
      // first, validate
      var current=wizList.parent().children('.ui-tabs').tabs('option', 'selected');
      var tabinputs = $('#entry_form div > .ui-tabs-panel:eq('+current+')').find('input,select').not(':disabled');
      if (typeof tabinputs.valid !== "undefined" && !tabinputs.valid()) {
        return;
      }
      // submit the form
      var form = $(this).parents('form:first');
      form.submit();
    });
    $('.wiz-complete').addClass('wiz-enabled');
  }
}

wizardProgressIndicator=function(options) {
  var defaults = {
    divId: 'controls',
    listClass: 'wiz-prog ui-corner-all ui-widget-content ui-helper-clearfix',
    a: [],
    equalWidth: true,
    start: 0,
    completionStep: 'Submit Record'
  };

  var o = $.extend({}, defaults, options);
  
  // find the outer wizard div
  var div = $("#" + o.divId);
  
  // put a ul element before to hold the progress info
  div.before('<ul id="' + o.divId + '-wiz-prog" class="' + o.listClass + '"></ul>');
  var progressUl = $($("#" + o.divId + "-wiz-prog")[0]);
  
  // find the list of tab headings
  var headingUl = $('> ul', div);
  var li=[];  
  $.each(headingUl.children(), function(i, item) {
    li.push($(item).text());
    var wizClass = (i===o.start ? 'wiz-selected' : 'wiz-disabled');
    progressUl.append('<li class="arrow-block '+wizClass+'">'+ (i+1) + '. ' + $(item).text() + '</li>');
    progressUl.append('<li class="arrow-head '+wizClass+'"></li>');
  });
  if (o.completionStep!==null && o.completionStep!=='') {
    progressUl.append('<li class="arrow-block wiz-complete">'+ (headingUl.children().length+1) + '. ' + o.completionStep + '</li>');
    progressUl.append('<li class="arrow-head wiz-complete"></li>');
  }
  
  //size the <li> equally
  if (o.equalWidth) {
    var arrowBlockLis = $("li.arrow-block", progressUl);
    var arrowHeadLis = $("li.arrow-head", progressUl);
    var totalWidth = progressUl.width();
    // Get difference in width of li between width including margin, and width of part inside padding. 
    var spacing = arrowBlockLis.outerWidth(true) - arrowBlockLis.width();
    var width = ((totalWidth / (arrowBlockLis.length)) | 0) - spacing - 3 - arrowHeadLis.outerWidth(true);
    arrowBlockLis.css('width', width + 'px');
  }
  
  progressUl.addClass('wiz-disabled');
  $(progressUl[o.start]).addClass('wiz-selected');
  $(progressUl[o.start]).removeClass('wiz-disabled');
  progressUl.children("a").bind('click.wiz-nav', function(e){
    e.preventDefault();
  });
  
  //now set up handlers for tab events
  //the selected tab is given class wiz-selected and has wiz-disabled/enabled removed.
  //once a tab has been visited it is given class wiz-enabled and it gets hover and click events.
  $("#" + o.divId).bind('tabsselect', function(event, ui){
    setupButtons($(this), ui.index);
  });
  setupButtons($("#" + o.divId), 0);
};

/**
 * Function that prepares the tabset for being addressable.
 * @link http://www.asual.com/jquery/address/
 */
initTabAddressing=function(divId) {

  $.address.externalChange(function(event){
    // Changes tab
    $("#"+divId).tabs('select', $('#link-' + event.value).attr('href'));
    if ($('.wiz-prog').length>0)
      scrollTopIntoView('.wiz-prog');
    else
      scrollTopIntoView('#'.divId);
  });
};

/**
 * Function to ensure the top part of the wizard (inluding progress bar if present) is visible
 * when navigating between pages.
 */
scrollTopIntoView=function(topDiv) {
  if ($(topDiv).offset().top-$(window).scrollTop()<0) {
    $(topDiv)[0].scrollIntoView(true);
  }
};

}(jQuery));