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

function control_speciesmap_addcontrols(options, translatedStrings) {

    var _featureAdded = function (a1) { // on editLayer
            if (a1.feature.attributes.type === "ghost") { return true; }
            indiciaData.control_speciesmap_new_feature = a1.feature.clone();
            switch (indiciaData.control_speciesmap_mode) {
            case 'Add':
                indiciaData.control_speciesmap_add_dialog.dialog('open');
                break;
            case 'Move':
                $('.move-dialog2-text').empty().append(translatedStrings.ConfirmMove2Text.replace('{OLD}', indiciaData.control_speciesmap_existing_feature.attributes.sRef));
                indiciaData.control_speciesmap_move_dialog2.dialog('open');
                break;
            }
        },
        _featureSelected = function (a1) { // on subSample layer
            var block = $('#scm-' + a1.feature.attributes.subSampleIndex + '-block');
            indiciaData.control_speciesmap_existing_feature = a1.feature; /* not clone */
            switch (indiciaData.control_speciesmap_mode) {
            case 'Modify':
                $('#' + indiciaData.control_speciesmap_opts.id + '-container').show().find('.new').removeClass('new');
                $(indiciaData.control_speciesmap_opts.mapDiv).hide();
                $('#' + indiciaData.control_speciesmap_opts.id + '-blocks > div').hide();
                $('#' + indiciaData.control_speciesmap_opts.id + ' > tbody > tr').not('.scClonableRow').hide();
                block.show();
                $('[name$=\:sampleIDX]').filter('[value=' + indiciaData.control_speciesmap_existing_feature.attributes.subSampleIndex + ']').closest('tr').show();
                $('#' + indiciaData.control_speciesmap_opts.messageId).empty().append(indiciaData.control_speciesmap_translatedStrings.ModifyMessage2);
                $('#' + indiciaData.control_speciesmap_opts.finishButtonId).show();
                $('#' + indiciaData.control_speciesmap_opts.id + ' .scClonableRow').find('[name$=\:sampleIDX]').each(function (idx, field) {
                    $(field).val(indiciaData.control_speciesmap_existing_feature.attributes.subSampleIndex);
                });
                break;
            case 'Move':
                $('.move-dialog1').empty().append(translatedStrings.ConfirmMove1Text.replace('{OLD}', a1.feature.attributes.sRef));
                indiciaData.control_speciesmap_move_dialog1.dialog('open');
                break;
            case 'Delete':
                $('.delete-dialog').empty().append(translatedStrings.ConfirmDeleteText.replace('{OLD}', a1.feature.attributes.sRef));
                indiciaData.control_speciesmap_delete_dialog.dialog('open');
                break;
            }
            return true;
        },
        _activate = function (me, mode, message) {
            var div = $(indiciaData.control_speciesmap_opts.mapDiv)[0];
            // first check validation state on species grid
            var feature = (indiciaData.control_speciesmap_mode === 'Add' ? indiciaData.control_speciesmap_new_feature : indiciaData.control_speciesmap_existing_feature);
            if(typeof feature !== "undefined"){
                var scinputs = $('[name$=\:sampleIDX]').filter('[value=' + feature.attributes.subSampleIndex + ']').closest('tr').filter(':visible').not('.scClonableRow').find('input,select').not(':disabled');
                if (typeof scinputs.valid !== "undefined" && scinputs.length>0 && !scinputs.valid()) {
                  return; // validation failed: leave everything as was
                }
            }
            // next deactivate current state
            $('#' + indiciaData.control_speciesmap_opts.buttonsId).find('.active').removeClass('active');
            indiciaData.control_speciesmap_existing_feature = null;
            indiciaData.control_speciesmap_new_feature = null;
            // Switches off add button functionality - note this equivalent of 'Finishing'
            div.map.editLayer.clickControl.deactivate();
            div.map.editLayer.destroyFeatures();
            $('#imp-sref,#imp-geom').val('');
            indiciaData.control_speciesmap_add_dialog.dialog('close');
            $('#' + indiciaData.control_speciesmap_opts.id + '-container').hide().find('.new').removeClass('new');
            $(indiciaData.control_speciesmap_opts.mapDiv).show();
            $('#' + indiciaData.control_speciesmap_opts.finishButtonId + ',#' + indiciaData.control_speciesmap_opts.cancelButtonId).hide();
            // Switch off Move button functionality
            indiciaData.control_speciesmap_selectFeatureControl.unselectAll();
            indiciaData.control_speciesmap_selectFeatureControl.deactivate();
            indiciaData.control_speciesmap_move_dialog1.dialog('close');
            // Switch off Delete button functionality
            // select feature is switched off above by Move code
            indiciaData.control_speciesmap_delete_dialog.dialog('close');
            // highlight button and display message.
            $(me).addClass('active');
            $('#' + indiciaData.control_speciesmap_opts.messageId).empty().append(message);
            $('#imp-sref,#imp-geom').removeAttr('id').val('');
            switch (mode) {
            case 'Add':
                div.map.editLayer.clickControl.activate();
                $('.add-sref').attr('id', 'imp-sref').val('');
                $('.add-geom').attr('id', 'imp-geom').val('');
                break;
            case 'Move':
                $('.move-sref').attr('id', 'imp-sref').val('');
                $('.move-geom').attr('id', 'imp-geom').val('');
                indiciaData.control_speciesmap_selectFeatureControl.activate();
                break;
            case 'Modify':
            case 'Delete':
                indiciaData.control_speciesmap_selectFeatureControl.activate();
                break;
            }
            $('#imp-sref,#imp-geom').val('');
            indiciaData.control_speciesmap_mode = mode;
        },
        control_speciesmap_addbutton = function () {
            _activate(this, 'Add', indiciaData.control_speciesmap_translatedStrings.AddMessage);
        },
        control_speciesmap_modifybutton = function () {
            _activate(this, 'Modify', indiciaData.control_speciesmap_translatedStrings.ModifyMessage1);
        },
        control_speciesmap_movebutton = function () {
            _activate(this, 'Move', indiciaData.control_speciesmap_translatedStrings.MoveMessage1);
        },
        control_speciesmap_deletebutton = function () {
            _activate(this, 'Delete', indiciaData.control_speciesmap_translatedStrings.DeleteMessage);
        },
        control_speciesmap_cancelbutton = function () {
            var div = $(indiciaData.control_speciesmap_opts.mapDiv)[0];
            switch (indiciaData.control_speciesmap_mode) {
            case 'Add':
                $(indiciaData.control_speciesmap_opts.mapDiv).show();
                $('#' + indiciaData.control_speciesmap_opts.messageId).empty().append(indiciaData.control_speciesmap_translatedStrings.AddMessage);
                $('#' + indiciaData.control_speciesmap_opts.finishButtonId + ',#' + indiciaData.control_speciesmap_opts.cancelButtonId).hide();
                indiciaData.control_speciesmap_selectFeatureControl.unselectAll();
                $('#scm-' + indiciaData.control_speciesmap_new_feature.attributes.subSampleIndex + '-block').remove();
                $('[name$=\:sampleIDX]').filter('[value=' + indiciaData.control_speciesmap_new_feature.attributes.subSampleIndex + ']').closest('tr').not('.scClonableRow').remove();
                indiciaData.SubSampleLayer.removeFeatures([indiciaData.control_speciesmap_new_feature]);
                fillInMainSref();
                $('#' + indiciaData.control_speciesmap_opts.id + '-container').hide();
                break;
            case 'Move':
                div.map.editLayer.clickControl.deactivate(); // to allow user to select new position.
                indiciaData.control_speciesmap_selectFeatureControl.activate();
                indiciaData.control_speciesmap_selectFeatureControl.unselectAll();
                $('#' + indiciaData.control_speciesmap_opts.messageId).empty().append(indiciaData.control_speciesmap_translatedStrings.MoveMessage1);
                $('#' + indiciaData.control_speciesmap_opts.cancelButtonId).hide();
                $('.move-dialog1').empty();
                div.map.editLayer.destroyFeatures();
                $('#imp-sref,#imp-geom').val('');
                indiciaData.control_speciesmap_move_dialog2.dialog('close');
                indiciaData.control_speciesmap_existing_feature = null;
                break;
            }
        },
        control_speciesmap_finishbutton = function () {
        	// first check that any filled in species grid rows pass validation.
            var feature = (indiciaData.control_speciesmap_mode === 'Add' ? indiciaData.control_speciesmap_new_feature : indiciaData.control_speciesmap_existing_feature);
            var scinputs = $('[name$=\:sampleIDX]').filter('[value=' + feature.attributes.subSampleIndex + ']').closest('tr').not('.scClonableRow').find('input,select').not(':disabled');
            if (typeof scinputs.valid !== "undefined" && scinputs.length>0 && !scinputs.valid()) {
              return; // validation failed: leave everything in sight
            }
            $('#' + indiciaData.control_speciesmap_opts.id + '-container').hide().find('.new').removeClass('new');
            $(indiciaData.control_speciesmap_opts.mapDiv).show();
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
        fillInMainSref = function () {
            // get centre of bounds: this is in the map projection. Service Call will change that to internal as well as giving the sref.
            var div = $(indiciaData.control_speciesmap_opts.mapDiv)[0], centre;
                centre = indiciaData.SubSampleLayer.getDataExtent().getCenterLonLat(),
                formatter = new OpenLayers.Format.WKT();
            var wkt = formatter.extractGeometry(new OpenLayers.Geometry.Point(centre.lon, centre.lat));
            $.getJSON(indiciaData.control_speciesmap_opts.base_url + '/index.php/services/spatial/wkt_to_sref?wkt=' + wkt + '&system=' + $('[name=sample\:entered_sref_system]').val() + '&wktsystem=' + div.map.projection.proj.srsProjNumber + '&precision=8&callback=?',
            function (data) {
                if (typeof data.error !== 'undefined') {
                    alert(data.error);
                } else {
                    $('[name=sample\:entered_sref]').val(data.sref);
                    $('[name=sample\:geom]').val(data.wkt);
                }
            });
            // TODO if map projection != indicia internal projection transform to internal projection
        },
        build_add_dialog1 = function () {
            var buttons = {}, // buttons are language specific
                _Yes = function () {
                    var div = $(indiciaData.control_speciesmap_opts.mapDiv)[0], centre;
                    div.map.editLayer.destroyFeatures();
                    indiciaData['gridSampleCounter-' + indiciaData.control_speciesmap_opts.id]++;
                    indiciaData.control_speciesmap_new_feature.attributes.subSampleIndex = indiciaData['gridSampleCounter-' + indiciaData.control_speciesmap_opts.id];
                    indiciaData.control_speciesmap_new_feature.attributes.sRef = $('#imp-sref').val();
                    indiciaData.control_speciesmap_new_feature.attributes.count = 0;
                    indiciaData.control_speciesmap_new_feature.style = null;
                    indiciaData.SubSampleLayer.addFeatures([indiciaData.control_speciesmap_new_feature]);
                    fillInMainSref();
                    // TODO if map projection != indicia internal projection transform to internal projection
                    indiciaData.control_speciesmap_add_dialog.dialog('close');
                    $(indiciaData.control_speciesmap_opts.mapDiv).hide();
                    $('#' + indiciaData.control_speciesmap_opts.id + '-container').show().find('.new').removeClass('new');
                    $('#' + indiciaData.control_speciesmap_opts.id + '-blocks').find('div').hide();
                    $('#' + indiciaData.control_speciesmap_opts.id + ' > tbody > tr').not('.scClonableRow').hide();
                    $('#' + indiciaData.control_speciesmap_opts.id + ' .scClonableRow').find('[name$=\:sampleIDX]').each(function (idx, field) {
                        $(field).val(indiciaData.control_speciesmap_new_feature.attributes.subSampleIndex);
                    });
                    var srefBlock = $('<div class="new added scm-block" id="scm-' + indiciaData['gridSampleCounter-' + indiciaData.control_speciesmap_opts.id] + '-block"></div>').appendTo('#' + indiciaData.control_speciesmap_opts.id + '-blocks');
                    $('<label>' + indiciaData.control_speciesmap_translatedStrings.SRefLabel + ':</label>').appendTo(srefBlock);
                    // new rows have no deleted field
                    $('<input type="text" name="sc:' + indiciaData['gridSampleCounter-' + indiciaData.control_speciesmap_opts.id] + '::sample:entered_sref" "readonly="readonly" value="' + $('#imp-sref').val() + '" />').appendTo(srefBlock);
                    $('<input type="hidden" name="sc:' + indiciaData['gridSampleCounter-' + indiciaData.control_speciesmap_opts.id] + '::sample:geom" value="' + $('#imp-geom').val() + '" />').appendTo(srefBlock);
                    $('#' + indiciaData.control_speciesmap_opts.messageId).empty().append(indiciaData.control_speciesmap_translatedStrings.AddDataMessage);
                    $('#' + indiciaData.control_speciesmap_opts.buttonsId).each(function () {window.scroll(0, $(this).offset().top); });
                    $('#' + indiciaData.control_speciesmap_opts.finishButtonId + ',#' + indiciaData.control_speciesmap_opts.cancelButtonId).show();
                },
                _No = function () {
                    var div = $(indiciaData.control_speciesmap_opts.mapDiv)[0];
                    div.map.editLayer.destroyFeatures();
                    $('#imp-sref,#imp-geom').val('');
                    indiciaData.control_speciesmap_add_dialog.dialog('close');
                };
            buttons[translatedStrings.Yes] = _Yes;
            buttons[translatedStrings.No] = _No;
            // dialog will be set closeOnEscape false as we need to do tidy up after it closes.
            indiciaData.control_speciesmap_add_dialog = $('<p>' + translatedStrings.ConfirmAddText + '<br/><input class="add-sref" type="text" readonly="readonly" value=""><input type="hidden" value="" class="add-geom"></p>')
                .dialog({ title: translatedStrings.ConfirmAddTitle, autoOpen: false, buttons: buttons, closeOnEscape: false});
            // The close button just closes it, but we need to do tidy up after it closes.
            $('#imp-sref').closest('.ui-dialog').find('.ui-dialog-titlebar-close').remove();
        },
        build_move_dialog1 = function () {
            var buttons = {}, // buttons are language specific
                _Yes = function () {
                    var div = $(indiciaData.control_speciesmap_opts.mapDiv)[0];
                    indiciaData.control_speciesmap_move_dialog1.dialog('close');
                    indiciaData.control_speciesmap_selectFeatureControl.deactivate();
                    // deacivating the control still leaves the selected feature highlighted.
                    div.map.editLayer.clickControl.activate(); // to allow user to select new position.
                    $('#' + indiciaData.control_speciesmap_opts.messageId).empty().append(indiciaData.control_speciesmap_translatedStrings.MoveMessage2);
                    $('#' + indiciaData.control_speciesmap_opts.cancelButtonId).show();
                },
                _No = function () {
                    indiciaData.control_speciesmap_selectFeatureControl.unselectAll();
                    indiciaData.control_speciesmap_move_dialog1.dialog('close');
                };
            buttons[translatedStrings.Yes] = _Yes;
            buttons[translatedStrings.No] = _No;
            // when we come out of the dialog we need to do stuff, whether yes or no, so can't let user just close the dialog.
            // Disable closeOnEscape and remove close icon
            indiciaData.control_speciesmap_move_dialog1 = $('<p class="move-dialog1"></p>')
                .dialog({ title: translatedStrings.ConfirmMove1Title, autoOpen: false, buttons: buttons, closeOnEscape: false});
            $('.move-dialog1').closest('.ui-dialog').find('.ui-dialog-titlebar-close').remove();
        },
        build_move_dialog2 = function () {
            var buttons = {}, // buttons are language specific
                _Yes = function () {
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
                    indiciaData.control_speciesmap_move_dialog2.dialog('close');
                    block.find('[name^=\:entered_ref]').val($('#imp-sref').val());
                    block.find('[name^=\:geom]').val($('#imp-geom').val());
                    $('#' + indiciaData.control_speciesmap_opts.messageId).empty().append(indiciaData.control_speciesmap_translatedStrings.MoveMessage1);
                    $('#' + indiciaData.control_speciesmap_opts.cancelButtonId).hide();
                    $('.move-dialog1,.move-dialog2-text').empty();
                    indiciaData.control_speciesmap_existing_feature = null;
                    indiciaData.control_speciesmap_new_feature = null;
                },
                _No = function () {
                    var div = $(indiciaData.control_speciesmap_opts.mapDiv)[0];
                    div.map.editLayer.destroyFeatures();
                    $('#imp-sref,#imp-geom').val('');
                    indiciaData.control_speciesmap_move_dialog2.dialog('close');
                };
            buttons[translatedStrings.Yes] = _Yes;
            buttons[translatedStrings.No] = _No;
            // when we come out of the dialog we need to do stuff, whether yes or no, so can't let user just close the dialog.
            // Disable closeOnEscape and remove close icon
            indiciaData.control_speciesmap_move_dialog2 = $('<p class="move-dialog2"><span class="move-dialog2-text"></span><br/><input class="move-sref" type="text" readonly="readonly" value=""><input type="hidden" value="" class="move-geom" ></p>')
                .dialog({ title: translatedStrings.ConfirmMove2Title, autoOpen: false, buttons: buttons, closeOnEscape: false});
            $('.move-dialog2').closest('.ui-dialog').find('.ui-dialog-titlebar-close').remove();
        },
        build_delete_dialog = function () {
            var buttons = {}, // buttons are language specific
                _Yes = function () {
                    var block = $('#scm-' + indiciaData.control_speciesmap_existing_feature.attributes.subSampleIndex + '-block');
                    indiciaData.control_speciesmap_delete_dialog.dialog('close');
                    // If the indicia sample id for the grid already exists, then have to flag as deleted, otherwise just wipe it.
                    if (block.filter('added').length === 0) {
                        block.find('[name$=\:sample\:deleted]').val('t').removeAttr('disabled');
                        block.hide();
                    } else {
                        block.remove();
                    }
                    indiciaData.control_speciesmap_selectFeatureControl.unselectAll();
                    $('[name$=\:sampleIDX]').filter('[value=' + indiciaData.control_speciesmap_existing_feature.attributes.subSampleIndex + ']').closest('tr').not('.scClonableRow').remove();
                    indiciaData.SubSampleLayer.removeFeatures([indiciaData.control_speciesmap_existing_feature]);
                    fillInMainSref();
                },
                _No = function () {
                    indiciaData.control_speciesmap_selectFeatureControl.unselectAll();
                    indiciaData.control_speciesmap_delete_dialog.dialog('close');
                };
            buttons[translatedStrings.Yes] = _Yes;
            buttons[translatedStrings.No] = _No;
            // when we come out of the dialog we need to do stuff, whether yes or no, so can't let user just close the dialog.
            // Disable closeOnEscape and remove close icon
            indiciaData.control_speciesmap_delete_dialog = $('<p class="delete-dialog"></p>')
                .dialog({ title: translatedStrings.ConfirmDeleteTitle, autoOpen: false, buttons: buttons, closeOnEscape: false});
            $('.delete-dialog').closest('.ui-dialog').find('.ui-dialog-titlebar-close').remove();
        },
        defaults = {mapDiv: '#map',
            buttonsId:      'speciesmap_controls',
            addButtonId:    'speciesmap_addbutton_control',
            modButtonId:    'speciesmap_modbutton_control',
            moveButtonId:   'speciesmap_movebutton_control',
            delButtonId:    'speciesmap_delbutton_control',
            cancelButtonId: 'speciesmap_cancelbutton_control',
            finishButtonId: 'speciesmap_finishbutton_control',
            messageId:      'speciesmap_controls_messages',
            messageClasses: 'page-notice ui-state-highlight ui-corner-all',
            featureLabel:   'Grid: ${sRef}\nSpecies: ${count}'};
    // Extend our default options with those provided, basing this on an empty object
    // so the defaults don't get changed.
    var opts = $.extend({}, defaults, options);
    indiciaData.control_speciesmap_opts = opts;
    indiciaData.control_speciesmap_translatedStrings = translatedStrings;
    var container = $('<div id="' + opts.buttonsId + '">').insertBefore(opts.mapDiv);
    $('<button id="' + opts.addButtonId + '" class="indicia-button" type="button">' + translatedStrings.AddLabel + '</button>').click(control_speciesmap_addbutton).appendTo(container);
    $('<button id="' + opts.modButtonId + '" class="indicia-button" type="button">' + translatedStrings.ModifyLabel + '</button>').click(control_speciesmap_modifybutton).appendTo(container);
    $('<button id="' + opts.moveButtonId + '" class="indicia-button" type="button">' + translatedStrings.MoveLabel + '</button>').click(control_speciesmap_movebutton).appendTo(container);
    $('<button id="' + opts.delButtonId + '" class="indicia-button" type="button">' + translatedStrings.DeleteLabel + '</button>').click(control_speciesmap_deletebutton).appendTo(container);
    $('<button id="' + opts.cancelButtonId + '" class="indicia-button" type="button">' + translatedStrings.CancelLabel + '</button>').click(control_speciesmap_cancelbutton).appendTo(container).hide();
    $('<button id="' + opts.finishButtonId + '" class="indicia-button" type="button">' + translatedStrings.FinishLabel + '</button>').click(control_speciesmap_finishbutton).appendTo(container).hide();
    $('<div id="' + opts.messageId + '" class="' + opts.messageClasses + '">' + translatedStrings.InitMessage + '</div>').appendTo(container);
    build_add_dialog1();
    build_move_dialog1();
    build_move_dialog2();
    build_delete_dialog();
    indiciaData.control_speciesmap_mode = 'Off';

    // We are assuming that this the species map control is invoked after the 
    mapInitialisationHooks.push(function (div) {
        if ('#' + div.id === opts.mapDiv) {
            var defaultStyle = $.extend(true, {}, div.map.editLayer.style),
                selectStyle = {fillColor: 'Blue', fillOpacity: 0.3, strokeColor: 'Blue', strokeWidth: 2};
            defaultStyle.label = indiciaData.control_speciesmap_opts.featureLabel;
            indiciaData.SubSampleLayer = new OpenLayers.Layer.Vector('Subsample Points', {displayInLayerSwitcher: false,
                 styleMap: new OpenLayers.StyleMap({'default': new OpenLayers.Style(defaultStyle), 'select': new OpenLayers.Style(selectStyle)})});
            // note select inherits the label from default
            div.map.addLayer(indiciaData.SubSampleLayer);
            indiciaData.control_speciesmap_selectFeatureControl = new OpenLayers.Control.SelectFeature(indiciaData.SubSampleLayer);
            div.map.addControl(indiciaData.control_speciesmap_selectFeatureControl);
            indiciaData.control_speciesmap_selectFeatureControl.deactivate();
            div.map.editLayer.clickControl.deactivate();
            indiciaData.SubSampleLayer.events.on({featureselected: _featureSelected});
            div.map.editLayer.events.on({featureadded: _featureAdded});
            // now add existing features.
            $('.scm-block').each(function (idx, block) {
                var id = $(block).attr('id').split('-'),
                    parser = new OpenLayers.Format.WKT(),
                    feature,
                    count;
                feature = parser.read($(block).find('[name$=sample\:geom]').val()); //style is null
                // TODO should convert from Indicia internal projection to map projection
                feature.attributes.subSampleIndex = id[1];
                feature.attributes.sRef = $(block).find('[name$=sample\:entered_sref]').val();
                count = $('[name$=\:sampleIDX]').filter('[value=' + feature.attributes.subSampleIndex + ']').length;
                feature.attributes.count = count;
                indiciaData.SubSampleLayer.addFeatures([feature]);
            });
            if (indiciaData.SubSampleLayer.features.length > 0) {
                indiciaData.SubSampleLayer.map.zoomToExtent(indiciaData.SubSampleLayer.getDataExtent());
            }
            hook_species_checklist_delete_row = function (data) {
                var feature = (indiciaData.control_speciesmap_mode === 'Add' ? indiciaData.control_speciesmap_new_feature : indiciaData.control_speciesmap_existing_feature);
                var count = $('[name$=\:sampleIDX]').filter('[value=' + feature.attributes.subSampleIndex + ']').length;
                feature.attributes.count = count;
                indiciaData.SubSampleLayer.refresh();
            };
            hook_species_checklist_new_row.push(hook_species_checklist_delete_row);
        }
    });
}