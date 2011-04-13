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
 * @package Media
 * @author  Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL 3.0
 * @link    http://code.google.com/p/indicia/
 */

/**
 * JQuery report grid widget for Indicia. Note that this is designed to attach to an already
 * loaded HTML grid (loaded using PHP on page load), and provides AJAX pagination and sorting without
 * page refreshes. It does not do the initial grid load operation.
 */
var report_grid_page = 0;
var report_grid_orderby = '';
var report_grid_sortdir = '';

(function($) {
  $.fn.reportgrid = function(options) {
    // Extend our default options with those provided, basing this on an empty object
    // so the defaults don't get changed.
    var opts = $.extend({}, $.fn.reportgrid.defaults, options);
    
    // flag to prevent double clicks
    var loading=false;
    
    /**
     * Function to make a service call to load the grid data.
     */
    load = function(div) {
      var serviceCall, paramName;
      if (div.settings.mode=='report') {
        serviceCall = 'report/requestReport?report='+div.settings.dataSource+'.xml&reportSource=local&';
      } else if (div.settings.mode=='direct') {
        serviceCall = 'data/' + div.settings.dataSource + '?';
      }
      var request = div.settings.url+'index.php/services/' +
          serviceCall +
          'mode=json&nonce=' + div.settings.nonce +
          '&auth_token=' + div.settings.auth_token +
          '&offset=' + div.settings.offset +
          '&callback=?';
      // Extract any parameters from the attached form
      $('form#'+div.settings.paramsFormId+'-params input, form#'+div.settings.paramsFormId+'-params select').each(function(idx, input) {
        if (input.type!=='submit') {
          paramName = $(input).attr('name').replace('param-'+div.settings.paramsFormId+'-', '');
          request += '&' + paramName + '=' + $(input).attr('value');
        }
      });
      if (div.settings.orderby !== null) {
        request += '&orderby=' + div.settings.orderby + '&sortdir=' + div.settings.sortdir;
      }
      // Ask for one more row than we need so we know if the next page link is available
      if (div.settings.itemsPerPage !== null) {
        request += '&limit=' + (div.settings.itemsPerPage+1);
      }
      // were any predefined parameter values supplied?
      if (div.settings.extraParams !== undefined) {
        $.each(div.settings.extraParams, function(name, value) {
          request += '&' + name + '=' + value;
        });
      }
      report_grid_page = Math.floor(div.settings.offset / div.settings.itemsPerPage);
      report_grid_orderby = div.settings.orderby;
      report_grid_sortdir = div.settings.sortdir;
      $.getJSON(request,
          null,
          function(response) {
            var tbody = $(div).find('tbody');
            // clear current grid rows
            tbody.children().remove();
            var row, rows = eval(response), rowclass='', count=0, hasMore=false, value, rowInProgress=false, rowOutput;            
            $.each(rows, function(rowidx, row) {
              // We asked for one too many rows. If we got it, then we can add a next page button
              if (div.settings.itemsPerPage !== null && rowidx>=div.settings.itemsPerPage) {
                hasMore = true;
              } else {
                // Initialise a new row, unless this is a gallery with multi-columns and not starting a new line
                if ((rowidx % div.settings.galleryColCount)==0) {
                  rowOutput = '<tr' + rowclass + '>';
                  rowInProgress=true;
                }
                $.each(div.settings.columns, function(idx, col) {
                  if (col.visible!==false && col.visible!='false') {
                    // either template the output, or just use the content according to the fieldname
                    if (typeof col.template !== "undefined") {
                      value = mergeParamsIntoTemplate(div, row, col.template);
                    } else if (typeof col.actions !== "undefined") {
                      value = getActions(div, row, col.actions);
                    } else {
                      value = row[col.fieldname];
                    }
                    // clear null value cells
                    value = (value===null) ? '' : value;
                    if (col.img == 'true') {
                      value = '<a href="'+div.settings.imageFolder+value+'" class="fancybox"><img src="'+div.settings.imageFolder+'thumb-'+value+'" /></a>';
                    }
                    rowOutput += '<td>' + value + '</td>';
                  }
                });
                if ((rowidx % div.settings.galleryColCount)==div.settings.galleryColCount-1) {
                  rowOutput += '</tr>';
                  tbody.append(rowOutput);
                  rowInProgress=false;
                  rowclass = (rowclass==='' ? ' class="'+div.settings.altRowClass + '"' : '');
                }
              }
            });
            if (rowInProgress) {
              rowOutput += '</tr>';
              tbody.append(rowOutput);
            }
            tbody.find('a.fancybox').fancybox();
            
            // Set a class to indicate the sorted column
            $('#' + div.id + ' th').removeClass('asc');
            $('#' + div.id + ' th').removeClass('desc');
            if (div.settings.orderby) {
              $('#' + div.id + '-th-' + div.settings.orderby).addClass(div.settings.sortdir.toLowerCase());
            }
            // recreate the pagination footer
            var pager=$(div).find('.pager');
            pager.empty();
            var pagerContent='';
            if (div.settings.offset!==0) {
              pagerContent += '<a class="prev" href="#">&#171 previous</a>';
            }
            if (div.settings.offset!==0 && hasMore) {
              pagerContent += ' | ';
            }
            if (hasMore) {
              pagerContent += '<a class="next" href="#">next &#187</a>';
            }
            pager.append(pagerContent);
            div.loading=false;
            setupPaginationClicks(div);

            // execute callback it there is one
            if (div.settings.callback !== "") {
              window[div.settings.callback]();
            }
          }
      );
    };
    
    getActions = function(div, row, actions) {
      var result='', onclick, href;
      $.each(actions, function(idx, action) {
        if (typeof action.javascript != "undefined") {
          var rowCopy = row;
          $.each(rowCopy, function(idx) {
            if (rowCopy[idx]!==null) {
              rowCopy[idx] = rowCopy[idx].replace(/'/g,"\\'");
            }
          });
          onclick=' onclick="' + mergeParamsIntoTemplate(div, rowCopy, action.javascript) + '"';
        } else {
          onclick='';
        }
        if (typeof action.url != "undefined") {
          var link = action.url;
          if (typeof action.urlParams != "undefined") {
            if (link.indexOf('?')==-1) { link += '?'; }
            else { link += '&'; }
            $.each(action.urlParams, function(name, value) {              
              link += name + '=' + value;
              link += '&';
            });
            if (link.substr(-1)=='&') {
              link = link.substr(0, link.length-1);
            }
          }
          link = mergeParamsIntoTemplate(div, row, link);
          href=' href="' + link + '"';
        } else {
          href='';
        }
        if (result !== '') {
          result += '<br/>';
        }
        result += '<a class="action-button"'+onclick+href+'>'+action.caption+'</a>';
      });
      return result;
    };
    
    mergeParamsIntoTemplate = function(div, params, template) {
      var regex;
      $.each(params, function(param) {
        regex = new RegExp('\\{'+param+'\\}','g');
        if (params[param]!==null) {
          template = template.replace(regex, params[param]);
        } else {
          template = template.replace(regex, '');
        }
      });
      // Also do some standard params from the settings, for various paths/urls
      regex = new RegExp('\\{rootFolder\\}','g');
      template = template.replace(regex, div.settings.rootFolder);
      regex = new RegExp('\\{imageFolder\\}','g');
      template = template.replace(regex, div.settings.imageFolder);
      regex = new RegExp('\\{currentUrl\\}','g');
      template = template.replace(regex, div.settings.currentUrl);
      return template;
    };
    
    setupPaginationClicks = function(div) {
      // Define pagination clicks.
      if (div.settings.itemsPerPage!==null) {
        $(div).find('.pager .next').click(function(e) {
          e.preventDefault();
          if (div.loading) {return;}
          div.loading = true;
          div.settings.offset += div.settings.itemsPerPage;
          load(div);
        });
        
        $(div).find('.pager .prev').click(function(e) {
          e.preventDefault();
          if (div.loading) {return;}
          div.loading = true;
          div.settings.offset -= div.settings.itemsPerPage;
          // Min offset is zero, shouldn't really happen.
          if (div.settings.offset<0) {div.settings.offset=0;}
          load(div);
        });
      }
    }
    
    return this.each(function() {
      this.settings = opts;
      
      // Make this accessible inside functions
      var div=this;
      
      // Define clicks on column headers to apply sort 
      $(this).find('th.sortable').click(function(e) {
        e.preventDefault();
        if (div.loading) {return;}
        div.loading = true;
        // $(this).text() = display label for column
        var colName = $(this).text();
        $.each(div.settings.columns, function(idx, col) {
          if (col.display==colName) {
            colName=col.orderby || col.fieldname;
          }
        });
        if (div.settings.orderby==colName && div.settings.sortdir=='ASC') {
          div.settings.sortdir = 'DESC';
        } else {
          div.settings.sortdir = 'ASC';
        }
        div.settings.orderby = colName;
        // Change sort to this column [DESC?]
        // reload the data
        load(div);
      });    

      setupPaginationClicks(div);

      // execute callback it there is one
      if (div.settings.callback !== "") {
        window[div.settings.callback]();
      }
    });
  };
})(jQuery);

/**
 * Main default options for the report grid
 */
$.fn.reportgrid.defaults = {
  mode: 'report',
  auth_token : '',
  nonce : '',
  dataSource : '',
  columns : null,
  orderby : null,
  sortdir : 'ASC',
  itemsPerPage : null,
  offset : 0,
  altRowClass : 'odd',
  imageFolder : '',
  rootFolder: '',
  currentUrl: '',
  callback : ''
};
