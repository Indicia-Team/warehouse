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

/**
 * List of methods that can be used for a prebuilt form control generation.
 * @package Client
 * @subpackage PrebuiltForms.
 * @param array $attributes
 * @param array $args
 * @param array $defAttrOptions
 * @param array $outerFilter
 * @param array $blockOptions Associative array of control names that have non-default options. Each entry
 * is keyed by the control name and has an array of the options and values to override.
 */

function get_attribute_html($attributes, $args, $defAttrOptions, $outerFilter=null, $blockOptions=null) {
  $lastOuterBlock='';
  $lastInnerBlock='';
  $r = '';
  foreach ($attributes as $attribute) {
    // Apply filter to only output 1 block at a time. Also hide controls that have already been handled.
    if (($outerFilter===null || strcasecmp($outerFilter,$attribute['outer_structure_block'])==0) && !isset($attribute['handled'])) {
      if (empty($outerFilter) && $lastOuterBlock!=$attribute['outer_structure_block']) {
        if (!empty($lastInnerBlock)) {
          $r .= '</fieldset>';
        }
        if (!empty($lastOuterBlock)) {
          $r .= '</fieldset>';
        }
        if (!empty($attribute['outer_structure_block']))
          $r .= '<fieldset id="'.get_fieldset_id($attribute['outer_structure_block']).
              '"><legend>'.lang::get($attribute['outer_structure_block']).'</legend>';
        if (!empty($attribute['inner_structure_block']))
          $r .= '<fieldset id="'.get_fieldset_id($attribute['outer_structure_block'], $attribute['inner_structure_block']).
              '"><legend>'.lang::get($attribute['inner_structure_block']).'</legend>';
      }
      elseif ($lastInnerBlock!=$attribute['inner_structure_block']) {
        if (!empty($lastInnerBlock)) {
          $r .= '</fieldset>';
        }
        if (!empty($attribute['inner_structure_block']))
          $r .= '<fieldset id="'.get_fieldset_id($lastOuterBlock, $attribute['inner_structure_block']).
              '"><legend>'.lang::get($attribute['inner_structure_block']).'</legend>';
      }
      $lastInnerBlock=$attribute['inner_structure_block'];
      $lastOuterBlock=$attribute['outer_structure_block'];
      $options = $defAttrOptions + get_attr_validation($attribute, $args);
      if (isset($blockOptions[$attribute['fieldname']])) {
        $options = array_merge($options, $blockOptions[$attribute['fieldname']]);
      }
      $r .= data_entry_helper::outputAttribute($attribute, $options);
      $attribute['handled']=true;
    }
  }
  if (!empty($lastInnerBlock)) {
    $r .= '</fieldset>';
  }
  if (!empty($lastOuterBlock) && strcasecmp($outerFilter,$lastOuterBlock)!==0) {
    $r .= '</fieldset>';
  }
  return $r;
}

/**
 * When attributes are fetched from the database the validation isn't passed through. In particular
 * validation isn't defined at a website/survey level yet, so validation may be specific to this form.
 * This allows the validation rules to be defined by a $args entry.
 */
function get_attr_validation($attribute, $args) {
  $retVal = array();
  if (!empty($args['attributeValidation'])) {
    $rules = array();
    $argRules = explode(';', $args['attributeValidation']);
    foreach($argRules as $rule){
      $rules[] = explode(',', $rule);
    }
    foreach($rules as $rule){
      if($attribute['fieldname'] == $rule[0] || substr($attribute['fieldname'], 0, strlen($rule[0])+1) == $rule[0].':') {
        // But only do if no parameter given as rule:param - eg min:-40, these have to be treated as attribute validation rules.
        // It is much easier to deal with these elsewhere.
        for($i=1; $i<count($rule); $i++)
          if(strpos($rule[$i], ':') === false) $retVal[] = $rule[$i];
      }
    }
    if(count($retVal) > 0)
      return array('validation' => $retVal);
  }
  return $retVal;
}

/**
 * Function to build an id for a fieldset from the block nesting data. Giving them a unique id helps if 
 * you want to do interesting things with JavaScript for example.
 */
function get_fieldset_id($outerBlock, $innerBlock='') {
  $parts = array('fieldset');
  if (!empty($outerBlock)) $parts[]=$outerBlock;
  if (!empty($innerBlock)) $parts[]=$innerBlock;
  $r = implode('-', $parts);
  $r = substr($r, 0, 30);
  // Make it lowercase and no whitespace
  $r = strtolower(preg_replace('/\s+/', '-', $r));
  return $r;
}