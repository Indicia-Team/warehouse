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
 * A term editor.
 * 
 * @package Client
 * @subpackage PrebuiltForms
 */
class iform_term {
  
  /** 
   * Return the form metadata. 
   * @return array The definition of the form.
   */
  public static function get_term_definition() {
    return array(
      'title'=>'Term editor',
      'category' => 'General Purpose Data Entry Forms',
      'description'=>'A simple page for editing terms in a warehouse termlist.'
    );
  }
  
  /**
   * Get the list of parameters for this form.
   * @return array List of parameters that this form requires.
   */
  public static function get_parameters() {   
    return array(
      array(
          'name'=>'termlist_id',
          'caption'=>'Term List',
          'description'=>'The term list being edited.',
          'type'=>'select',
          'table'=>'termlist',
          'captionField'=>'title',
          'valueField'=>'id',
          'siteSpecific'=>true,
          'group'=>'Terms',
          'required'=>true
      ),
      array(
          'name'=>'language_id',
          'caption'=>'Language',
          'description'=>'The language that terms are created in.',
          'type'=>'select',
          'table'=>'language',
          'captionField'=>'language',
          'valueField'=>'id',
          'siteSpecific'=>true,
          'group'=>'Terms',
          'required'=>true
      )
    );
  }
  
  /**
   * Return the generated form output.
   * @param array $args List of parameter values passed through to the form depending on how the form has been configured.
   * This array always contains a value for language.
   * @param object $node The Drupal node object.
   * @param array $response When this form is reloading after saving a submission, contains the response from the service call.
   * Note this does not apply when redirecting (in this case the details of the saved object are in the $_GET data).
   * @return Form HTML.
   */
  public static function get_form($args, $node, $response=null) {
    $reloadPath = self::get_reload_path();
    $auth = data_entry_helper::get_read_write_auth($args['website_id'], $args['password']);
    $r = "<form method=\"post\" id=\"entry_form\" action=\"$reloadPath\">\n";
    $r .= $auth['write'];
    data_entry_helper::$entity_to_load = array();
    if (!empty($_GET['termlists_term_id'])) {
      data_entry_helper::load_existing_record($auth['read'], 'termlists_term', $_GET['termlists_term_id']); 
      // map fields to their appropriate supermodels
      data_entry_helper::$entity_to_load['term:term'] = data_entry_helper::$entity_to_load['termlists_term:term'];
      data_entry_helper::$entity_to_load['term:id'] = data_entry_helper::$entity_to_load['termlists_term:term_id'];
      data_entry_helper::$entity_to_load['meaning:id'] = data_entry_helper::$entity_to_load['termlists_term:meaning_id'];
      if (function_exists('hostsite_set_page_title'))
        hostsite_set_page_title(lang::get('Edit {1}', data_entry_helper::$entity_to_load['term:term'])); 
    }
    $r .= data_entry_helper::hidden_text(array(
      'fieldname' => 'website_id',
      'default' => $args['website_id']
    ));
    $r .= data_entry_helper::hidden_text(array(
      'fieldname' => 'termlists_term:id'
    ));
    $r .= data_entry_helper::hidden_text(array(
      'fieldname' => 'termlists_term:termlist_id',
      'default' => $args['termlist_id']
    ));
    $r .= data_entry_helper::hidden_text(array(
      'fieldname' => 'termlists_term:preferred',
      'default' => 't'
    ));
    $r .= data_entry_helper::hidden_text(array(
      'fieldname' => 'term:id'      
    ));
    $r .= data_entry_helper::hidden_text(array(
      'fieldname' => 'term:language_id',
      'default' => $args['language_id']
    ));
    $r .= data_entry_helper::hidden_text(array(
      'fieldname' => 'meaning:id'      
    ));
    // request automatic JS validation
    data_entry_helper::enable_validation('entry_form');
    $r .= data_entry_helper::text_input(array(
      'label' => lang::get('Term'),
      'fieldname' => 'term:term',
      'helpText' => lang::get('Please provide the term'),
      'validation' => array('required'),
      'class' => 'control-width-5'
    ));
    $r .= "<input type=\"submit\" name=\"form-submit\" id=\"delete\" value=\"Delete\" />\n";
    $r .= "<input type=\"submit\" name=\"form-submit\" value=\"Save\" />\n";
    $r .= '<form>';
    self::set_breadcrumb($args);
    return $r;
  }
  
  /** 
   * If we know the page to return to, we can set the page breadcrumb.
   */
  protected static function set_breadcrumb($args) {
    if (!empty($args['redirect_on_success']) && function_exists('hostsite_set_breadcrumb')) {
      $breadcrumb = array('Terms' => $args['redirect_on_success']);
      hostsite_set_breadcrumb($breadcrumb);
    }
  }
  
  protected static function get_reload_path () {
    $reload = data_entry_helper::get_reload_link_parts();
    unset($reload['params']['termlists_terms_id']);
    $reloadPath = $reload['path'];
    if(count($reload['params'])) {
      // decode params prior to encoding to prevent double encoding.
      foreach ($reload['params'] as $key => $param)
        $reload['params'][$key] =urldecode($param);
      $reloadPath .= '?'.http_build_query($reload['params']);
    }
    return $reloadPath;
  }
  
  /**
   * Handles the construction of a submission array from a set of form values. 
   * @param array $values Associative array of form data values. 
   * @param array $args iform parameters. 
   * @return array Submission structure.
   */
  public static function get_submission($values, $args) {
    iform_load_helpers(array('submission_builder'));
    return submission_builder::build_submission($values, array(
      'model'=>'termlists_term',
      'superModels'=>array(
        'meaning'=>array('fk' => 'meaning_id'),
        'term'=>array('fk' => 'term_id')
      )
    ));
  }

}
