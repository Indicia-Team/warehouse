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
 */
 
/**
 * Retrieve the html for a block of attributes.
 * @param array $attributes Array of attributes as returned from a call to data_entry_helper::getAttributes.
 * @param array $args Form argument array.
 * @param array $ctrlOptions Array of default options to apply to every attribute control.
 * @param array $outerFilter Name of the outer block to get controls for. Leave null for all outer blocks.
 * @param array $attrSpecificOptions Associative array of control names that have non-default options. Each entry
 * is keyed by the control name and has an array of the options and values to override.
 * @param array $idPrefix Optional prefix to give to IDs (e.g. for fieldsets) to allow you to ensure they remain unique.
 */

function get_attribute_html(&$attributes, $args, $ctrlOptions, $outerFilter=null, 
    $attrSpecificOptions=null, $idPrefix='', $helperClass = 'data_entry_helper') {
  $lastOuterBlock='';
  $lastInnerBlock='';
  $r = '';
  foreach ($attributes as &$attribute) {
    if (in_array($attribute['id'],data_entry_helper::$handled_attributes))
      $attribute['handled']=1;
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
          $r .= '<fieldset id="'.get_fieldset_id($attribute['outer_structure_block'], $idPrefix).
              '"><legend>'.lang::get($attribute['outer_structure_block']).'</legend>';
        if (!empty($attribute['inner_structure_block']))
          $r .= '<fieldset id="'.get_fieldset_id($attribute['outer_structure_block'], $attribute['inner_structure_block'], $idPrefix).
              '"><legend>'.lang::get($attribute['inner_structure_block']).'</legend>';
      }
      elseif ($lastInnerBlock!=$attribute['inner_structure_block']) {
        if (!empty($lastInnerBlock)) {
          $r .= '</fieldset>';
        }
        if (!empty($attribute['inner_structure_block']))
          $r .= '<fieldset id="'.get_fieldset_id($lastOuterBlock, $attribute['inner_structure_block'], $idPrefix).
              '"><legend>'.lang::get($attribute['inner_structure_block']).'</legend>';
      }
      $lastInnerBlock=$attribute['inner_structure_block'];
      $lastOuterBlock=$attribute['outer_structure_block'];
      $options = $ctrlOptions + get_attr_validation($attribute, $args);
      // when getting the options, only use the first 2 parts of the fieldname as any further imply an existing record ID so would differ.
      $fieldNameParts=explode(':',$attribute['fieldname']);
      if (preg_match('/[a-z][a-z][a-z]Attr/', $fieldNameParts[count($fieldNameParts)-2]))
        $optionFieldName = $fieldNameParts[count($fieldNameParts)-2] . ':' . $fieldNameParts[count($fieldNameParts)-1];
      elseif (preg_match('/[a-za-za-z]Attr/', $fieldNameParts[count($fieldNameParts)-3]))
        $optionFieldName = $fieldNameParts[count($fieldNameParts)-3] . ':' . $fieldNameParts[count($fieldNameParts)-2];
      else 
        throw new exception('Option fieldname not found');
      if (isset($attrSpecificOptions[$optionFieldName])) {
        $options = array_merge($options, $attrSpecificOptions[$optionFieldName]);
      }
      $r .= call_user_func($helperClass . '::outputAttribute', $attribute, $options);
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
function get_fieldset_id($outerBlock, $innerBlock='', $idPrefix='') {
  $parts = array();
  if (!empty($idPrefix))
    $parts[] = $idPrefix;
  $parts[] = 'fieldset';  
  if (!empty($outerBlock)) 
    $parts[]=substr($outerBlock, 0, 20);
  if (!empty($innerBlock)) 
    $parts[]=substr($innerBlock, 0, 20);
  $r = implode('-', $parts);
  // Make it lowercase and no whitespace or other special chars
  $r = strtolower(preg_replace('/[\s\'()]+/', '-', $r));
  $r = strtolower(preg_replace('/[()]+/', '', $r));
  return $r;
}

/**
 * Finds the list of tab names that are going to be required by the custom attributes.
 * @param array $attributes List of attributes. Any attributes with no outer structure block are assigned
 * to a tab called Other Information.
 */
function get_attribute_tabs(&$attributes) {
  $r = array();
  foreach($attributes as &$attribute) {
    if (!isset($attribute['handled']) || $attribute['handled']!=true) {
      // Assign any ungrouped attributes to a block called Other Information 
      if (empty($attribute['outer_structure_block'])) 
        $attribute['outer_structure_block']='Other Information';
      if (!array_key_exists($attribute['outer_structure_block'], $r))
        // Create a tab for this structure block and mark it with [*] so the content goes in
        $r[$attribute['outer_structure_block']] = array("[*]");
    }
  }
  return $r;
}

/** 
 * Find the attribute called CMS User ID, or return false.
 * @param array $attributes List of attributes returned by a call to data_entry_helper::getAttributes.
 * @param bool $unset If true (default) then the CMS User ID attributes are removed from the array.
 * @return array Single attribute definition, or false if none found.
 */
function extract_cms_user_attr(&$attributes, $unset=true) {
  $found=false;
  foreach($attributes as $idx => $attr) {
    if (strcasecmp($attr['caption'], 'CMS User ID')===0) {
      // found will pick up just the first one
      if (!$found)
        $found=$attr;
      if ($unset)
        unset($attributes[$idx]);
      else
        // don't bother looking further if not unsetting them all
        break;
    }
  }
  return $found;
}

/**
 * Returns a list of hidden inputs which are extracted from the form attributes which can be extracted
 * from the user's profile information in Drupal. The attributes which are used are marked as handled
 * so they don't need to be output elsewhere on the form.
 * @param array $attributes List of form attributes.
 * @param array $args List of form arguments. Can include values called:
 *   copyFromProfile - boolean indicating if values should be copied from the profile when the names match
 *   nameShow - boolean, if true then name values should be displayed rather than hidden
 *   emailShow - boolean, if true then email values should be displayed rather than hidden.
 * @param boolean $exists Pass true for an existing record. If the record exists, then the attributes
 *   are marked as handled but are not output to avoid overwriting metadata about the original creator of the record.
 * @param array $readAuth Read authorisation tokens.
 * @return string HTML for the hidden inputs.
 */
function get_user_profile_hidden_inputs(&$attributes, $args, $exists, $readAuth) {
  // This is Drupal specific code, so degrade gracefully.
  if (!function_exists('profile_load_all_profile'))
    return '';
  $hiddens = '';
  global $user;
  $logged_in = $user->uid > 0;
  $version6 = (substr(VERSION, 0, 1) == '6');
  // If logged in, output some hidden data about the user
  if (isset($args['copyFromProfile']) && $args['copyFromProfile']==true) {  
    if($version6) {
      // In version 6 the profile module holds user setttings.
      profile_load_all_profile($user);
    }
    else {
      // In version 7, the field module holds user settings.
      $user = user_load($user->uid);
    }
  }  
  foreach($attributes as &$attribute) {
    // Constuct the name of the user property (which varies between versions) to match against the attribute caption.
    $attrPropName = $version6 ? 'profile_' : 'field_';
    $attrPropName .= strtolower(str_replace(' ', '_', $attribute['caption']));
    
    if (isset($args['copyFromProfile']) && $args['copyFromProfile'] == true && isset($user->$attrPropName)) {
      // Obtain the property value which is stored differently between versions.
      if($version6) {
        $attrPropValue = $user->$attrPropName;
      }
      else {
        $attrPropValues = field_get_items('user', $user, $attrPropName);
        $attrPropValue = isset($attrPropValues[0]['safe value']) ? $attrPropValues[0]['safe value'] : $attrPropValues[0]['value'];
      }
      
      // lookups need to be translated to the termlist_term_id, unless they are already IDs
      if ($attribute['data_type'] === 'L' && !preg_match('/^[\d]+$/', $attrPropValue)) {
        $terms = data_entry_helper::get_population_data(array(
          'table' => 'termlists_term',
          'extraParams' => $readAuth + array('termlist_id' => $attribute['termlist_id'], 'term' => $attrPropValue)
        ));
        $value = (count($terms) > 0) ? $terms[0]['id'] : '';
      } 
      else {
        $value = $attrPropValue;
      }
      
      if (isset($args['nameShow']) && $args['nameShow'] == true) {
        $attribute['default'] = $value;
      }
      else {
        // profile attributes are not displayed as the user is logged in
        $attribute['handled']=true;
        $attribute['value'] = $value;
      }
    }
    elseif (strcasecmp($attribute['caption'], 'cms user id')==0) {
      if ($logged_in) $attribute['value'] = $user->uid;
      $attribute['handled']=true; // user id attribute is never displayed
    }
    elseif (strcasecmp($attribute['caption'], 'cms username')==0) {
      if ($logged_in) $attribute['value'] = $user->name;
      $attribute['handled']=true; // username attribute is never displayed
    }
    elseif (strcasecmp($attribute['caption'], 'email')==0) {
      if ($logged_in) {
        if (!isset($args['emailShow']) || $args['emailShow'] != true)
        {// email attribute is not displayed
          $attribute['value'] = $user->mail;
          $attribute['handled']=true; 
        }
        else
          $attribute['default'] = $user->mail;
      }
    }
    elseif ((strcasecmp($attribute['caption'], 'first name')==0 ||
        strcasecmp($attribute['caption'], 'last name')==0 ||
        strcasecmp($attribute['caption'], 'surname')==0) && $logged_in) {
      if (!isset($args['nameShow']) || $args['nameShow'] != true) {  
        // name attributes are not displayed because we have the users login
        $attribute['handled']=true;
      }
    }
    // If we have a value for one of the user login attributes then we need to output this value. BUT, for existing data
    // we must not overwrite the user who created the record. Note that we don't do this at the beginning of the method
    // as we still wanted to mark the attributes as handled.
    if (isset($attribute['value']) && !$exists) {
      $hiddens .= '<input type="hidden" name="'.$attribute['fieldname'].'" value="'.$attribute['value'].'" />'."\n";
    }
  }
  return $hiddens;
}

/**
 * Variant on the profile modules profile_load_profile, that also gets empty profile values. 
 */
function profile_load_all_profile(&$user) {
  // don't do anything unless in Drupal, with the profile module enabled, and the user logged in.
  if ($user->uid > 0 && function_exists('profile_load_profile')) {
    $result = db_query('SELECT f.name, f.type, v.value FROM {profile_fields} f LEFT JOIN {profile_values} v ON f.fid = v.fid AND uid = %d', $user->uid);
    while ($field = db_fetch_object($result)) {
      if (empty($user->{$field->name})) {
        if (empty($field->value)) 
          $user->{$field->name} = '';
        else
          $user->{$field->name} = _profile_field_serialize($field->type) ? unserialize($field->value) : $field->value;
      }
    }
  }
}