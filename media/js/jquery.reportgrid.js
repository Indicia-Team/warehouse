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

var simple_tooltip;

/**
 * JQuery report grid widget for Indicia. Note that this is designed to attach to an already
 * loaded HTML grid (loaded using PHP on page load), and provides AJAX pagination and sorting without
 * page refreshes. It does not do the initial grid load operation.
 */

(function ($) {
  "use strict";
  /**
   *Function to enable tooltips for the filter inputs
   */
  simple_tooltip = function (target_items, name){
    $(target_items).each(function(i){
      $("body").append("<div class='"+name+"' id='"+name+i+"'><p>"+$(this).attr('title')+"</p></div>");
      var my_tooltip = $("#"+name+i);
      if (my_tooltip.width() > 450) {
        my_tooltip.css({width:"450px"});
      }

      if ($(this).attr("title") !== "" && $(this).attr("title") !== "undefined") {

        $(this).removeAttr("title").mouseover(function(){
          my_tooltip.css({opacity:0.8, display:"none"}).fadeIn(400);
        }).mousemove(function(kmouse){
          var border_top = $(window).scrollTop();
          var border_right = $(window).width();
          var left_pos;
          var top_pos;
          var offset = 20;
          if(border_right - (offset *2) >= my_tooltip.width() + kmouse.pageX){
            left_pos = kmouse.pageX+offset;
          } else {
            left_pos = border_right-my_tooltip.width()-offset;
          }

          if(border_top + (offset *2)>= kmouse.pageY - my_tooltip.height()){
            top_pos = border_top +offset;
          } else {
            top_pos = kmouse.pageY-offset;
          }
          my_tooltip.css({left:left_pos, top:top_pos});
        }).mouseout(function(){
          my_tooltip.css({left:"-9999px"});
        });

      }

    });
  };

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

    function getUrlParamsForAllRecords(div) {
      var request = {}, paramName;
      // Extract any parameters from the attached form as long as they are report parameters
      $('form#'+div.settings.reportGroup+'-params input, form#'+div.settings.reportGroup+'-params select').each(function(idx, input) {
        if (input.type!=='submit' && $(input).attr('name').indexOf(div.settings.reportGroup+'-')===0
            && (input.type!=="checkbox" || $(input).attr('checked'))) {
          paramName = $(input).attr('name').replace(div.settings.reportGroup+'-', '');
          request[paramName] = $(input).attr('value');
        }
      });
      if (typeof div.settings.extraParams !== "undefined") {
        $.each(div.settings.extraParams, function(key, value) {
          // skip sorting params if the grid has its own sort applied by clicking a column title
          if ((key!=='orderby' && key!=='sortdir') || div.settings.orderby === null) {
            request[key] = value;
          }
        });
      }
      $.extend(request, getQueryParam(div));
      return request;
    }

    function mergeParamsIntoTemplate (div, params, template) {
      var regex, regexEsc, regexEscDbl, regexHtmlEsc, regexHtmlEscDbl, r;
      $.each(params, function(param) {
        regex = new RegExp('\\{'+param+'\\}','g');
        regexEsc = new RegExp('\\{'+param+'-escape-quote\\}','g');
        regexEscDbl = new RegExp('\\{'+param+'-escape-dblquote\\}','g');
        regexHtmlEsc = new RegExp('\\{'+param+'-escape-htmlquote\\}','g');
        regexHtmlEscDbl = new RegExp('\\{'+param+'-escape-htmldblquote\\}','g');
        r = params[param] || '';
        template = template.replace(regex, r);
        template = template.replace(regexEsc, r.replace("'","\\'"));
        template = template.replace(regexEscDbl, r.replace('"','\\"'));
        template = template.replace(regexHtmlEsc, r.replace("'","&#39;"));
        template = template.replace(regexHtmlEscDbl, r.replace('"','&quot;'));
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
      var result='', onclick, href, content, img;
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
            var link = action.url, linkParams=[];
            row.rootFolder = div.settings.rootFolder;
            if (div.settings.pathParam !== '' && link.indexOf('?'+div.settings.pathParam+'=') === -1) {
              //if there is a path param but it is not in the link already then add it to the rootFolder
              row.rootFolder += '?'+div.settings.pathParam+'=';
            }
            if (link.substr(0, 12).toLowerCase()!=='{rootfolder}' && link.substr(0, 12).toLowerCase()!=='{currenturl}'
                && link.substr(0, 4).toLowerCase()!=='http') {
              link='{rootFolder}'+link;
            }
            link = mergeParamsIntoTemplate(div, row, link);
            if (typeof action.urlParams !== "undefined") {
              if (link.indexOf('?')===-1) {
                link += '?';
              } else {
                link += '&';
              }
              $.each(action.urlParams, function(name, value) {
                linkParams.push(name + '=' + value);
              });
            }
            link = link + mergeParamsIntoTemplate(div, row, linkParams.join('&'));
            href=' href="' + link + '"';
          } else {
            href='';
          }
          if (typeof action.img!=="undefined") {
            img=action.img.replace(/{rootFolder}/g, div.settings.rootFolder);
            content = '<img src="'+img+'" title="'+action.caption+'" />';
          } else
            content = action.caption;
          result += '<a class="action-button"'+onclick+href+'>'+content+'</a>';
        }
      });
      return result;
    }

    function simplePager (pager, div, hasMore) {
      var pagerContent='';
      if (div.settings.offset!==0) {
        pagerContent += '<a class="pag-prev pager-button" rel="nofollow" href="#">previous</a> ';
      } else {
        pagerContent += '<span class="pag-prev pager-button ui-state-disabled">previous</span> ';
      }

      if (hasMore) {
        pagerContent += '<a class="pag-next pager-button" rel="nofollow" href="#">next</a>';
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
        pagerContent = pagerContent.replace('{prev}', '<a class="pag-prev pager-button" rel="nofollow" href="#">'+div.settings.langPrev+'</a> ');
        pagerContent = pagerContent.replace('{first}', '<a class="pag-first pager-button" rel="nofollow" href="#">'+div.settings.langFirst+'</a> ');
      } else {
        pagerContent = pagerContent.replace('{prev}', '<span class="pag-prev pager-button ui-state-disabled">'+div.settings.langPrev+'</span> ');
        pagerContent = pagerContent.replace('{first}', '<span class="pag-first pager-button ui-state-disabled">'+div.settings.langFirst+'</span> ');
      }

      if (hasMore)  {
        pagerContent = pagerContent.replace('{next}', '<a class="pag-next pager-button" rel="nofollow" href="#">'+div.settings.langNext+'</a> ');
        pagerContent = pagerContent.replace('{last}', '<a class="pag-last pager-button" rel="nofollow" href="#">'+div.settings.langLast+'</a> ');
      } else {
        pagerContent = pagerContent.replace('{next}', '<span class="pag-next pager-button ui-state-disabled">'+div.settings.langNext+'</span> ');
        pagerContent = pagerContent.replace('{last}', '<span class="pag-last pager-button ui-state-disabled">'+div.settings.langLast+'</span> ');
      }

      for (page=Math.max(1, div.settings.offset/div.settings.itemsPerPage-4);
          page<=Math.min(div.settings.offset/div.settings.itemsPerPage+6, Math.ceil(div.settings.recordCount / div.settings.itemsPerPage));
          page += 1) {
        if (page===div.settings.offset/div.settings.itemsPerPage+1) {
          pagelist += '<span class="pag-page pager-button ui-state-disabled" id="page-' + div.settings.id+ '-'+page+'">'+page+'</span> ';
        } else {
          pagelist += '<a href="#" class="pag-page pager-button" rel="nofollow" id="page-' + div.settings.id+ '-'+page+'">'+page+'</a> ';
        }
      }
      pagerContent = pagerContent.replace('{pagelist}', pagelist);
      if (div.settings.recordCount===0) {
        pagerContent=pagerContent.replace('{showing}', div.settings.noRecords);
      } else {
        showing = showing.replace('{1}', div.settings.offset+1);
        showing = showing.replace('{2}', div.settings.offset + $(div).find('tbody').children().length);
        showing = showing.replace('{3}', div.settings.recordCount);
        pagerContent = pagerContent.replace('{showing}', showing);
      }
      
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
        return {query: JSON.stringify(query)};
      } else {
        return {};
      }
    }

    function loadGridFrom (div, request, clearExistingRows) {
      // overlay on the body, unless no records yet loaded as body is empty
      var elem = div.settings.recordCount ? $(div).find('tbody') :  $(div).find('table'),
          offset = div.settings.recordCount ? [0,0,-1,0] : [0,1,-2,-2],
          rowTitle;
      // skip the loading overlay in <IE9 as it is buggy
      if ($.support.cssFloat) {
        $(div).find(".loading-overlay").css({
          top     : $(elem).position().top+offset[0],
          left    : $(elem).position().left+offset[1],
          width   : $(elem).outerWidth()+offset[2],
          height  : $(elem).outerHeight()+offset[3]
        });
        $(div).find(".loading-overlay").show();
      }
      $.ajax({
        dataType: "json",
        url: request,
        data: null,
        success: function(response) {
          var tbody = $(div).find('tbody'), rows, rowclass, rowclasses, hasMore=false,
              value, rowInProgress=false, rowOutput, rowId, features=[],
              feature, geom, map, valueData;
          // if we get a count back, then update the stored count
          if (typeof response.count !== "undefined") {
            div.settings.recordCount = parseInt(response.count);
            rows = response.records;
          } else {
            rows = response;
          }
          // clear current grid rows
          if (clearExistingRows) {
            tbody.children().remove();
          }
          if (div.settings.sendOutputToMap && typeof indiciaData.reportlayer!=="undefined") {
            map=indiciaData.reportlayer.map;
            indiciaData.mapdiv.removeAllFeatures(indiciaData.reportlayer, 'linked');
          }
          rowTitle = (div.settings.rowId && typeof indiciaData.reportlayer!=="undefined") ?
            ' title="'+div.settings.msgRowLinkedToMapHint+'"' : '';
          $.each(rows, function(rowidx, row) {
            if (div.settings.rowClass!=='') {
              rowclasses=[mergeParamsIntoTemplate(div, row, div.settings.rowClass)];
            } else {
              rowclasses=[];
            }
            if (div.settings.altRowClass!=='' && rowidx%2===0) {
              rowclasses.push(div.settings.altRowClass);
            }
            rowclass = (rowclasses.length>0) ? 'class="' + rowclasses.join(' ') + '" ' : '';
            // We asked for one too many rows. If we got it, then we can add a next page button
            if (div.settings.itemsPerPage !== null && rowidx>=div.settings.itemsPerPage) {
              hasMore = true;
            } else {
              rowId = (div.settings.rowId!=='') ? 'id="row'+row[div.settings.rowId]+'" ' : '';
              // Initialise a new row, unless this is a gallery with multi-columns and not starting a new line
              if ((rowidx % div.settings.galleryColCount)===0) {
                rowOutput = '<tr ' + rowId + rowclass + rowTitle + '>';
                rowInProgress=true;
              }
              // decode any json columns
              $.each(div.settings.columns, function(idx, col) {
                if (typeof col.json!=="undefined" && col.json && typeof row[col.fieldname]!=="undefined") {
                  valueData = JSON.parse(row[col.fieldname]);
                  $.extend(row, valueData);
                }
              });
              $.each(div.settings.columns, function(idx, col) {
                if (div.settings.sendOutputToMap && typeof indiciaData.reportlayer!=="undefined" &&
                    typeof col.mappable!=="undefined" && (col.mappable==="true" || col.mappable===true)) {
                  geom=OpenLayers.Geometry.fromWKT(row[col.fieldname]);
                  if (map.projection.getCode() != map.div.indiciaProjection.getCode()) {
                    geom.transform(map.div.indiciaProjection, map.projection);
                  }
                  geom = geom.getCentroid();
                  feature = new OpenLayers.Feature.Vector(geom, {type: 'linked'});
                  if (div.settings.rowId!=="") {
                    feature.id = row[div.settings.rowId];
                    feature.attributes[div.settings.rowId] = row[div.settings.rowId];
                  }
                  features.push(feature);
                }
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
                  if ((col.img === true || col.img==='true') && value!=='') {
                    var imgs = value.split(','), imgclass=imgs.length>1 ? 'multi' : 'single', match;
                    value='';
                    $.each(imgs, function(idx, img) {
                      match = img.match(/^http(s)?:\/\/(www\.)?([a-z]+)/);
                      if (match!==null) {
                        value += '<a href="'+img+'" class="social-icon '+match[3]+'"></a>';
                      } else if ($.inArray(img.split('.').pop(), ['mp3','wav'])>-1) {
                        value += '<audio controls src="'+div.settings.imageFolder+img+'" type="audio/mpeg"/>';
                      } else {
                        value += '<a href="'+div.settings.imageFolder+img+'" class="fancybox ' + imgclass + '"><img src="'+div.settings.imageFolder+'thumb-'+img+'" /></a>';
                      }
                      
                    });
                  }
                  rowOutput += '<td>' + value + '</td>';
                }
              });
              if ((rowidx % div.settings.galleryColCount)===div.settings.galleryColCount-1) {
                rowOutput += '</tr>';
                tbody.append(rowOutput);
                rowInProgress=false;
              }
            }
          });
          if (rowInProgress) {
            rowOutput += '</tr>';
            tbody.append(rowOutput);
          }
          tbody.find('a.fancybox').fancybox();
          if (features.length>0) {
            indiciaData.reportlayer.addFeatures(features);
            map.zoomToExtent(indiciaData.reportlayer.getDataExtent());
          }

          // Set a class to indicate the sorted column
          $('#' + div.id + ' th').removeClass('asc');
          $('#' + div.id + ' th').removeClass('desc');
          if (div.settings.orderby) {
            $('#' + div.id + '-th-' + div.settings.orderby).addClass(div.settings.sortdir.toLowerCase());
          }
          updatePager(div, hasMore);
          div.loading=false;
          setupReloadLinks(div);
          if ($.support.cssFloat) {$(div).find(".loading-overlay").hide();}

          // execute callback it there is one
          if (div.settings.callback !== "") {
            window[div.settings.callback]();
          }
          
        },
        error: function() {
          if ($.support.cssFloat) {$(div).find(".loading-overlay").hide();}
          alert('The report did not load correctly.');
        }
      });
    }

    /**
     * Build the URL required for a report request, excluding the pagination (limit + offset) parameters. Option to exclude the sort info and idlist param.
     */
    function getFullRequestPathWithoutPaging(div, sort, idlist) {
      var request = getRequest(div), params=getUrlParamsForAllRecords(div);
      $.each(params, function(key, val) {
        if (!idlist && key==='idlist') {
          // skip the idlist param value as we want the whole map
          val='';
        }
        request += '&' + key + '=' + encodeURIComponent(val);
      });
      if (sort && div.settings.orderby !== null) {
        request += '&orderby=' + div.settings.orderby + '&sortdir=' + div.settings.sortdir;
      }
      return request;
    }

    /**
     * Function to make a service call to load the grid data.
     */
    function load (div, recount) {
      var request = getFullRequestPathWithoutPaging(div, true, true);
      request += '&offset=' + div.settings.offset;
      if (recount) {
        request += '&wantCount=1';
      }
      // Ask for one more row than we need so we know if the next page link is available
      if (div.settings.itemsPerPage !== null) {
        request += '&limit=' + (div.settings.itemsPerPage+1);
      }
      loadGridFrom(div, request, true);
    }

    // Sets up various clickable things like the filter button on a direct report, or the pagination links.
    function setupReloadLinks (div) {
      var lastPageOffset = Math.max(0, Math.floor((div.settings.recordCount-1) / div.settings.itemsPerPage)*div.settings.itemsPerPage);
      // Define pagination clicks.
      if (div.settings.itemsPerPage!==null) {
        $(div).find('.pager .pag-next').click(function(e) {
          e.preventDefault();
          if (div.loading) {return;}
          div.loading = true;
          div.settings.offset += $(div).find('tbody').children().length; // in case not showing full page after deletes
          if (div.settings.offset>lastPageOffset) {
            div.settings.offset=lastPageOffset;
          } 
          load(div, false);
        });

        $(div).find('.pager .pag-prev').click(function(e) {
          e.preventDefault();
          if (div.loading) {return;}
          div.loading = true;
          div.settings.offset -= div.settings.itemsPerPage;
          // Min offset is zero.
          if (div.settings.offset<0) {div.settings.offset=0;}
          load(div, false);
        });

        $(div).find('.pager .pag-first').click(function(e) {
          e.preventDefault();
          if (div.loading) {return;}
          div.loading = true;
          div.settings.offset = 0;
          load(div, false);
        });

        $(div).find('.pager .pag-last').click(function(e) {
          e.preventDefault();
          if (div.loading) {return;}
          div.loading = true;
          div.settings.offset = lastPageOffset;
          load(div, false);
        });

        $(div).find('.pager .pag-page').click(function(e) {
          e.preventDefault();
          if (div.loading) {return;}
          div.loading = true;
          var page = this.id.replace('page-'+div.settings.id+'-', '');
          div.settings.offset = (page-1) * div.settings.itemsPerPage;
          load(div, false);
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
          load(div, true);
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

    this.getUrlParamsForAllRecords = function() {
      var r;
      // loop, though we only return 1.
      $.each($(this), function(idx, div) {
        r=getUrlParamsForAllRecords(div, false);
      });
      return r;
    };

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

    this.reload = function(recount) {
      recount = (typeof recount==="undefined") ? false : recount;
      $.each($(this), function(idx, div) {
        load(div, recount);
      });
    };
    
    /**
     * Public method to support late-loading of the initial page of grid data via AJAX.
     * Automatically waits for the current tab to load if on jquery tabs.
     */
    this.ajaxload = function() {
      // are we on a hidden tab?
      if ($(this).parents('.ui-tabs-panel').hasClass('ui-tabs-hide')) {
        var report=this;
        $($(this).parents('.ui-tabs-panel').parent()).bind('tabsshow', function(evt, ui) {
          if (ui.panel.id===$(report).parents('.ui-tabs-panel')[0].id) {
            report.reload(true);
            $(this).unbind(evt);
          }
        });
      } else {
        this.reload(true);
      }
    };
    
    var BATCH_SIZE=2000, currentMapRequest;
        
    function hasIntersection(a, b) {
      var ai=0, bi=0;

      while( ai < a.length && bi < b.length ){
         if      (a[ai] < b[bi] ){ ai++; }
         else if (a[ai] > b[bi] ){ bi++; }
         else /* they're equal */
         {
           return true;
         }
      }

      return false;
    }
    
    function _internalMapRecords(div, request, offset, callback, recordCount) {
      $(indiciaData.mapdiv).parent().find(".loading-overlay").css({
          top     : $(indiciaData.mapdiv).position().top,
          left    : $(indiciaData.mapdiv).position().left,
          width   : $(indiciaData.mapdiv).outerWidth(),
          height  : $(indiciaData.mapdiv).outerHeight()
      });
      $('#map-loading').show();
      var matchString, feature;
      // first call- get the record count
      $.ajax({
        dataType: "json",
        url: request + '&offset=' + offset + (typeof recordCount==="undefined" ? '&wantCount=1' : ''),
        success: function(response) {
          if (typeof recordCount==="undefined") {
            recordCount = response.count;
            response = response.records;
          }
          // implement a crude way of aborting out of date requests, since jsonp does not support xhr
          // therefore no xhr.abort...&jsonp
          matchString = this.url.replace(/((jsonp\d+)|(jQuery\d+_\d+))/, '?').substring(0, currentMapRequest.length);
          if (matchString===currentMapRequest) {
            // start the load of the next batch
            if (offset+BATCH_SIZE<recordCount) {
              _internalMapRecords(div, request, offset+BATCH_SIZE, async, recordCount);
            }              
            // whilst that is loading, put the dots on the map
            var features=[];
            $.each(response, function (idx, obj) {
              feature=indiciaData.mapdiv.addPt(features, obj, 'geom', {"type":"vector"}, obj[div.settings.rowId]);
              if (typeof indiciaData.selectedRows!=="undefined" && 
                  ((typeof obj[div.settings.rowId]!=="undefined" && $.inArray(div.settings.rowId, indiciaData.selectedRows)) ||
                  // plural - report returns list of IDs
                  (typeof obj[div.settings.rowId+'s']!=="undefined" && hasIntersection(obj[div.settings.rowId+'s'].split(','), indiciaData.selectedRows)))) {
                feature.renderIntent='select';
                indiciaData.reportlayer.selectedFeatures.push(feature);
              }
            });
            indiciaData.reportlayer.addFeatures(features);
            if (offset+BATCH_SIZE>=recordCount) {
              $('#map-loading').hide();
            }
          }
          if (callback!==null) {
            callback();
          }
        }
      });
    }

    /** 
     * Public function which loads the current report request output onto a map. 
     * The request is handled in chunks of 1000 records. Optionally supply an id to map just 1 record.
     */
    function mapRecords(div, zooming, id, callback) {
      if (typeof indiciaData.mapdiv==="undefined" || typeof indiciaData.reportlayer==="undefined") {
        return false;
      }
      var layerInfo = {bounds: null}, map=indiciaData.mapdiv.map, currentBounds=null;
      // we need to reload the map layer using the mapping report, so temporarily switch the report      
      var origReport=div.settings.dataSource, request;
      if (div.settings.mapDataSource!=='') {
        if (map.resolution>30 && div.settings.mapDataSourceLoRes) {
          div.settings.dataSource=div.settings.mapDataSourceLoRes;
        } else {
          div.settings.dataSource=div.settings.mapDataSource;
        }
      }
      try {
        request=getFullRequestPathWithoutPaging(div, false, false)+'&limit='+BATCH_SIZE;
        if (map.resolution>600 && div.settings.mapDataSourceLoRes) {
          request += '&sq_size=10000';
          layerInfo.zoomLayerIdx = 0;
        } else if (map.resolution>120 && div.settings.mapDataSourceLoRes) {
          request += '&sq_size=2000';
          layerInfo.zoomLayerIdx = 1;
        } else if (map.resolution>30 && div.settings.mapDataSourceLoRes) {
          request += '&sq_size=1000';
          layerInfo.zoomLayerIdx = 2;
        } else {
          layerInfo.zoomLayerIdx = 3;
        }
        layerInfo.report=div.settings.dataSource;
        if (typeof id!=="undefined") {
          request += '&' + div.settings.rowId + '=' + id;
        } else {
          // if zoomed in below a 10k map, use the map bounding box to limit the loaded features. Having an indexed site filter changes the threshold as it is less necessary.
          if (map.zoom<=600 && div.settings.mapDataSourceLoRes && 
              (map.zoom<=30 || typeof div.settings.extraParams.indexed_location_id==="undefined" || div.settings.extraParams.indexed_location_id==='')) {
            // get the current map bounds. If zoomed in close, get a larger bounds so that the map can be panned a bit without reload.          
            layerInfo.bounds = map.calculateBounds(map.getCenter(), Math.max(39, map.getResolution()));
            // plus the current bounds to test if a reload is necessary
            currentBounds = map.calculateBounds();
            if (map.projection.getCode() != indiciaData.mapdiv.indiciaProjection.getCode()) {
              layerInfo.bounds.transform(map.projection, indiciaData.mapdiv.indiciaProjection);
              currentBounds.transform(map.projection, indiciaData.mapdiv.indiciaProjection);
            }
            request += '&bounds='+encodeURIComponent(layerInfo.bounds.toGeometry().toString());
          }
        }
      }      
      finally {
        div.settings.dataSource=origReport;
      }
      if (!zooming || typeof indiciaData.loadedReportLayerInfo==="undefined" || layerInfo.report!==indiciaData.loadedReportLayerInfo.report
          || (indiciaData.loadedReportLayerInfo.bounds!==null && (currentBounds===null || !indiciaData.loadedReportLayerInfo.bounds.containsBounds(currentBounds)))
          || layerInfo.zoomLayerIdx!==indiciaData.loadedReportLayerInfo.zoomLayerIdx) {
        indiciaData.mapdiv.removeAllFeatures(indiciaData.reportlayer, 'linked', true);
        currentMapRequest = request;
        _internalMapRecords(div, request, 0, typeof callback==="undefined" ? null : callback);
        if (typeof id==="undefined") {
          // remmeber the layer we just loaded, so we can only reload if really required. If loading a single record, this doesn't count.
          indiciaData.loadedReportLayerInfo=layerInfo;
        }
      }
    }
    
    this.mapRecords = function(report, reportLoRes, zooming) {
      if (typeof zooming==="undefined") {
        zooming=false;
      }
      $.each($(this), function(idx, div) {
        div.settings.mapDataSource = report;
        if (reportLoRes) {
          div.settings.mapDataSourceLoRes = reportLoRes;
        }
        mapRecords(div, zooming);
      });
    };
    
    /**
     * Public method to be called after deleting rows from the grid - to keep paginator updated
     */
    this.removeRecordsFromPage = function(count) {
      $.each($(this), function(idx, div) {
        div.settings.recordCount -= count;
        updatePager(div, true);
        setupReloadLinks(div);
      });
    };

    return this.each(function() {
      this.settings = opts;

      // Make this accessible inside functions
      var div=this;

      // Define clicks on column headers to apply sort
      $(div).find('th.sortable').click(function(e) {
        e.preventDefault();
        if (div.loading) {return;}
        div.loading = true;
        // $(this).text() = display label for column, but this may have had language string translation carried out against it.
        // use hidden input field to store the field name.
        var colName = $(this).find('input').val();
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
        load(div, false);
      });

      $(div).find('.report-download-link').click(function(e) {
        e.preventDefault();
        var url=$(e.target).attr('href'), field;
        $.each($(div).find('tr.filter-row input'), function(idx, input) {
          if ($(input).val()!=='') {
            field=input.id.replace('col-filter-', '');
            url += '&' + field + '=' + $(input).val();
          }
        });
        $.each(div.settings.extraParams, function(field, val) {
          if (field.match(/^[a-zA-Z_]+$/)) { // filter out rubbish in the extraParams
            // strip any prior values out before replacing with the latest filter settings
            url = url.replace(new RegExp(field + '=[^&]*&?'), '') + '&' + field + '=' + encodeURIComponent(val);
          }
        });
        window.location=url;
      });
      
      var doFilter = function(e) {
        if (e.target.hasChanged) {
          var fieldname = e.target.id.match(new RegExp('^col-filter-(.*)-' + div.id + '$'))[1];
          if ($.trim($(e.target).val())==='') {
            delete div.settings.extraParams[fieldname];
          } else {
            div.settings.extraParams[fieldname] = $(e.target).val();
          }
          div.settings.offset=0;
          load(div, true);
          if (div.settings.linkFilterToMap && typeof indiciaData.reportlayer!=="undefined") {
            mapRecords(div);
          }
          e.target.hasChanged = false;
        }
      };
      $(this).find('th .col-filter').focus(function(e) {
        e.target.hasChanged = false;
      });
      $(this).find('th .col-filter').change(function(e) {
        e.target.hasChanged = true;
      });
      $(this).find('th .col-filter').blur(doFilter);
      $(this).find('th .col-filter').keypress(function(e) {
        e.target.hasChanged = true;
        if (e.keyCode===13) {
          doFilter(e);
        }
      });

      setupReloadLinks(div);

      if (div.settings.rowId) {
        // Setup highlighting of features on an associated map when rows are clicked
        $(div).find('tbody').click(function(e) {
          if ($(e.target).hasClass('no-select')) {
            // clicked object might request no auto row selection
            return;
          }
          if (typeof indiciaData.reportlayer!=="undefined") {
            var tr=$(e.target).parents('tr')[0], featureId=tr.id.substr(3), 
                featureArr, map=indiciaData.reportlayer.map;
            featureArr=map.div.getFeaturesByVal(indiciaData.reportlayer, featureId, div.settings.rowId);
            // deselect any existing selection and select the feature
            map.setSelection(indiciaData.reportlayer, featureArr);
            $(div).find('tbody tr').removeClass('selected');
            // select row
            $(tr).addClass('selected');
          }
        });
        $(div).find('tbody').dblclick(function(e) {
          if (typeof indiciaData.reportlayer!=="undefined") {
            var tr=$(e.target).parents('tr')[0], featureId=tr.id.substr(3), 
                featureArr, map=indiciaData.reportlayer.map, extent, zoom;
            featureArr=map.div.getFeaturesByVal(indiciaData.reportlayer, featureId, div.settings.rowId);
            var zoomToFeature=function() {
              if (featureArr.length!==0) {
                extent = featureArr[0].geometry.getBounds().clone();
                for(var i=1;i<featureArr.length;i++) {
                    extent.extend(featureArr[i].geometry.getBounds());
                }
                zoom = indiciaData.reportlayer.map.getZoomForExtent(extent)-2;
                indiciaData.reportlayer.map.setCenter(extent.getCenterLonLat(), zoom);
                indiciaData.mapdiv.map.events.triggerEvent('moveend');
              }
            }
            if (featureArr.length===0) {
              // feature not available on the map, probably because we are loading just the viewport and 
              // and the point is not visible. So try to load it with a callback to zoom in.
              mapRecords(div, false, featureId, function() {
                featureArr=map.div.getFeaturesByVal(indiciaData.reportlayer, featureId, div.settings.rowId);
                zoomToFeature();
              });
            } else {
              // feature available on the map, so we can pan and zoom to show it.
              zoomToFeature();
            }
          }
        });
      }

      // execute callback it there is one
      if (div.settings.callback !== "") {
        window[div.settings.callback]();
      }

    });
  };
  
  $('.social-icon').live('click', function(e) {
    e.preventDefault();
    var href=$(e.target).attr('href');
    if (href) {
      $.ajax({
        url: "http://noembed.com/embed?format=json&callback=?&url="+encodeURIComponent(href),
        dataType: 'json',
        success: function(data) {
          if (data.error) {
            alert(data.error);
          } else {
            $.fancybox({
              "title":data.title,
              "content":data.html
            });
          }
        }
      });
    }
    return false;
  });
}(jQuery));

/**
 * Main default options for the report grid
 */
jQuery.fn.reportgrid.defaults = {
  id: 'report',
  mode: 'report',
  auth_token : '',
  nonce : '',
  dataSource : '',
  mapDataSource: '',
  mapDataSourceLoRes: '',
  view: 'list',
  columns : null,
  orderby : null,
  sortdir : 'ASC',
  itemsPerPage : null,
  offset : 0,
  rowClass : '', // template for the output row class
  altRowClass : 'odd',
  rowId: '',
  imageFolder : '',
  rootFolder: '',
  currentUrl: '',
  callback : '',
  filterCol: null,
  filterValue: null,
  langFirst: 'first',
  langPrev: 'prev',
  langNext: 'next',
  langLast: 'last',
  langShowing: 'Showing records {1} to {2} of {3}',
  noRecords: 'No records',
  sendOutputToMap: false, // does the current page of report data get shown on a map?
  linkFilterToMap: false, // requires a rowId - filtering the grid also filters the map
  msgRowLinkedToMapHint: 'Click the row to highlight the record on the map. Double click to zoom in.'
};
