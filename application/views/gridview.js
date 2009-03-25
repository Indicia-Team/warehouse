var filter = new HashArray();
var sort = new HashArray();
var page;
var queryString;
var baseQueryString;
var pageUrlSegmentNo;
var realUrl;

/**
	* Refreshes everything - a shortcut
 */
function refresh(){
	buildQueryString();
	refreshGrid();
	refreshPager();
};

/**
* Refreshes the grid controller from the querystring variable.
 */
function refreshGrid(){
	$("#gvBody").load(queryString);
};

/**
	* Refreshes the pager.
 */
function refreshPager(){
	var pagerString = queryString;
	if (pagerString.charAt(pagerString.length) == '?'){
		pagerString = pagerString + 'type=pager';
	} else {
		pagerString = pagerString + '&type=pager';
	}
	$.ajax({
		url: pagerString,
		cache: false,
		success: function(a){
			$('.pager').html(a);
			pagerLinks();
		}
	});
};

/**
 * Adds new javascript links to the pager component
 */
function pagerLinks(){
	$('.pagination a').each(function(i){
		$(this).click(function(e){
			e.preventDefault();
			page = $.url.setUrl($(this).attr('href')).segment(pageUrlSegmentNo);
			refresh();
		});
	});
};


/**
	* Builds a new query string from the filter and sort arrays
 */
function buildQueryString() {
	var sortCols = '';
	var sortDirs = '';
	var filterCols = '';
	var filterStrings = '';

	for (var i = 0; i < sort.size(); i++){
		sortCols = sortCols + sort.getKeyAtIndex(i) + ',';
		sortDirs = sortDirs + sort.getValueAtIndex(i) + ',';
	}
	if (sortCols != '') {
		sortCols = sortCols.substring(0,sortCols.length -1);
		sortDirs = sortDirs.substring(0,sortDirs.length -1);
	}

	for (var i = 0; i < filter.size(); i++){
		filterCols = filterCols + filter.getKeyAtIndex(i) + ',';
		filterStrings = filterStrings + filter.getValueAtIndex(i) + ',';
	}
	if (filterCols != '') {
		filterCols = filterCols.substring(0,filterCols.length -1);
		filterStrings = filterStrings.substring(0,filterStrings.length -1);
	}

	queryString = baseQueryString
		+ page + '/'
		+ realUrl.segment(pageUrlSegmentNo + 1) + '?'
		+ ((sortCols != '') ? 'orderby=' + sortCols
			+ '&direction=' + sortDirs + '&': '')
		+ ((filterCols != '') ?	'columns=' + filterCols
			+ '&filters=' + filterStrings : '');
};

$(document).ready(function(){

	// Get the real URL (in case of routing)
	realUrl = $.url.setUrl($('meta[name=routedURI]').attr('content'));
	baseUri = $('meta[name=baseURI]').attr('content');

	// Determine the segment number used for the page - the gridview control will
	// always use the last two segments for the page, and limit - number of items to
	// show per page.
	pageUrlSegmentNo = realUrl.attr('path').split('/').length - 3;

	// Set the base query string
	baseQueryString = baseUri;
	var afterIndex = 0;
	for (var i = 0; i < pageUrlSegmentNo; i++) {
		if (afterIndex > 0) {
			if (afterIndex == 2) {
				baseQueryString += realUrl.segment(i) + '_gv/';
			} else {
				baseQueryString += realUrl.segment(i) + '/';
			}
			afterIndex++;
		}
		if (realUrl.segment(i)=='index.php')
			afterIndex=1;
	}

	//Set initial page
	page = realUrl.segment(pageUrlSegmentNo);

	// Paging
	pagerLinks();

	// Sorting
	$('#pageGrid thead th.gvSortable').each(function(i){
		$(this).click(function(e){
			e.preventDefault();
			var h = $(this).attr('id').toLowerCase();
			var a = sort.get(h);
			if (a != undefined) {
				if (a == 'asc') {
					sort.unshift(h,'desc');
					$(this).removeClass('gvColAsc');
					$(this).addClass('gvColDesc');
				} else {
					sort.remove(h);
					$(this).removeClass('gvColDesc');
					$(this).addClass('gvCol');
				}
			} else {
				sort.unshift(h, 'asc');
				$(this).removeClass('gvCol');
				$(this).addClass('gvColAsc');
			}
			refresh();
		});
	});

	// Filtration
	$('#gvFilter form').submit(function(e){
		e.preventDefault();
		filter.clear();
		filter.unshift($('select').val(), $('div#gvFilter input:first').val());
		refresh();
	});
});
