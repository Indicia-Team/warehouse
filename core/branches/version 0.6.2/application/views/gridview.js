/**
 * Indicia, the OPAL Online Recording Toolkit.
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
 *
 * @package    Core
 * @subpackage Views
 * @author    Indicia Team
 * @license    http://www.gnu.org/licenses/gpl.html GPL
 * @link     http://code.google.com/p/indicia/
 */

var filter = new HashArray();
var sort = new HashArray();
var queryString;

/**
  * Refreshes everything - a shortcut
 */
function refresh(gridId, url) {
  buildQueryString(url);  
  refreshGrid(gridId);
  refreshPager(gridId);
};

/**
* Refreshes the grid controller from the querystring variable.
 */
function refreshGrid(gridId){
  $("#gridBody-" + gridId).load(queryString);
};

/**
 * Refreshes the pager.
 */
function refreshPager(gridId){
  var pagerString = queryString;
  if (pagerString.charAt(pagerString.length) == '?'){
    pagerString = pagerString + 'type=pager';
  } else {
    pagerString = pagerString + '&type=pager';
  }
  // Make an AJAX call to get the updated pager text.
  $.ajax({
    url: pagerString,
    cache: false,
    success: function(a){      
      $('#pager-'+gridId).html(a);      
    }
  });
};

/**
 * Adds new javascript links to the pager component
 */
function pagerLinks(){
  $('.pagination a').live('click',
    function(e) {
      e.preventDefault();
      // find the unique ID for this grid so we refresh the correct one.
      var gridId = $(this).parent().parent().attr('id').split('-')[1];      
      url = buildAjaxUrl($(this).attr('href'));       
      refresh(gridId, url);      
    }
  );  
};

function buildAjaxUrl(urlStr) {
  var url=$.url.setUrl(urlStr), urlStr;   
  // Build a query string from the link that was clicked
  urlStr=url.attr('protocol') + '://' + url.attr('host');
  var i=0;
  while (url.segment(i)!=null) {
    urlStr += '/' + url.segment(i);
    if (url.segment(i)=='page') {
        urlStr += '_gv'
    }
    i++;      
  }    
  return urlStr;
}

/**
  * Builds a new query string from the filter and sort arrays
 */
function buildQueryString(url) {
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

  queryString = url + '?'
    + ((sortCols != '') ? 'orderby=' + sortCols
      + '&direction=' + sortDirs + '&': '')
    + ((filterCols != '') ?    'columns=' + filterCols
      + '&filters=' + encodeURIComponent(filterStrings) : '');
};

$(document).ready(function(){

  // Paging
  pagerLinks();

  // Sorting
  $('thead th.gvSortable').live('click', function(e) {
    e.preventDefault();
    var h = $(this).attr('id').toLowerCase();
    var a = sort.get(h);
    if (a != undefined) {
      if (a == 'asc') {
        sort.unshift(h,'desc');
        $(this).removeClass('gvColAsc');
        $(this).addClass('gvColDesc');
      } else {
        sort.unshift(h,'asc');
        $(this).removeClass('gvColDesc');
        $(this).addClass('gvCol');
      }
    } else {
      sort.unshift(h, 'asc');
      $(this).removeClass('gvCol');
      $(this).addClass('gvColAsc');
    }
    var gridId = $(this).parent().parent().parent().attr('id').split('-')[1];
    // Because the column header is not a link, we don't know the URL to go to. So we use the filterForm's action to get the URL.
    url = buildAjaxUrl($('#filterForm-'+gridId).attr('action'));
    refresh(gridId, url);
  });  

  // Filtration
  // kill the live handler, as document ready is called again when the AJAX tab loads
  $('.gvFilter form').die();
  $('.gvFilter form').live('submit', function(e) {
    e.preventDefault();
    // find the unique ID for this grid so we refresh the correct one.
    var gridId = $(this).attr('id').split('-')[1];
    url = buildAjaxUrl($(this).attr('action'));    
    filter.clear();
    filter.unshift($('#filterForm-' + gridId + ' select').val(), $('#filterForm-' + gridId + ' input:first').val());
    refresh(gridId, url);
  });
});
