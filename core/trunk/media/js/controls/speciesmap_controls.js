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
 * @package Client
 * @subpackage PrebuiltForms
 * @author  Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL 3.0
 * @link    http://code.google.com/p/indicia/
 */

var control_speciesmap_addcontrols;

(function ($) {

control_speciesmap_addcontrols = function(options, translatedStrings) {

    var featureAdded = function (a1) { // on editLayer
            if (a1.feature.attributes.type !== "clickPoint" ) { return true; }
            indiciaData.control_speciesmap_new_feature = a1.feature.clone();
            switch (indiciaData.control_speciesmap_mode) {
            case 'Add':
                do_add_sref();
                break;
            case 'Move':
                end_move();
                break;
            }
        },
        featureSelected = function (a1) { // on subSample layer
            var block = $('#scm-' + a1.feature.attributes.subSampleIndex + '-block'), rowsToShow;
            indiciaData.control_speciesmap_existing_feature = a1.feature; /* not clone */
            switch (indiciaData.control_speciesmap_mode) {
            case 'Modify':
                $('#' + indiciaData.control_speciesmap_opts.id + '-container').show(indiciaData.control_speciesmap_opts.animationDuration).find('.new').removeClass('new');
                $(indiciaData.control_speciesmap_opts.mapDiv).hide(indiciaData.control_speciesmap_opts.animationDuration);
                $('#' + indiciaData.control_speciesmap_opts.id + '-blocks > div').hide();
                $('#' + indiciaData.control_speciesmap_opts.id + ' > tbody > tr').not('.scClonableRow').hide();
                block.show();
                rowsToShow = $("[name$='\:sampleIDX']").filter('[value=' + indiciaData.control_speciesmap_existing_feature.attributes.subSampleIndex + ']').closest('tr');
                rowsToShow.show();
                rowsToShow.next('.supplementary-row').show();
                $('#' + indiciaData.control_speciesmap_opts.messageId).empty().append(indiciaData.control_speciesmap_translatedStrings.ModifyMessage2);
                $('#' + indiciaData.control_speciesmap_opts.finishButtonId).show();
                $('#' + indiciaData.control_speciesmap_opts.id + ' .scClonableRow').find("[name$='\:sampleIDX']").each(function (idx, field) {
                  $(field).val(indiciaData.control_speciesmap_existing_feature.attributes.subSampleIndex);
                });
                break;
            case 'Move':
                begin_move();
                break;
            case 'Delete':
                $('.delete-dialog').empty().append(translatedStrings.ConfirmDeleteText.replace('{OLD}', a1.feature.attributes.sRef));
                indiciaData.control_speciesmap_delete_dialog.dialog('open');
                break;
            }
            return true;
        },
        set_up_summary_rows = function (sampleIDX) {
            var elements = $('.control_speciesmapsummary').find('th:visible').length,
                block = $('#scm-' + sampleIDX + '-block'),
                rows = $("[name$='\:sampleIDX']").filter('[value=' + sampleIDX + ']').closest('tr').not('.scClonableRow');
            if ($('.control_speciesmapsummary').length === 0) {
                return;
            }
            $('.scm-summary-' + sampleIDX).remove();
            if (block.length > 0 && rows.length > 0) {
                $('.control_speciesmapsummary tbody').append('<tr class="scm-summary-' + sampleIDX + '"><td colspan=' + elements + '><span>' +
                    indiciaData.control_speciesmap_translatedStrings.SRefLabel + ': ' + block.find("[name$='\:sample\:entered_sref']").val() + '</td></tr>');
            }
            rows.each(function (idx, elem) {
                var cloned = $(elem).clone();
                cloned.addClass('scm-summary-' + sampleIDX).find('*').removeAttr('id');
                cloned.find('td').filter('[class=],.scSampleCell,.scPresenceCell').remove();
                cloned.find('.deh-required').remove();
                cloned.find('input[type=hidden]').each(function (idx, elem) {
                    $(elem).remove();
                });
                cloned.find('input:text').each(function (idx, elem) {
                    $(elem).after('<span>' + $(elem).val() + '</span>').remove();
                });
                cloned.find('input:checkbox').each(function (idx, elem) {
                    if ($(elem).filter(':checked').length > 0) {
                        $(elem).after('<span>' + indiciaData.control_speciesmap_translatedStrings.Yes + '</span>').remove();
                    } else {
                        $(elem).after('<span>' + indiciaData.control_speciesmap_translatedStrings.No + '</span>').remove();
                    }
                });
                cloned.find('select').each(function (idx, elem) {
                    var text = $('#' + indiciaData.control_speciesmap_opts.id).find('[name=' + $(elem).attr('name').replace(':', '\:') + ']').val();
                    $(elem).val(text);
                    text = $(elem).find('option:selected');
                    if (text.length > 0) {
                        text = text[0].text;
                        $(elem).after('<span>' + text + '</span>').remove();
                    } else {
                        $(elem).remove();
                    }
                });
                cloned.find('input:radio').each(function (idx, elem) {
                    if ($(elem).filter(':checked').length > 0) {
                        var label = "";
                        $(elem).parent().find('label').each(function (idx, elem) {
                            label = label + elem.innerHTML;
                        });
                        $(elem).parent().after('<span>' + label + '</span>').remove();
                    } else {
                        $(elem).parent().remove(); // removes span - includes label.
                    }
                });
                cloned.find('input,label').each(function (idx, elem) {
                    $(elem).remove();
                });
                $('.control_speciesmapsummary tbody').append(cloned);
            });
        },
        fillInMainSref = function () {
            // get centre of bounds: this is in the map projection. Service Call will change that to internal as well as giving the sref.
            var div = $(indiciaData.control_speciesmap_opts.mapDiv)[0],
                extent = indiciaData.SubSampleLayer.getDataExtent(),
                formatter = new OpenLayers.Format.WKT(),
                wkt, centre;
            if (extent !== null) {
              centre = indiciaData.SubSampleLayer.getDataExtent().getCenterLonLat();
              wkt = formatter.extractGeometry(new OpenLayers.Geometry.Point(centre.lon, centre.lat));
              $.getJSON(indiciaData.control_speciesmap_opts.base_url + '/index.php/services/spatial/wkt_to_sref?wkt=' + wkt + '&system=' + $("[name='sample\:entered_sref_system']").val() + '&wktsystem=' + div.map.projection.proj.srsProjNumber + '&precision=8&callback=?',
                  function (data) {
                      if (typeof data.error !== 'undefined') {
                          alert(data.error);
                      } else {
                          $("[name='sample\:entered_sref']").val(data.sref);
                          $("[name='sample\:geom']").val(data.wkt);
                      }
                  });
            }
            // TODO if map projection != indicia internal projection transform to internal projection
        },
        activate = function (me, mode, message) {
            var div = $(indiciaData.control_speciesmap_opts.mapDiv)[0],
                feature,
                scinputs;
            // first check validation state on species grid
            feature = (indiciaData.control_speciesmap_mode === 'Add' ? indiciaData.control_speciesmap_new_feature : indiciaData.control_speciesmap_existing_feature);
            if (typeof feature !== "undefined" && feature !== null) {
                scinputs = $("[name$='\:sampleIDX']").filter('[value=' + feature.attributes.subSampleIndex + ']').closest('tr').filter(':visible').not('.scClonableRow').find('input,select').not(':disabled');
                if (typeof scinputs.valid !== "undefined" && scinputs.length > 0 && !scinputs.valid()) {
                    return; // validation failed: leave everything as was
                }
                set_up_summary_rows(feature.attributes.subSampleIndex);
            }
            // next deactivate current state
            $('#' + indiciaData.control_speciesmap_opts.buttonsId).find('.ui-state-highlight').removeClass('ui-state-highlight');
            indiciaData.control_speciesmap_existing_feature = null;
            indiciaData.control_speciesmap_new_feature = null;
            // Switches off add button functionality - note this equivalent of 'Finishing'
            div.map.editLayer.clickControl.deactivate();
            div.map.editLayer.destroyFeatures();
            $('#imp-sref,#imp-geom').val('');
            $('#' + indiciaData.control_speciesmap_opts.id + '-container').hide(indiciaData.control_speciesmap_opts.animationDuration).find('.new').removeClass('new');
            $(div).show(indiciaData.control_speciesmap_opts.animationDuration, function() { div.map.updateSize(); });
            $('#' + indiciaData.control_speciesmap_opts.finishButtonId + ',#' + indiciaData.control_speciesmap_opts.cancelButtonId).hide();
            // Switch off Move button functionality
            indiciaData.control_speciesmap_selectFeatureControl.unselectAll();
            indiciaData.control_speciesmap_selectFeatureControl.deactivate();
            // Switch off Delete button functionality
            // select feature is switched off above by Move code
            indiciaData.control_speciesmap_delete_dialog.dialog('close');
            // highlight button and display message.
            $(me).addClass('ui-state-highlight');
            $('#' + indiciaData.control_speciesmap_opts.messageId).empty().append(message);
            switch (mode) {
            case 'Add':
                div.map.editLayer.clickControl.activate();
                break;
            case 'Move':
                indiciaData.control_speciesmap_selectFeatureControl.activate();
                break;
            case 'Modify':
            case 'Delete':
                indiciaData.control_speciesmap_selectFeatureControl.activate();
                break;
            }
            $('#imp-sref,#imp-geom').val('');
            // don't fire map events on the sref hidden control, otherwise the map zooms in
            $('#imp-sref').unbind('change');
            indiciaData.control_speciesmap_mode = mode;
        },
        control_speciesmap_addbutton = function () {
            activate(this, 'Add', indiciaData.control_speciesmap_translatedStrings.AddMessage);
        },
        control_speciesmap_modifybutton = function () {
            activate(this, 'Modify', indiciaData.control_speciesmap_translatedStrings.ModifyMessage1);
        },
        control_speciesmap_movebutton = function () {
            activate(this, 'Move', indiciaData.control_speciesmap_translatedStrings.MoveMessage1);
        },
        control_speciesmap_deletebutton = function () {
            activate(this, 'Delete', indiciaData.control_speciesmap_translatedStrings.DeleteMessage);
        },
        control_speciesmap_cancelbutton = function () {
            var div = $(indiciaData.control_speciesmap_opts.mapDiv)[0];
            switch (indiciaData.control_speciesmap_mode) {
            case 'Add':
                $(div).show(indiciaData.control_speciesmap_opts.animationDuration, function() { div.map.updateSize(); });
                $('#' + indiciaData.control_speciesmap_opts.messageId).empty().append(indiciaData.control_speciesmap_translatedStrings.AddMessage);
                $('#' + indiciaData.control_speciesmap_opts.finishButtonId + ',#' + indiciaData.control_speciesmap_opts.cancelButtonId).hide();
                indiciaData.control_speciesmap_selectFeatureControl.unselectAll();
                $('#scm-' + indiciaData.control_speciesmap_new_feature.attributes.subSampleIndex + '-block').remove();
                $("[name$='\:sampleIDX']").filter('[value=' + indiciaData.control_speciesmap_new_feature.attributes.subSampleIndex + ']').closest('tr').not('.scClonableRow').remove();
                set_up_summary_rows(indiciaData.control_speciesmap_new_feature.attributes.subSampleIndex);
                indiciaData.SubSampleLayer.removeFeatures([indiciaData.control_speciesmap_new_feature]);
                fillInMainSref();
                $('#' + indiciaData.control_speciesmap_opts.id + '-container').hide(indiciaData.control_speciesmap_opts.animationDuration);
                break;
            case 'Move':
                div.map.editLayer.clickControl.deactivate(); // to allow user to select new position.
                indiciaData.control_speciesmap_selectFeatureControl.activate();
                indiciaData.control_speciesmap_selectFeatureControl.unselectAll();
                $('#' + indiciaData.control_speciesmap_opts.messageId).empty().append(indiciaData.control_speciesmap_translatedStrings.MoveMessage1);
                $('#' + indiciaData.control_speciesmap_opts.cancelButtonId).hide();
                div.map.editLayer.destroyFeatures();
                $('#imp-sref,#imp-geom').val('');
                indiciaData.control_speciesmap_existing_feature = null;
                break;
            }
        },
        control_speciesmap_finishbutton = function () {
            // first check that any filled in species grid rows pass validation.
            var feature = (indiciaData.control_speciesmap_mode === 'Add' ? indiciaData.control_speciesmap_new_feature : indiciaData.control_speciesmap_existing_feature),
                scinputs, div = $(indiciaData.control_speciesmap_opts.mapDiv)[0];
            scinputs = $("[name$='\:sampleIDX']").filter('[value=' + feature.attributes.subSampleIndex + ']').closest('tr').not('.scClonableRow').find('input,select').not(':disabled');
            if (typeof scinputs.valid !== "undefined" && scinputs.length > 0 && !scinputs.valid()) {
                return; // validation failed: leave everything in sight
            }
            set_up_summary_rows(feature.attributes.subSampleIndex);
            $('#' + indiciaData.control_speciesmap_opts.id + '-container').hide(indiciaData.control_speciesmap_opts.animationDuration).find('.new').removeClass('new');
            $(div).show(indiciaData.control_speciesmap_opts.animationDuration, function() { div.map.updateSize(); });
            switch (indiciaData.control_speciesmap_mode) {
            case 'Add':
                $('#' + indiciaData.control_speciesmap_opts.messageId).empty().append(indiciaData.control_speciesmap_translatedStrings.AddMessage);
                break;
            case 'Modify':
                $('#' + indiciaData.control_speciesmap_opts.messageId).empty().append(indiciaData.control_speciesmap_translatedStrings.ModifyMessage1);
                indiciaData.control_speciesmap_selectFeatureControl.unselectAll();
                break;
            }
            $('#' + indiciaData.control_speciesmap_opts.finishButtonId + ',#' + indiciaData.control_speciesmap_opts.cancelButtonId).hide();
        },
        do_add_sref = function () {
          var div = $(indiciaData.control_speciesmap_opts.mapDiv)[0], subsampleBlock, gridIdx;
          div.map.editLayer.destroyFeatures();
          indiciaData['gridSampleCounter-' + indiciaData.control_speciesmap_opts.id]++;
          indiciaData.control_speciesmap_new_feature.attributes.subSampleIndex = indiciaData['gridSampleCounter-' + indiciaData.control_speciesmap_opts.id];
          indiciaData.control_speciesmap_new_feature.attributes.sRef = $('#imp-sref').val();
          indiciaData.control_speciesmap_new_feature.attributes.count = 0;
          indiciaData.control_speciesmap_new_feature.style = null;
          indiciaData.SubSampleLayer.addFeatures([indiciaData.control_speciesmap_new_feature]);
          fillInMainSref();
          // TODO if map projection != indicia internal projection transform to internal projection
          $(indiciaData.control_speciesmap_opts.mapDiv).hide(indiciaData.control_speciesmap_opts.animationDuration);
          $('#' + indiciaData.control_speciesmap_opts.id + '-container').show(indiciaData.control_speciesmap_opts.animationDuration).find('.new').removeClass('new');
          $('#' + indiciaData.control_speciesmap_opts.id + '-blocks').find(' > div').hide();
          $('#' + indiciaData.control_speciesmap_opts.id + ' > tbody > tr').not('.scClonableRow').hide();
          $('#' + indiciaData.control_speciesmap_opts.id + ' .scClonableRow').find("[name$='\:sampleIDX']").each(function (idx, field) {
              $(field).val(indiciaData.control_speciesmap_new_feature.attributes.subSampleIndex);
          });
          subsampleBlock = $('<div class="new added scm-block" id="scm-' + indiciaData['gridSampleCounter-' + indiciaData.control_speciesmap_opts.id] + '-block"></div>')
              .appendTo('#' + indiciaData.control_speciesmap_opts.id + '-blocks');
          $('<label>' + indiciaData.control_speciesmap_translatedStrings.SRefLabel + ':</label> ').appendTo(subsampleBlock);
          $('<input type="text" name="sc:' + indiciaData['gridSampleCounter-' + indiciaData.control_speciesmap_opts.id] + '::sample:entered_sref" "readonly="readonly" value="' + $('#imp-sref').val() + '" />')
              .appendTo(subsampleBlock);
          $('<input type="hidden" name="sc:' + indiciaData['gridSampleCounter-' + indiciaData.control_speciesmap_opts.id] + '::sample:geom" value="' + $('#imp-geom').val() + '" />')
              .appendTo(subsampleBlock);
          if(typeof options['subSampleSampleMethodID'] != undefined && options['subSampleSampleMethodID'] != '')
              $('<input type="hidden" name="sc:' + indiciaData['gridSampleCounter-' + indiciaData.control_speciesmap_opts.id] + '::sample:sample_method_id" value="' + options['subSampleSampleMethodID'] + '" />')
                .appendTo(subsampleBlock);
          // new rows have no deleted field
          $('#' + indiciaData.control_speciesmap_opts.messageId).empty().append(indiciaData.control_speciesmap_translatedStrings.AddDataMessage);
          $('#' + indiciaData.control_speciesmap_opts.buttonsId).each(function () {window.scroll(0, $(this).offset().top); });
          $('#' + indiciaData.control_speciesmap_opts.finishButtonId + ',#' + indiciaData.control_speciesmap_opts.cancelButtonId).show();
          gridIdx = indiciaData['gridSampleCounter-' + indiciaData.control_speciesmap_opts.id];
          if (typeof indiciaData.control_speciesmap_opts.sampleMethodId !== "undefined" && indiciaData.control_speciesmap_opts.sampleMethodId !== '') {    
            $('<input type="hidden" name="sc:' + indiciaData['gridSampleCounter-' + indiciaData.control_speciesmap_opts.id] + '::sample:sample_method_id" value="' + indiciaData.control_speciesmap_opts.sampleMethodId + '" />')
                .appendTo(subsampleBlock);
            sampleControlsDiv = $('#' + indiciaData.control_speciesmap_opts.id + '-subsample-ctrls').clone(true, true).appendTo(subsampleBlock).show();
            // correct the IDs on the cloned block of sample controls
            $.each(sampleControlsDiv.find('*'), function(idx, elem) {
              if ($(elem).attr('id')) {
                $(elem).attr('id', $(elem).attr('id').replace(/^sc:n::/, 'sc:' + gridIdx + '::'));
                $(elem).attr('id', $(elem).attr('id').replace(/sc-n--/, 'sc-' + gridIdx + '--'));
              }
              if ($(elem).attr('name')) {
                $(elem).attr('name', $(elem).attr('name').replace(/^sc:n::/, 'sc:' + gridIdx + '::'));
              }
              if ($(elem).attr('for')) {
                $(elem).attr('for', $(elem).attr('for').replace(/^sc:n::/, 'sc:' + gridIdx + '::'));
              }
            });
          }
        },
        begin_move = function () {
          var div = $(indiciaData.control_speciesmap_opts.mapDiv)[0];
          indiciaData.control_speciesmap_selectFeatureControl.deactivate();
          // deacivating the control still leaves the selected feature highlighted.
          div.map.editLayer.clickControl.activate(); // to allow user to select new position.
          $('#' + indiciaData.control_speciesmap_opts.messageId).empty().append(indiciaData.control_speciesmap_translatedStrings.MoveMessage2);
          $('#' + indiciaData.control_speciesmap_opts.cancelButtonId).show();
        },
        end_move = function () {
          var div = $(indiciaData.control_speciesmap_opts.mapDiv)[0],
              block = $('#scm-' + indiciaData.control_speciesmap_existing_feature.attributes.subSampleIndex + '-block');
          div.map.editLayer.destroyFeatures();
          div.map.editLayer.clickControl.deactivate(); // to allow user to select new position.
          indiciaData.control_speciesmap_selectFeatureControl.activate();
          indiciaData.control_speciesmap_selectFeatureControl.unselectAll();
          indiciaData.control_speciesmap_new_feature.attributes.subSampleIndex = indiciaData.control_speciesmap_existing_feature.attributes.subSampleIndex;
          indiciaData.control_speciesmap_new_feature.attributes.count = indiciaData.control_speciesmap_existing_feature.attributes.count;
          indiciaData.control_speciesmap_new_feature.attributes.sRef = $('#imp-sref').val();
          indiciaData.control_speciesmap_new_feature.style = null; // needed so picks up style from new layer, including label
          indiciaData.SubSampleLayer.removeFeatures([indiciaData.control_speciesmap_existing_feature]);
          indiciaData.SubSampleLayer.addFeatures([indiciaData.control_speciesmap_new_feature]);
          fillInMainSref();
          block.find("[name$='\:entered_sref']").val($('#imp-sref').val());
          block.find("[name$='\:geom']").val($('#imp-geom').val());
          $('#' + indiciaData.control_speciesmap_opts.messageId).empty().append(indiciaData.control_speciesmap_translatedStrings.MoveMessage1);
          $('#' + indiciaData.control_speciesmap_opts.cancelButtonId).hide();
          indiciaData.control_speciesmap_existing_feature = null;
          indiciaData.control_speciesmap_new_feature = null;
        },
        build_delete_dialog = function () {
            var buttons = {}, // buttons are language specific
                Yes = function () {
                    var block = $('#scm-' + indiciaData.control_speciesmap_existing_feature.attributes.subSampleIndex + '-block');
                    indiciaData.control_speciesmap_delete_dialog.dialog('close');
                    // If the indicia sample id for the grid already exists, then have to flag as deleted, otherwise just wipe it.
                    if (block.hasClass('added')) {
                      block.remove();
                    } else {
                      block.find("[name$='\:sample\:deleted']").val('t').removeAttr('disabled');
                      block.hide();
                    }
                    indiciaData.control_speciesmap_selectFeatureControl.unselectAll();
                    $("[name$='\:sampleIDX']").filter('[value=' + indiciaData.control_speciesmap_existing_feature.attributes.subSampleIndex + ']').closest('tr').not('.scClonableRow').remove();
                    set_up_summary_rows(indiciaData.control_speciesmap_existing_feature.attributes.subSampleIndex);
                    indiciaData.SubSampleLayer.removeFeatures([indiciaData.control_speciesmap_existing_feature]);
                    fillInMainSref();
                },
                No = function () {
                    indiciaData.control_speciesmap_selectFeatureControl.unselectAll();
                    indiciaData.control_speciesmap_delete_dialog.dialog('close');
                };
            buttons[translatedStrings.Yes] = Yes;
            buttons[translatedStrings.No] = No;
            // when we come out of the dialog we need to do stuff, whether yes or no, so can't let user just close the dialog.
            // Disable closeOnEscape and remove close icon
            indiciaData.control_speciesmap_delete_dialog = $('<p class="delete-dialog"></p>')
                .dialog({ title: translatedStrings.ConfirmDeleteTitle, autoOpen: false, buttons: buttons, closeOnEscape: false});
            $('.delete-dialog').closest('.ui-dialog').find('.ui-dialog-titlebar-close').remove();
        },
        defaults = {mapDiv: '#map',
            panelClasses:   'ui-widget-header ui-corner-tl ui-corner-tr',
            buttonsId:      'speciesmap_controls',
            addButtonId:    'speciesmap_addbutton_control',
            modButtonId:    'speciesmap_modbutton_control',
            moveButtonId:   'speciesmap_movebutton_control',
            delButtonId:    'speciesmap_delbutton_control',
            cancelButtonId: 'speciesmap_cancelbutton_control',
            finishButtonId: 'speciesmap_finishbutton_control',
            messageId:      'speciesmap_controls_messages',
            messageClasses: '',
            featureLabel:   'Grid: ${sRef}\nSpecies: ${count}',
            animationDuration: 1000},
        opts,
        container;
    // Extend our default options with those provided, basing this on an empty object
    // so the defaults don't get changed.
    opts = $.extend({}, defaults, options);
    indiciaData.control_speciesmap_opts = opts;
    indiciaData.control_speciesmap_translatedStrings = translatedStrings;
    container = $('<div id="' + opts.buttonsId + '" class="' + opts.panelClasses + '">').insertBefore(opts.mapDiv);
    $('<button id="' + opts.addButtonId + '" class="indicia-button" type="button">' + translatedStrings.AddLabel + '</button>').click(control_speciesmap_addbutton).appendTo(container);
    $('<button id="' + opts.modButtonId + '" class="indicia-button" type="button">' + translatedStrings.ModifyLabel + '</button>').click(control_speciesmap_modifybutton).appendTo(container);
    $('<button id="' + opts.moveButtonId + '" class="indicia-button" type="button">' + translatedStrings.MoveLabel + '</button>').click(control_speciesmap_movebutton).appendTo(container);
    $('<button id="' + opts.delButtonId + '" class="indicia-button" type="button">' + translatedStrings.DeleteLabel + '</button>').click(control_speciesmap_deletebutton).appendTo(container);
    $('<button id="' + opts.cancelButtonId + '" class="indicia-button" type="button">' + translatedStrings.CancelLabel + '</button>').click(control_speciesmap_cancelbutton).appendTo(container).hide();
    $('<button id="' + opts.finishButtonId + '" class="indicia-button" type="button">' + translatedStrings.FinishLabel + '</button>').click(control_speciesmap_finishbutton).appendTo(container).hide();
    $('<div id="' + opts.messageId + '" class="' + opts.messageClasses + '"></div>').appendTo(container);
    build_delete_dialog();
    indiciaData.control_speciesmap_mode = 'Off';

    // We are assuming that this the species map control is invoked after the
    mapInitialisationHooks.push(function (div) {
        if ('#' + div.id === opts.mapDiv) {
            var defaultStyle = $.extend(true, {}, div.map.editLayer.style),
                selectStyle = {fillColor: 'Blue', fillOpacity: 0.3, strokeColor: 'Blue', strokeWidth: 2},
                parentStyle = {fillOpacity: 0, strokeColor: 'Red', strokeWidth: 2},
                cloned;
            defaultStyle.label = indiciaData.control_speciesmap_opts.featureLabel;
            defaultStyle.labelOutlineColor = "white";
            defaultStyle.labelOutlineWidth = 3;
            defaultStyle.labelYOffset = 18;
            indiciaData.SubSampleLayer = new OpenLayers.Layer.Vector('Subsample Points', {displayInLayerSwitcher: false,
                styleMap: new OpenLayers.StyleMap({'default': new OpenLayers.Style(defaultStyle), 'select': new OpenLayers.Style(selectStyle)})});
            indiciaData.ParentSampleLayer = new OpenLayers.Layer.Vector('Parent sample', {displayInLayerSwitcher: true,
                styleMap: new OpenLayers.StyleMap({'default': new OpenLayers.Style(parentStyle)})});
            // note select inherits the label from default
            div.map.addLayer(indiciaData.SubSampleLayer);
            div.map.addLayer(indiciaData.ParentSampleLayer);
            if(div.map.editLayer.features.length > 0) {
              var first = div.map.editLayer.features;
              div.map.editLayer.removeFeatures(first);
              first[0].style = null;
              indiciaData.ParentSampleLayer.addFeatures(first);
            }
            indiciaData.control_speciesmap_selectFeatureControl = new OpenLayers.Control.SelectFeature(indiciaData.SubSampleLayer);
            div.map.addControl(indiciaData.control_speciesmap_selectFeatureControl);
            indiciaData.control_speciesmap_selectFeatureControl.deactivate();
            div.map.editLayer.clickControl.deactivate();
            indiciaData.SubSampleLayer.events.on({featureselected: featureSelected});
            div.map.editLayer.events.on({featureadded: featureAdded});
            cloned = $('#' + indiciaData.control_speciesmap_opts.id + ' > thead > tr').clone();
            cloned.find('th').eq(0).removeAttr('colspan');
            // remove the filter button
            cloned.find('th button').remove();
            $('.control_speciesmapsummary thead').append(cloned);
            // now add existing features.
            $('.scm-block').each(function (idx, block) {
                var id = $(block).attr('id').split('-'),
                    parser = new OpenLayers.Format.WKT(),
                    feature;
                feature = parser.read($(block).find("[name$='sample\:geom']").val()); //style is null
                // TODO should convert from Indicia internal projection to map projection
                feature.attributes.subSampleIndex = id[1];
                feature.attributes.sRef = $(block).find("[name$='sample\:entered_sref']").val();
                feature.attributes.count = $("[name$='\:sampleIDX']").filter('[value=' + feature.attributes.subSampleIndex + ']').closest('tr').not('.scClonableRow').length;
                indiciaData.SubSampleLayer.addFeatures([feature]);
                set_up_summary_rows(feature.attributes.subSampleIndex);
            });
            if (indiciaData.SubSampleLayer.features.length > 0) {
                indiciaData.SubSampleLayer.map.zoomToExtent(indiciaData.SubSampleLayer.getDataExtent());
            } else if (indiciaData.ParentSampleLayer.features.length > 0) {
                indiciaData.ParentSampleLayer.map.zoomToExtent(indiciaData.ParentSampleLayer.getDataExtent());
            }
            hook_species_checklist_delete_row = function (data) {
                var feature = (indiciaData.control_speciesmap_mode === 'Add' ? indiciaData.control_speciesmap_new_feature : indiciaData.control_speciesmap_existing_feature);
                // need to remove then re-add feature to rebuild label
                indiciaData.SubSampleLayer.removeFeatures([feature]);
                feature.attributes.count = $("[name$='\:sampleIDX']").filter('[value=' + feature.attributes.subSampleIndex + ']').closest('tr').not('.scClonableRow').length;
                feature.style = null;
                indiciaData.SubSampleLayer.addFeatures([feature]);
            };
            hook_species_checklist_new_row.push(hook_species_checklist_delete_row);
            hook_species_checklist_new_row.push(function() {
              var feature = (indiciaData.control_speciesmap_mode === 'Add' ? indiciaData.control_speciesmap_new_feature : indiciaData.control_speciesmap_existing_feature);
              set_up_summary_rows(feature.attributes.subSampleIndex);
            });
            // set the initial mode to add grid refs
            $('#'+opts.addButtonId).click();
        }
    });
}

}(jQuery));