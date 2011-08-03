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

(function ($) {
  $.fn.reportgrid = function (options) {
    // Extend our default options with those provided, basing this on an empty object
    // so the defaults don't get changed.
    var opts = $.extend({}, $.fn.reportgrid.defaults, options),
        // flag to prevent double clicks
        loading=false;
        
    function getRequest(div) {
      var serviceCall, request;
      if (div.settings.mode==='report') {
        serviceCall = 'report/requestReport?report='+div.settings.dataSource+'.xml&reportSource=local&';
      } else if (div.settings.mode==='direct') {
        serviceCall = 'data/' + div.settings.dataSource + '?';
      }
      request = div.settings.url+'index.php/services/' +
          serviceCall +
          'mode=json&nonce=' + div.settings.nonce +
          '&auth_token=' + div.settings.auth_token +
          '&view=' + div.settings.view +          
          '&callback=?';
      return request;
    }
    
    function mergeParamsIntoTemplate (div, params, template) {
      var regex, regexEsc, regexEscDbl, r;
      $.each(params, function(param) {
        regex = new RegExp('\\{'+param+'\\}','g');
        regexEsc = new RegExp('\\{'+param+'-escape-quote\\}','g');
        regexEscDbl = new RegExp('\\{'+param+'-escape-dblquote\\}','g');
        r = params[param] || '';
        template = template.replace(regex, r);
        template = template.replace(regexEsc, r.replace("'","\\'"));
        template = template.replace(regexEscDbl, r.replace('"','\\"'));
      });
      // Also do some standard params from the settings, for various paths/urls
      regex = new RegExp('\\{rootFolder\\}','g');
      template = template.replace(regex, div.settings.rootFolder);
      regex = new RegExp('\\{imageFolder\\}','g');
      template = template.replace(regex, div.settings.imageFolder);
      regex = new RegExp('\\{currentUrl\\}','g');
      template = template.replace(regex, div.settings.currentUrl);
      return template;
    }
    
    function getActions (div, row, actions) {
      var result='', onclick, href;
      $.each(actions, function(idx, action) {
        if (typeof action.visibility_field === "undefined" || row[action.visibility_field]!=='f') {
          if (typeof action.javascript !== "undefined") {
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
          if (typeof action.url !== "undefined") {
            var link = action.url;
            if (typeof action.urlParams !== "undefined") {
              if (link.indexOf('?')===-1) { link += '?'; }
              else { link += '&'; }
              $.each(action.urlParams, function(name, value) {              
                link += name + '=' + value;
                link += '&';
              });
              if (link.substr(-1)==='&') {
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
        }
      });
      return result;
    }
    
    function simplePager (pager, div, hasMore) {
      var pagerContent='';
      if (div.settings.offset!==0) {
        pagerContent += '<a class="pag-prev pager-button" href="#">previous</a> ';
      } else {
        pagerContent += '<span class="pag-prev pager-button ui-state-disabled">previous</span> ';
      }
      
      if (hasMore) {
        pagerContent += '<a class="pag-next pager-button" href="#">next</a>';
      } else {
        pagerContent += '<span class="pag-next pager-button ui-state-disabled">next</span>';
      }
      if (div.settings.offset!==0 || hasMore) {
        pager.append(pagerContent);
      }
    }
    
    function advancedPager (pager, div, hasMore) {
      var pagerContent=div.settings.pagingTemplate, pagelist = '', page, showing = div.settings.langShowing;
      if (div.settings.offset!==0) {
        pagerContent = pagerContent.replace('{prev}', '<a class="pag-prev pager-button" href="#">'+div.settings.langPrev+'</a> ');
        pagerContent = pagerContent.replace('{first}', '<a class="pag-first pager-button" href="#">'+div.settings.langFirst+'</a> ');
      } else {
       pagerContent = pagerContent.replace('{prev}', '<span class="pag-prev pager-button ui-state-disabled">'+div.settings.langPrev+'</span> ');
       pagerContent = pagerContent.replace('{first}', '<span class="pag-first pager-button ui-state-disabled">'+div.settings.langFirst+'</span> ');
      }
      
      if (hasMore)  {
        pagerContent = pagerContent.replace('{next}', '<a class="pag-next pager-button" href="#">'+div.settings.langNext+'</a> ');
        pagerContent = pagerContent.replace('{last}', '<a class="pag-last pager-button" href="#">'+div.settings.langLast+'</a> ');
      } else {
        pagerContent = pagerContent.replace('{next}', '<span class="pag-next pager-button ui-state-disabled">'+div.settings.langNext+'</span> ');
        pagerContent = pagerContent.replace('{last}', '<span class="pag-last pager-button ui-state-disabled">'+div.settings.langLast+'</span> ');
      }

      for (page=Math.max(1, div.settings.offset/div.settings.itemsPerPage-4); 
          page<=Math.min(div.settings.offset/div.settings.itemsPerPage+6, Math.round(div.settings.recordCount / div.settings.itemsPerPage)); 
          page += 1) {
        if (page===div.settings.offset/div.settings.itemsPerPage+1) {
          pagelist += '<span class="pag-page pager-button ui-state-disabled" id="page-' + div.settings.id+ '-'+page+'">'+page+'</span> ';
        } else {
          pagelist += '<a href="#" class="pag-page pager-button" id="page-' + div.settings.id+ '-'+page+'">'+page+'</a> ';
        }
      }
      pagerContent = pagerContent.replace('{pagelist}', pagelist);
      showing = showing.replace('{1}', div.settings.offset+1);
      showing = showing.replace('{2}', Math.min(div.settings.offset + div.settings.itemsPerPage, div.settings.recordCount));
      showing = showing.replace('{3}', div.settings.recordCount);
      pagerContent = pagerContent.replace('{showing}', showing);

      pager.append(pagerContent);
    }
        
    // recreate the pagination footer
    function updatePager (div, hasMore) {
      var pager=$(div).find('.pager');
      pager.empty();
      if (typeof div.settings.recordCount==="undefined") {
        simplePager(pager, div, hasMore);
      } else {
        advancedPager(pager, div, hasMore);
      }
    }
    
    /**
     * Returns the query parameter, which filters the output based on the filters and filtercol/filtervalue.
     */
    function getQueryParam (div) {
      var query={}, needQuery = false;
      if (div.settings.filterCol !== null && div.settings.filterValue !== null) {
        query.like = {};
        query.like[div.settings.filterCol] = div.settings.filterValue;
        needQuery = true;
      }
      // were any predefined parameter values supplied?
      if (typeof div.settings.filters !== "undefined") {
        $.each(div.settings.filters, function(name, value) {
          if ($.isArray(value)) {
            if (typeof query['in']==="undefined") {
              query['in'] = {};
            }
            query['in'][name] = value;
          } else {
            if (typeof query.where==="undefined") {
              query.where = {};
            }
            query.where[name] = value;
          }
          needQuery = true;
        });
      }
      if (needQuery) {
        return '&query=' + JSON.stringify(query);
      } else {
        return '';
      }
    }
    
        function loadGridFrom (div, request, clearExistingRows) {      
      $.getJSON(request,
          null,
          function(response) {
            var tbody = $(div).find('tbody'), row, rows = eval(response), rowclass='', hasMore=false, value, rowInProgress=false, rowOutput;            
            // clear current grid rows
            if (clearExistingRows) {
              tbody.children().remove();
            }
            $.each(rows, function(rowidx, row) {
              // We asked for one too many rows. If we got it, then we can add a next page button
              if (div.settings.itemsPerPage !== null && rowidx>=div.settings.itemsPerPage) {
                hasMore = true;
              } else {
                // Initialise a new row, unless this is a gallery with multi-columns and not starting a new line
                if ((rowidx % div.settings.galleryColCount)===0) {
                  rowOutput = '<tr' + rowclass + '>';
                  rowInProgress=true;
                }
                $.each(div.settings.columns, function(idx, col) {
                  if (col.visible!==false && col.visible!=='false') {
                    // either template the output, or just use the content according to the fieldname
                    if (typeof col.template !== "undefined") {
                      value = mergeParamsIntoTemplate(div, row, col.template);
                    } else if (typeof col.actions !== "undefined") {
                      value = getActions(div, row, col.actions);
                    } else {
                      value = row[col.fieldname];
                    }
                    // clear null value cells
                    value = (value===null || typeof value==="undefined") ? '' : value;
                    if (col.img === true || col.img==='true') {
                      value = '<a href="'+div.settings.imageFolder+value+'" class="fancybox"><img src="'+div.settings.imageFolder+'thumb-'+value+'" /></a>';
                    }
                    rowOutput += '<td>' + value + '</td>';
                  }
                });
                if ((rowidx % div.settings.galleryColCount)===div.settings.galleryColCount-1) {
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
            updatePager(div, hasMore);
            div.loading=false;
            setupReloadLinks(div);

            // execute callback it there is one
            if (div.settings.callback !== "") {
              window[div.settings.callback]();
            }
          }
      );
    }
    
    /**
     * Function to make a service call to load the grid data.
     */
    function load (div) {
      var paramName, request = getRequest(div);
      request += '&offset=' + div.settings.offset;
      // Extract any parameters from the attached form
      $('form#'+div.settings.reportGroup+'-params input, form#'+div.settings.reportGroup+'-params select').each(function(idx, input) {
        if (input.type!=='submit') {
          paramName = $(input).attr('name').replace(div.settings.reportGroup+'-', '');
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
      if (typeof div.settings.extraParams !== "undefined") {
        $.each(div.settings.extraParams, function(key, value) {
          // skip sorting params if the grid has its own sort applied by clicking a column title
          if ((key!=='orderby' && key!=='sortdir') || div.settings.orderby === null) {
            request += '&' + key + '=' + value;
          }
        });
      }
      request += getQueryParam(div);
      loadGridFrom(div, request, true);
    }
    
    // Sets up various clickable things like the filter button on a direct report, or the pagination links.
    function setupReloadLinks (div) {
      // Define pagination clicks.
      if (div.settings.itemsPerPage!==null) {
        $(div).find('.pager .pag-next').click(function(e) {
          e.preventDefault();
          if (div.loading) {return;}
          div.loading = true;
          div.settings.offset += div.settings.itemsPerPage;
          load(div);
        });
        
        $(div).find('.pager .pag-prev').click(function(e) {
          e.preventDefault();
          if (div.loading) {return;}
          div.loading = true;
          div.settings.offset -= div.settings.itemsPerPage;
          // Min offset is zero, shouldn't really happen.
          if (div.settings.offset<0) {div.settings.offset=0;}
          load(div);
        });
        
        $(div).find('.pager .pag-first').click(function(e) {
          e.preventDefault();
          if (div.loading) {return;}
          div.loading = true;
          div.settings.offset = 0;
          load(div);
        });
        
        $(div).find('.pager .pag-last').click(function(e) {
          e.preventDefault();
          if (div.loading) {return;}
          div.loading = true;
          div.settings.offset = Math.round(div.settings.recordCount / div.settings.itemsPerPage-1)*div.settings.itemsPerPage;
          load(div);
        });
        
        $(div).find('.pager .pag-page').click(function(e) {
          e.preventDefault();
          if (div.loading) {return;}
          div.loading = true;
          var page = this.id.replace('page-'+div.settings.id+'-', '');
          div.settings.offset = (page-1) * div.settings.itemsPerPage;
          load(div);
        });
      }
        
      if (div.settings.mode==='direct' && div.settings.autoParamsForm) {
        // define a filter form click
        $(div).find('.run-filter').click(function(e) {
          e.preventDefault();
          div.settings.offset = 0;
          if (div.loading) {return;}
          div.loading = true;
          div.settings.filterCol = $(div).find('.filterSelect').val();
          div.settings.filterValue = $(div).find('.filterInput').val();
          load(div);
          if (div.settings.filterValue==='') {
            $(div).find('.clear-filter').hide();
          } else {
            $(div).find('.clear-filter').show();
          }
        });
        $(div).find('.clear-filter').click(function(e) {
          e.preventDefault();
          $(div).find('.filterSelect').val('');
          $(div).find('.filterInput').val('');
          $(div).find('.run-filter').click();
        });
      }
    }
    
    /**
     * Public function which adds a list of records to the bottom of the grid, loaded according to a filter.
     * Typical usage might be to specify an id to add a single record.
     */
    this.addRecords = function(filterField, filterValue) {
      $.each($(this), function(idx, div) {
        var request = getRequest(div);
        request += '&' + filterField + '=' + filterValue;
        loadGridFrom(div, request, false);
      });
    };
    
    this.reload = function() {
      $.each($(this), function(idx, div) {
        load(div);
      });
    };
    
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
          if (col.display===colName) {
            colName=col.orderby || col.fieldname;
          }
        });
        if (div.settings.orderby===colName && div.settings.sortdir==='ASC') {
          div.settings.sortdir = 'DESC';
        } else {
          div.settings.sortdir = 'ASC';
        }
        div.settings.orderby = colName;
        // Change sort to this column [DESC?]
        // reload the data
        load(div);
      });    

      setupReloadLinks(div);

      // execute callback it there is one
      if (div.settings.callback !== "") {
        window[div.settings.callback]();
      }
     
    });
  };
}(jQuery));

/**
 * Main default options for the report grid
 */
$.fn.reportgrid.defaults = {
  id: 'report',
  mode: 'report',
  auth_token : '',
  nonce : '',
  dataSource : '',
  view: 'list', 
  columns : null,
  orderby : null,
  sortdir : 'ASC',
  itemsPerPage : null,
  offset : 0,
  altRowClass : 'odd',
  imageFolder : '',
  rootFolder: '',
  currentUrl: '',
  callback : '',
  filterCol: null,
  filterValue: null,
  langFirst: 'first',
  langPrev: 'previous',
  langNext: 'next',
  langLast: 'last',
  langShowing: 'Showing records {1} to {2} of {3}'
};
