function wizardProgressIndicator(options) {
  var defaults = {
    divId: 'controls',
    listClass: 'wiz-prog ui-corner-all ui-widget-content ui-helper-clearfix',
    a: [],
    equalWidth: true,
    start: 0,
    completionStep: 'Survey submitted'
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
    li.push(item.innerText);
    var wizClass = (i===o.start ? 'wiz-selected' : 'wiz-disabled');
    progressUl.append('<li class="arrow-block '+wizClass+'"><a href="'+$('a', item).attr('href') + '">'+ (i+1) + '. ' + item.innerText + '</a></li>');
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
    var width = ((totalWidth / (arrowBlockLis.length)) | 0) - spacing - 1 - arrowHeadLis.outerWidth(true);
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
    var tabs = $(this);
    var wizList = $("#" + tabs.attr('id') + "-wiz-prog");
    var wizLis = $("li", wizList);
    var prevLi = $(".wiz-selected", wizList);
    var prevA =  prevLi.children("a:first");
    
    prevLi.removeClass('wiz-selected');
    prevLi.addClass('wiz-enabled');
    prevA.click(function(e){
      //transfer the click to the tab anchor
      var wizLi = $(this).parent();
      var wizList = wizLi.parent();
      var wizLis = wizList.children("li");
      var index = wizLis.index(wizLi);
      var tabs = wizList.parent();
      var tabAnchor = $("ul.ui-tabs-nav a", tabs)[index];
      $(tabAnchor).click();
    });

    prevA.hover(
      function()
      {
        $(this).parent().addClass('wiz-hover');
        $(this).parent().next().addClass('wiz-hover');
      },
      function(){
        $(this).parent().removeClass('wiz-hover');
        $(this).parent().next().removeClass('wiz-hover');
      }
    );
        
    // update classes on the arrow bodyy
    $(wizLis[ui.index*2]).addClass('wiz-selected');
    $(wizLis[ui.index*2]).removeClass('wiz-enabled');
    $(wizLis[ui.index*2]).removeClass('wiz-disabled');
    // update classes on the arrow header
    $(wizLis[ui.index*2+1]).addClass('wiz-selected');
    $(wizLis[ui.index*2+1]).removeClass('wiz-enabled');
    $(wizLis[ui.index*2+1]).removeClass('wiz-disabled');
  })

}
