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
 * @package    Client
 * @subpackage PrebuiltForms
 * @author    Indicia Team
 * @license    http://www.gnu.org/licenses/gpl.html GPL 3.0
 * @link     http://code.google.com/p/indicia/
 */

/**
 * Prebuilt Indicia data entry form.
 * NB has Drupal specific code. Relies on presence of IForm loctools and IForm Proxy.
 * 
 * @package    Client
 * @subpackage PrebuiltForms
 */

require_once('dynamic_sample_occurrence.php');

class iform_mnhnl_dynamic_1 extends iform_dynamic_sample_occurrence {

  /** 
   * Return the form metadata.
   * @return string The definition of the form.
   */
  public static function get_mnhnl_dynamic_1_definition() {
    return array(
      'title'=>'MNHNL Dynamic 1 - dynamically generated data entry form',
      'category' => 'MNHNL forms',
      'helpLink'=>'http://code.google.com/p/indicia/wiki/TutorialDynamicForm',
      'description'=>'Derived from the Dynamic Sample Occurrence Form with custom headers and footers.'
    );
  }

  /**
   * Get the list of parameters for this form.
   * @return array List of parameters that this form requires.
   */
  public static function get_parameters() {    
    $retVal = array_merge(
      parent::get_parameters(),
      array(
        array(
          'name'=>'headerAndFooter',
          'caption'=>'Use Header and Footer',
          'description'=>'Include MNHNL header and footer html.',
          'type'=>'boolean',
          'group' => 'User Interface',
          'default' => false,
          'required' => false
        ),
      )
    );
    return $retVal;
 }
  
  protected static function get_form_html($args, $auth, $attributes) {
    if($args['includeLocTools'] && function_exists('iform_loctools_listlocations')){
  		$squares = iform_loctools_listlocations($node);
  		if($squares != "all" && count($squares)==0)
  			return lang::get('Error: You do not have any squares allocated to you. Please contact your manager.');
  	}
    $r = call_user_func(array(self::$called_class, 'getHeaderHTML'), $args);
    $r .= parent::get_form_html($args, $auth, $attributes);
    $r .= call_user_func(array(self::$called_class, 'getTrailerHTML'), $args);
    return $r;
  }
  
  protected static function getGrid($args, $node, $auth) {
    $r = call_user_func(array(self::$called_class, 'getHeaderHTML'), $args);
    $r .= parent::getGrid($args, $node, $auth);
    $r .= call_user_func(array(self::$called_class, 'getTrailerHTML'), $args);
    return $r;  
  }
  
  protected static function getHeaderHTML($args) {
    $base = base_path();
    if(substr($base, -1)!='/') $base.='/';
    return (isset($args['headerAndFooter']) && $args['headerAndFooter'] ?
      '<div id="iform-header">
        <div id="iform-logo-left"><a href="http://www.environnement.public.lu" target="_blank"><img border="0" class="government-logo" alt="'.lang::get('Gouvernement').'" src="'.$base.'sites/all/files/gouv.png"></a></div>
        <div id="iform-logo-right"><a href="http://www.crpgl.lu" target="_blank"><img border="0" class="gabriel-lippmann-logo" alt="'.lang::get('Gabriel Lippmann').'" src="'.$base.drupal_get_path('module', 'iform').'/client_helpers/prebuilt_forms/images/mnhnl-gabriel-lippmann-logo.jpg"></a></div>
        </div>' : '');
  }

  protected static function getTrailerHTML($args) {
    return (isset($args['headerAndFooter']) && $args['headerAndFooter'] ?
      '<p id="iform-trailer">'.lang::get('LANG_Trailer_Text').'</p>' : '');
  }
  
}