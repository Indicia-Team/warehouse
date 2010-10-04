function wizardProgressIndicator(options) {
  var defaults = {
    divId: 'controls',
    listClass: 'wiz-prog ui-helper-clearfix',
    a: [],
    equalWidth: true,
    start: 0
  };

  var o = $.extend({}, defaults, options);
  
  // find the outer wizard div
  var div = $("#" + o.divId);
  
  // put a ul element before to hold the progress info
  div.before('<ul id="' + o.divId + '-wiz-prog" class="' + o.listClass + '"></ul>');
  var progressUl = $($("#" + o.divId + "-wiz-prog")[0]);
  
  // find the list of tab headings
  var headingUl = div.find('> ul');
  var li=[];
  $.each(headingUl.find('> li'), function(i, item) {
    li.push(item.textContent);
	
	progressUl.append('<li><a href="'+$('a', item).attr('href') + '"><span>'+item.textContent+'</span></a></li>');
  });
  
  //size the <li> equally
  if (o.equalWidth) {
    var wizLis = $("li", progressUl);
    var totalWidth = progressUl.width();
    var spacing = wizLis.outerWidth(true) - wizLis.width();
    var width = ((totalWidth / (wizLis.length)) | 0) - spacing;
    wizLis.css('width', width + 'px');
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
      },
      function(){
        $(this).parent().removeClass('wiz-hover');
      }
    );
        
    $(wizLis[ui.index]).addClass('wiz-selected');
    $(wizLis[ui.index]).removeClass('wiz-enabled');
    $(wizLis[ui.index]).removeClass('wiz-disabled');
  })

}
