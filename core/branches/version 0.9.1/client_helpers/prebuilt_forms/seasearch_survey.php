<?php
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
 * @link  http://code.google.com/p/indicia/
 */

require_once('includes/dynamic.php');
require_once('dynamic_sample_occurrence.php');

class iform_seasearch_survey extends iform_dynamic_sample_occurrence {

  /**
   * @var array List of custom sample attributes in array keyed by caption. Helps to make this form
   * ID independent.
   */
  private static $attrsByCaption = array();

  /**
   * @var array List of custom sample attributes in array keyed by caption for the habitat level of the
   * hierarchy. Helps to make this form ID independent.
   */
  private static $habitatAttrsByCaption = array();

  /**
   * @var array Structured array containing data for habitat subsamples when loading existing
   * data.
   */
  private static $existingSubsampleData = array();

  /**
   * Return the form metadata.
   * @return string The definition of the form.
   */
  public static function get_seasearch_survey_definition() {
    return array(
        'title'=>'Seasearch survey form',
        'category' => 'Forms for specific surveying methods',
        'description'=>'Form for a seasearch survey. Requires the correct survey structure for Seasearch.'
    );
  }

  /**
   * Get the list of parameters for this form.
   * @return array List of parameters that this form requires.
   */
  public static function get_parameters() {
    $r = array_merge(
        parent::get_parameters(),
        array(
          array(
            'name'=>'habitat_sample_method_id',
            'caption'=>'Habitat sample method',
            'description'=>'Select the sample method type for the habitat data.',
            'required'=>true,
            'type' => 'select',
            'table'=>'termlists_term',
            'captionField'=>'term',
            'valueField'=>'id',
            'extraParams' => array('termlist_external_key'=>'indicia:sample_methods'),
            'group'=>'Attribute Setup'
          ),
          array(
            'name'=>'sacforp_attr_id',
            'caption'=>'SACFORP attribute',
            'description'=>'Custom attribute used for recording SACFORP for each record.',
            'type'=>'select',
            'table'=>'occurrence_attribute',
            'valueField'=>'id',
            'captionField'=>'caption',
            'group'=>'Attribute Setup'
          ),
        )
    );
    return $r;
  }

  protected static function get_form_html($args, $auth, $attributes) {
    // @todo Process the available data to load subsamples (habitats) and associated records correctly.
    // toggle the checkboxes to after the label to match the form.
    global $indicia_templates;
    drupal_add_library('system', 'ui.tooltip', true);
    $indicia_templates['check_or_radio_group_item'] =
        '<li><label for="{itemId}">{caption}</label><input type="{type}" name="{fieldname}" id="{itemId}" value="{value}"{class}{checked}{title} {disabled}/></li>';

    // Create an array of custom attributes keyed by caption for easy lookup later
    foreach($attributes as $attr)
      self::$attrsByCaption[strtolower($attr['caption'])] = $attr;

    // Build a list of the habitat-level attributes as well.
    $attributeOpts = array(
        'valuetable' => 'sample_attribute_value',
        'attrtable' => 'sample_attribute',
        'key' => 'sample_id',
        'fieldprefix' => 'smpAttr',
        'extraParams' => $auth['read'],
        'survey_id' => $args['survey_id'],
        'sample_method_id' => $args['habitat_sample_method_id']
    );
    $habitatAttributes = data_entry_helper::getAttributes($attributeOpts, false);
    foreach($habitatAttributes as $attr)
      self::$habitatAttrsByCaption[strtolower($attr['caption'])] = $attr;
    // load the habitat attribute values, if we have an existing sample
    if (!empty(self::$loadedSampleId)) {
      self::load_existing(self::$loadedSampleId, $auth);
    } else {
      data_entry_helper::$javascript .= "indiciaData.existingSubsampleData=[];\n";
    }
    // output some attribute info we can use for validation & business logic
    data_entry_helper::$javascript .= "indiciaData.depthMinLimitAttrNames = " . json_encode(array(
          self::$attrsByCaption['depth shallow bsl']['fieldname'],
          self::$attrsByCaption['depth shallow bcd']['fieldname']
        )) . ";\n";
    data_entry_helper::$javascript .= "indiciaData.depthMaxLimitAttrNames = " . json_encode(array(
            self::$attrsByCaption['depth deepest bsl']['fieldname'],
            self::$attrsByCaption['depth deepest bcd']['fieldname']
        )) . ";\n";
    data_entry_helper::$javascript .= "indiciaData.driftAttrId = " .
        self::$attrsByCaption['drift dive?']['attributeId'] . ";\n";
    data_entry_helper::$javascript .= "indiciaData.depthCDAttrName = '" .
        self::$attrsByCaption['tidal correction to chart datum']['fieldname'] . "';\n";
    data_entry_helper::$javascript .= "indiciaData.habitatMinDepthSLAttrId = " .
        self::$habitatAttrsByCaption['upper depth from sea level']['attributeId'] . ";\n";
    data_entry_helper::$javascript .= "indiciaData.habitatMaxDepthSLAttrId = " .
        self::$habitatAttrsByCaption['lower depth from sea level']['attributeId'] . ";\n";
    data_entry_helper::$javascript .= "indiciaData.habitatMinDepthCDAttrId = " .
        self::$habitatAttrsByCaption['upper depth from chart datum']['attributeId'] . ";\n";
    data_entry_helper::$javascript .= "indiciaData.habitatMaxDepthCDAttrId = " .
        self::$habitatAttrsByCaption['lower depth from chart datum']['attributeId'] . ";\n";

    return parent::get_form_html($args, $auth, $attributes);
  }

  /**
   * Additional functionality required to load habitat data for existing samples.
   * @param integer $sample_id ID of the parent sample being loaded
   * @param array $auth Authorisation tokens
   */
  protected static function load_existing($sample_id, $auth) {
    iform_load_helpers(array('report_helper'));
    $samples = report_helper::get_report_data(array(
        'dataSource' => 'library/samples/subsamples',
        'readAuth' => $auth['read'],
        'extraParams' => array('parent_sample_id'=>$sample_id)
    ));
    foreach($samples as $sample) {
      self::$existingSubsampleData['sample:'.$sample['id']] = array('sample_id'=>$sample['id'], 'comment' => $sample['comment'], 'values' => array());
    }
    $values = report_helper::get_report_data(array(
      'dataSource' => 'library/sample_attribute_values/subsample_attribute_values',
      'readAuth' => $auth['read'],
      'extraParams' => array('parent_sample_id'=>$sample_id)
    ));
    foreach ($values as $idx => $value) {
      self::$existingSubsampleData['sample:'.$value['sample_id']]['values']["$idx:$value[sample_attribute_id]"] =
          "$value[id]:$value[value]:$value[data_type]";
    }
    data_entry_helper::$javascript .= "indiciaData.existingSubsampleData=" .
        json_encode(array_values(self::$existingSubsampleData)) . ";\n";
  }

  protected static function get_control_addhabitat($auth, $args, $tabAlias, $options) {
    // set a minimum of 1 habitat
    $initialCount = count(self::$existingSubsampleData)===0 ? 1 : count(self::$existingSubsampleData);
    data_entry_helper::$javascript .= "indiciaData.initialHabitatCount = $initialCount;\n";
    return '<button id="add-habitat" type="button">Add another habitat</button>';
  }

  protected static function get_control_habitatblocks($auth, $args, $tabAlias, $options) {
    // build a template for the data entry controls for each habitat
    $template = '<legend title="' . lang::get('Each habitat is numbered. Make sure the description and quantitative data is ' .
        'entered in the correct columns and that you number your sketch or plan in the same way. Each written description ' .
        'should tally with the information entered on the columns and diagrams on the next page.') . '">Habitat habitatIdx</legend>';
    $template .= data_entry_helper::textarea(array(
        'fieldname' => 'sample:comment:habitatIdx',
        'label' => lang::get('DESCRIPTION (physical + community'),
        'tooltip' => lang::get('This should be a brief \\\'sketch in words\\\' to describe the main characteristics ' .
                'of each habitat and the dominant plant or animal communities. An example would be: "Gently shelving ' .
                'seabed consisting of large boulders up to 1m x 1m with patches of coarse sand collecting between them. Kelp ' .
                'forest on boulders with pink encrusting algae and red seaweeds beneath".')
    ));
    $biotopeCode = self::$habitatAttrsByCaption['biotope code'];
    $seabedType = self::$habitatAttrsByCaption['seabed type'];
    $seabedTypeOther = self::$habitatAttrsByCaption['other seabed type'];
    $communities = self::$habitatAttrsByCaption['communities'];
    $animalTurf = self::$habitatAttrsByCaption['animal turf'];
    $animalBed = self::$habitatAttrsByCaption['animal bed'];
    $sedimentTypes = self::$habitatAttrsByCaption['sediment types'];
    if (user_access('biotope codes')) {
      $template .= data_entry_helper::text_input(array(
        'fieldname' => "smpAttr:$biotopeCode[attributeId]::habitatIdx",
        'label' => lang::get('Biotope code'),
      ));
    }
    $template .= data_entry_helper::checkbox_group(array(
        'fieldname' => "smpAttr:$seabedType[attributeId]::habitatIdx",
        'label' => lang::get('Seabed type'),
        'table' => 'termlists_term',
        'valueField' => 'id',
        'captionField' => 'term',
        'extraParams' => $auth['read'] + array(
             'termlist_id'=>$seabedType['termlist_id'],
             'view'=>'cache'
        ),
        'afterControl' =>
            data_entry_helper::text_input(array(
              'label' => lang::get('other'),
              'labelClass' => 'auto',
              'fieldname' => "smpAttr:$seabedTypeOther[attributeId]::habitatIdx"
            )),
        'labelClass' => 'auto',
        'tooltip' => lang::get('Each habitat should only contain a limited number of physical types. Rock and boulders or '.
            'cobble and pebbles are fine but avoid identifying habitats containing very different physical characteristics, ' .
            'for instance rock and sand or wreckage and mud.')
    ));
    $template .= data_entry_helper::checkbox_group(array(
        'fieldname' => "smpAttr:$communities[attributeId]::habitatIdx",
        'label' => lang::get('Communities'),
        'table' => 'termlists_term',
        'valueField' => 'id',
        'captionField' => 'term',
        'extraParams' => $auth['read'] + array(
            'label' => lang::get('other'),
            'termlist_id'=>$communities['termlist_id'],
            'view'=>'cache',
            'orderby' => 'sort_order'
        ),
        'labelClass' => 'auto',
        'tooltip' => lang::get('Each habitat described should rarely have more than one dominant community. For instance ' .
            'if the main cover is kelp forest with pink encrusting algae and anemones on the rocks beneath only tick the '.
            'kelp forest box because this dominates.')
    ));
    $template .= '<div style="display: inline-block;">';
    $template .= data_entry_helper::text_input(array(
        'label' => lang::get('animal turf'),
        'fieldname' => "smpAttr:$animalTurf[attributeId]::habitatIdx",
        'labelClass' => 'auto',
        'tooltip' => lang::get('Write the main component in the box. This may, for example, be hydroids, jewel anemones or ' .
            'bryozoans but will not be mobile animals.')
    ));
    $template .= '</div>&nbsp; <div style="display: inline-block;">';
    $template .= data_entry_helper::text_input(array(
        'label' => lang::get('animal bed'),
        'fieldname' => "smpAttr:$animalBed[attributeId]::habitatIdx",
        'labelClass' => 'auto',
        'tooltip' => lang::get('Animal beds are where large numbers of a particular animal changes the composition of the ' .
            'seabed. Examples are the brittlestar beds, mussel beds and gravel sea cucumber beds.')
    ));
    $template .= '</div>';
    $template .= data_entry_helper::checkbox_group(array(
        'fieldname' => "smpAttr:$sedimentTypes[attributeId]::habitatIdx",
        'table' => 'termlists_term',
        'valueField' => 'id',
        'captionField' => 'term',
        'extraParams' => $auth['read'] + array(
              'label' => lang::get('other'),
              'termlist_id'=>$sedimentTypes['termlist_id'],
              'view'=>'cache'
          )
    ));
    // create the control output
    // add the template, wrapped in a hidden div. JS will be used to clone it as many times as is required.
    $r = "<div style=\"display: none;\"><fieldset id=\"habitat-block-template\">\n$template\n</fieldset></div>\n";
    $r .= '<input type="hidden" id="habitat-count" name="habitat-count" />';
    $r .= "<div id=\"habitat-blocks\"></div>\n";
    return $r;
  }

  protected static function get_control_quantitativedataleft($auth, $args, $tabAlias, $options) {
    $depthLimitAttrs = array(
        'upper depth from sea level'=>'Upper (from sea level) (i.e. minimum)',
        'lower depth from sea level'=>'Lower (from sea level) (i.e. maximum)',
        'upper depth from chart datum'=>'Upper (from chart datum)',
        'lower depth from chart datum'=>'Lower (from chart datum)'
    );
    $substratumAttrs = array(
        '% bedrock'=>'Bedrock type?:',
        '% boulders - very large > 1.0m'=>'Boulders - very large > 1.0m',
        '% boulders - large 0.5 - 1.0 m'=>'Boulders - large 0.5 - 1.0m',
        '% boulders - small 0.25 - 0.5m'=>'Boulders - small 0.25 - 0.5m',
        '% cobbles (fist - head size)'=>'Cobbles (fist - head size)',
        '% pebbles (50p - fist size)'=>'Pebbles (50p - fist size)',
        '% gravel - stone'=>'Gravel - stone',
        '% gravel - shell fragments'=>'Gravel - shell fragments',
        '% sand - coarse'=>'Sand - coarse',
        '% sand - medium'=>'Sand - medium',
        '% sand - fine'=>'Sand - fine',
        '% mud'=>'Mud',
        '% shells (empty - or as large pieces)'=>'Shells (empty - or as large pieces)',
        '% shells (living - eg mussels, limpets)'=>'Shells (living - eg mussels, limpets',
        '% artificial - metal'=>'Artificial - metal',
        '% atificial - concrete'=>'Artificial - concrete',
        '% artificial - wood'=>'Artificial - wood',
        '% other (state)'=>'Other (state)'
    );
    $r = '<p>' . lang::get('Enter the recorded depth range for each habitat.') . '</p>';
    $r .= '<table id="depth-limits" class="quantitative">';
    $r .= '<thead><tr></tr><tr><th class="units">m</th><th class="label">DEPTH LIMITS</th></tr></thead>';
    $r .= "<tbody>\n";
    // output 1 row per depth limit attribute.
    foreach ($depthLimitAttrs as $caption => $label) {
      $attr = self::$habitatAttrsByCaption[$caption];
      $label = lang::get($label);
      $r .= "<tr data-attrid=\"$attr[attributeId]\" data-class=\"{number:true}\"><td class=\"label\">$label</td></tr>\n";
    }
    $r .= '</tbody></table>';
    $r .= '<p>' . lang::get('Record the percentage in each category for each habitat. ' .
        'Make sure each column adds up to 100%.') . '</p>';
    $r .= '<table id="substratum" title="' . lang::get('Note the constituents of gravel - individual fragments may be of ' .
        'stone or shell. Corase sand has large clearly defined grains, usually a mixture of sizes. Fine sand runs through the ' .
        'fingers and the individual grains are difficult to pick out.') . '" class="quantitative">';
    $r .= '<thead><tr></tr><tr><th class="units">%</th><th class="label">Substratum</th></tr></thead>';
    $r .= "<tbody>\n";
    // output 1 row per substratum attribute.
    foreach ($substratumAttrs as $caption => $label) {
      $attr = self::$habitatAttrsByCaption[$caption];
      $label = lang::get($label);
      if ($caption==='% bedrock')
        $label .= '<input name="'.self::$attrsByCaption['bedrock type']['fieldname'].
            '" value="'.self::$attrsByCaption['bedrock type']['default'].'" type="text"/>';
      $r .= "<tr data-attrid=\"$attr[attributeId]\" data-class=\"{integer:true, min:0, max:100}\"><td class=\"label\">$label</td></tr>\n";
    }
    $r .= '</tbody>';
    $r .= '</table>';
    return $r;
  }

  protected static function get_control_quantitativedataright($auth, $args, $tabAlias, $options) {
    $rockFeatureAttrs = array(
        'rock - relief of habitat' => 'Relief of habitat (even - rugged)',
        'rock - texture' => 'Texture (smooth - pitted)',
        'rock - stability' => 'Stability (stable - mobile)',
        'rock - scour' => 'Scour (none - scoured)',
        'rock - silt' => 'Silt (none - silted)',
        'rock - fissures > 10 mm' => 'Fissures > 10mm (none - many)',
        'rock - crevices < 10 mm' => 'Crevices < 10mm (none - many)',
        'rock - boulder/cobble/pebble shape' => 'Boulder/cobble/pebble shape (rounded - angular)',
        'rock - sediment' => 'Sediment on rock? (tick if present)'
    );
    $sediment1FeatureAttrs = array(
      'sediment - mounds / casts' => 'Mounds / casts',
      'sediment - burrows / holes' => 'Burrows / holes',
      'sediment - waves (>10 cm high)' => 'Waves (>10 cm high)',
      'sediment - ripples (<10 cm high)' => 'Ripples (<10 cm high)',
      'sediment - subsurface course layer' => 'Subsurface course layer?',
      'sediment - subsurface anoxic (black) layer' => 'Subsurface anoxic (black) layer'
    );
    $sediment2FeatureAttrs = array(
      'sediment - firmness (firm - soft)' => 'Firmness (firm - sort)',
      'sediment - stability (stable - mobile)' => 'Stability (stable - mobile)',
      'sediment - sorting (well - poor)' => 'Sorting (well - poor)'
    );
    $tooltips = array(
        'rock - relief of habitat' => 'can range from 1 (very even - unbroken bedrock with uniform slope) ' .
            'to 5 (very rugged - highly broken slope with wide range of surfaces including fissures and gullies).',
        'rock - texture' => 'an indication of the smoothness of the rock type, from 1 (very smooth - a hard and well worn ' .
            'rock, or well rounded cobbles) to 5 (highly pitted - a highly pitted or bored rock such as chalk or limestone, ' .
            'or one with very jagged outlines).',
        'rock - stability' => 'relating to wave action, from 1 (very stable - bedrock or very large boulders which are never moved by wave ' .
            'action) to 5 (highly mobile - frequently turned pebbles, cobbles or small boulders, where colonisation is affected ' .
            'because of such movement).',
        'rock - scour' => 'an indication of scour by sand, from 1 (none - no scouring apparent) to 5 (highly scoured - base ' .
            'of rocks likely to be smooth and without colonisation).',
        'rock - silt' => 'the amount of silt settled on rocks, from 1 (none - very clean rock surfaces) to 5 ' .
            '(highly silted - thick layer of silt on all surfaces).',
        'rock - fissures > 10 mm' => 'Fissures and Crevices – indicates the presence of micro habitats for different sized species.',
        'rock - crevices < 10 mm' => 'Fissures and Crevices – indicates the presence of micro habitats for different sized species.',
        'rock - boulder/cobble/pebble shape' => 'Indication of stability and scouring, ranging from 1 (smooth rounded shapes) ' .
            'to 5 (sharp edged fragments such as flints or slates).',
        'rock - sediment' => 'tick where there is rock with a thin layer of sand or mud on top.',
        'sediment - mounds / casts' => 'often created by worms or crabs',
        'sediment - burrows / holes' => 'created by shells, crabs and worms',
        'sediment - subsurface course layer' => 'was there a firm layer of larger particles underneath a thin layer of sand?',
        'sediment - subsurface anoxic (black) layer' => 'remove the top few centimetres with your fingers to see if there is ' .
            'a layer of decomposed material beneath.',
        'sediment - firmness (firm - soft)' => 'the degree of softness or compactness of the sediment: 1 (very firm - difficult '.
            'to dig with fingers), 2 (fingers only in), 3 (hand in), 4 (can penetrate up to elbow), to 5 (very soft - whole arm in!).',
        'sediment - stability (stable - mobile)' => 'from 1 (highly stable - movement of sediment very unlikely) to  5 ' .
            '(highly mobile - sediment constantly being moved).',
        'sediment - sorting (well - poor)' => 'an indication of the uniformity of the particle size, from 1 (very well sorted ' .
            '- sediment composed of a single particle size) to 5 (very poorly sorted - sediment with wide range of particle sizes).'
    );
    $r = '<table id="features" class="quantitative">';
    $r .= '<thead><tr></tr><tr><th class="units">1 - 5</th><th class="label">FEATURES - ROCK (all categories)</th></tr></thead>';
    $r .= "<tbody>\n";
    $idx=0;
    // output 1 row per rock feature attribute.
    foreach ($rockFeatureAttrs as $caption => $label) {
      $attr = self::$habitatAttrsByCaption[$caption];
      $label = lang::get($label);
      // rock sediment is a special case for checkboxes
      $class = $attr['data_type'] === 'B' ? 'class="checkboxes" ' : 'data-class="{integer:true, min:1, max:5}"';
      $title = isset($tooltips[$caption]) ? ' title="' . lang::get($tooltips[$caption]) . '"' : '';
      $r .= "<tr id=\"rock-row-$idx\" {$class}{$title}data-attrid=\"$attr[attributeId]\"><td class=\"label\">$label</td></tr>\n";
      $idx++;
    }
    $r .= "</tbody>\n";
    $r .= '<thead><tr></tr><tr><th class="units">Tick</th><th class="label">FEATURES - SEDIMENT (1)</th></tr></thead>';
    $r .= "<tbody class=\"checkboxes\">\n";
    // output 1 row per sediment feature attribute.
    foreach ($sediment1FeatureAttrs as $caption => $label) {
      $attr = self::$habitatAttrsByCaption[$caption];
      $label = lang::get($label);
      $class = $attr['data_type'] === 'B' ? 'class="checkboxes" ' : 'data-class="{integer:true, min:1, max:5}"';
      $title = isset($tooltips[$caption]) ? ' title="' . lang::get($tooltips[$caption]) . '"' : '';
      $r .= "<tr {$class}{$title}data-attrid=\"$attr[attributeId]\"><td class=\"label\">$label</td></tr>\n";
    }
    $r .= "</tbody>\n";
    $r .= '<thead><tr></tr><tr><th class="units">1 - 5</th><th class="label">FEATURES - SEDIMENT (2)</th></tr></thead>';
    $r .= "<tbody class=\"checkboxes\">\n";
    // output 1 row per sediment feature attribute.
    foreach ($sediment2FeatureAttrs as $caption => $label) {
      $attr = self::$habitatAttrsByCaption[$caption];
      $label = lang::get($label);
      $class = $attr['data_type'] === 'B' ? 'class="checkboxes" ' : 'data-class="{integer:true, min:1, max:5}"';
      $title = isset($tooltips[$caption]) ? ' title="' . lang::get($tooltips[$caption]) . '"' : '';
      $r .= "<tr {$class}{$title}data-attrid=\"$attr[attributeId]\"><td class=\"label\">$label</td></tr>\n";
    }
    $r .= "</tbody>\n";
    $r .= '</table>';
    return $r;
  }

  public static function get_control_position($auth, $args, $tabAlias, $options) {
    // @todo test
    // @todo draw drift on the map
    // @todo after validation failure, drift start and end don't reload, because they weren't in the post at all -TEST
    // @toto saving does not set the entered_sref_system properly

  }

  public static function get_submission($values, $args) {
    $values['habitat-count']=2;
    $habitatSamples = array();
    for ($i=1; $i<=$values['habitat-count']; $i++)
      $habitatSamples["habitat$i"] = array();
    foreach ($values as $key=>$value) {
      if (substr_count($key, ':')===3) {
        $parts = explode(':', $key, 4);
        if ($parts[0]==='smpAttr') {
          // If the last part of a habitat field is not a number, this is just a dummy field used
          // when cloning habitat controls. We also skip empty new attribute values.
          if (preg_match('/^\d+$/', $parts[3]) && (!empty($parts[2]) || !empty($value))) {
            // habitat number is the last part of the attribute field name
            $id = array_pop($parts);
            // remove empty stuff from the attribute name (e.g. an unused space for the existing value ID, if a new attribute value).
            while (empty($parts[count($parts)-1]))
              array_pop($parts);
            $fieldname = implode(':', $parts);
            $habitatSamples["habitat$id"][$fieldname] = $value;
          }
          unset($values[$key]);
        }
      }
      $parts = explode(':', $key);
      // For the group_name, the autocomplete functionality needs to be removed from the submission.
      // So copy the edit box into the underlying field which gets posted.
      if (array_pop($parts)==='group_name')
        $values[implode(':', $parts)] = $value;
    }
    $buddyPairSubmission = submission_builder::wrap_with_images($values, 'sample');
    unset($buddyPairSubmission['fields']['habitat-count']);
    // Get the list of records implied by the SACFOR data for each habitat. At this point we'll create 1 big list and split
    // it across the habitats later.
    $occurrences = data_entry_helper::wrap_species_checklist($values, true, array(), array());
    // now work out which habitat contains which occurrence
    $habitatOccurrences = array();
    foreach (array_keys($habitatSamples) as $habitatId)
      $habitatOccurrences[$habitatId] = array();
    foreach ($occurrences as $occurrence) {
      // take a copy of the fields with all habitat data
      $fields = array_merge($occurrence['model']['fields']);
      // @todo Remove hard coded field ID.
      $habitatFields = preg_grep('/occAttr:243(:\d+)?/', array_keys($fields));
      if (count($habitatFields))
        $habitatId = $fields[array_pop($habitatFields)]['value'];
      else
        // this case occurs when deleting an occurrence, as the habitat ID input field is disabled. Therefore
        // we need to revert to the original hidden sampleIdx field for the loaded record.
        $habitatId = $fields['sampleIDX']['value'] + 1; // zero indexed
      $habitatOccurrences["habitat$habitatId"][] = $occurrence;
    }
    // now create the submodel data for each habitat.
    $buddyPairSubmission['subModels'] = array();
    // copy the basic sample data into each set of habitat subsample values
    foreach ($habitatSamples as $habitatId => &$habitatSample) {
      $habitatIdx = str_replace('habitat', '', $habitatId);
      $habitatSample['website_id'] = $values['website_id'];
      $habitatSample['survey_id'] = $values['survey_id'];
      if (isset($_POST["habitat_sample_id:$habitatIdx"]))
        $habitatSample['sample:id'] = $_POST["habitat_sample_id:$habitatIdx"];
      $habitatSample['sample:date'] = $values['sample:date'];
      $habitatSample['sample:entered_sref'] = $values['sample:entered_sref'];
      $habitatSample['sample:entered_sref_system'] = $values['sample:entered_sref_system'];
      $habitatSample['sample:input_form'] = $values['sample:input_form'];
      if (isset($_POST["sample:comment:$habitatIdx"])) {
        $habitatSample['sample:comment'] = $_POST["sample:comment:$habitatIdx"];
        unset($buddyPairSubmission['fields']["comment:$habitatIdx"]);
      }
      $habitatSubmission = submission_builder::wrap($habitatSample, 'sample');
      $habitatSubmission['subModels'] = $habitatOccurrences[$habitatId];
      $buddyPairSubmission['subModels'][] = array(
        'fkId' => 'parent_id',
        'model' => $habitatSubmission
      );
    }
    return $buddyPairSubmission;
  }

  /**
   * Declare the list of permissions we've got set up to pass to the CMS' permissions code.
   * @param int $nid Node ID, not used
   * @param array $args Form parameters array, used to extract the defined permissions.
   * @return array List of distinct permissions.
   */
  public static function get_perms($nid, $args) {
    $perms = array('biotope codes');
    if (!empty($args['edit_permission']))
      $perms[] = $args['edit_permission'];
    if (!empty($args['ro_permission']))
      $perms[] = $args['ro_permission'];
    // scan for @permission=... in the form structure
    // @todo Refactor this into dynamic.php so that all types of dynamic form benefit
    $structure = data_entry_helper::explode_lines($args['structure']);
    $permissions = preg_grep('/^@((smp|occ|loc)Attr:\d+|)?permission=/', $structure);
    foreach ($permissions as $permission) {
      $parts = explode('=', $permission, 2);
      $perms[] = array_pop($parts);
    }
    return $perms;
  }

}