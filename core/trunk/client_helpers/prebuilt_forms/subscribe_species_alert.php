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

require_once('includes/map.php');

/**
 * Provides a form for subscribing to receive a notification when a certain species is recorded.
 * 
 * @package Client
 * @subpackage PrebuiltForms
 */
class iform_subscribe_species_alert {
  
  /** 
   * Return the form metadata. Note the title of this method includes the name of the form file. This ensures
   * that if inheritance is used in the forms, subclassed forms don't return their parent's form definition.
   * @return array The definition of the form.
   * @todo rename this method.
   */
  public static function get_subscribe_species_alert_definition() {
    return array(
      'title'=>'Subscribe to a species alert',
      'category' => 'Miscellaneous',
      'description'=>'Provides a simple form for picking a species and optional geographic filter to subscribe to receive an alert notification '.
          'when that species is recorded or verified.'
    );
  }
  
  /**
   * Get the list of parameters for this form.
   * @return array List of parameters that this form requires.
   */
  public static function get_parameters() {   
    return array_merge(
      iform_map_get_map_parameters(), 
      array(
        array(
          'fieldname'=>'list_id',
          'label'=>'Species list ',
          'helpText'=>'The species list that species can be selected from.',
          'type'=>'select',
          'table'=>'taxon_list',
          'valueField'=>'id',
          'captionField'=>'title',
          'required'=>false,
          'group'=>'Lookups',
          'siteSpecific'=>true
        ),
        array(
          'fieldname'=>'location_type_id',
          'label'=>'Location type',
          'helpText'=>'The location type available to filter against.',
          'type'=>'select',
          'table'=>'termlists_term',
          'valueField'=>'id',
          'captionField'=>'term',
          'required'=>false,
          'group'=>'Lookups',
          'siteSpecific'=>true
        ),
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
    $form = '<form action="#" method="POST" id="entry_form">';
    if ($_POST) {
      $auth = data_entry_helper::get_read_write_auth($args['website_id'], $args['password']);
      self::subscribe($args, $auth);
    } else {
      // don't bother with write auth for initial form load, as read auth is cached and faster
      $auth = array('read' => data_entry_helper::get_read_auth($args['website_id'], $args['password']));
    }
    // if not logged in, then ask for details to register against
    global $user;
    if (!hostsite_get_user_field('id') || !isset($user) || empty($user->mail) || !hostsite_get_user_field('last_name')) {
      $form .= "<fieldset><legend>".lang::get('Your details').":</legend>\n";
      $default = hostsite_get_user_field('first_name');
      $form .= data_entry_helper::text_input(array(
        'label'=>lang::get('First name'),
        'fieldname' => 'first_name',
        'validation' => array('required'),
        'default' => $default ? $default : '',
        'class' => 'control-width-4'
      ));
      $default = hostsite_get_user_field('last_name');
      $form .= data_entry_helper::text_input(array(
        'label'=>lang::get('Last name'),
        'fieldname' => 'surname',
        'validation' => array('required'),
        'default' => $default ? $default : '',
        'class' => 'control-width-4'
      ));
      $default = empty($user->mail) ? '' : $user->mail;
      $form .= data_entry_helper::text_input(array(
        'label'=>lang::get('Email'),
        'fieldname' => 'email',
        'validation' => array('required', 'email'),
        'default' => $default,
        'class' => 'control-width-4'
      ));
      $form .= "</fieldset>\n";
    } else {
      $form .= data_entry_helper::hidden_text(array(
        'fieldname' => 'first_name',
        'default' => hostsite_get_user_field('first_name')
      ));
      $form .= data_entry_helper::hidden_text(array(
        'fieldname' => 'surname',
        'default' => hostsite_get_user_field('last_name')
      ));
      $form .= data_entry_helper::hidden_text(array(
        'fieldname' => 'email',
        'default' => $user->mail
      ));
      $form .= data_entry_helper::hidden_text(array(
        'fieldname' => 'user_id',
        'default' => hostsite_get_user_field('indicia_user_id')
      ));
    }
    $form .= "<fieldset><legend>".lang::get('Alert criteria').":</legend>\n";
    $form .= data_entry_helper::species_autocomplete(array(
      'label' => lang::get('Alert species'),
      'helpText' => lang::get('Select the species you are interested in receiving alerts in relation to.'),
      'fieldname' => 'taxa_taxon_list_id',
      'cacheLookup' => true,
      'extraParams' => $auth['read'] + array('taxon_list_id' => $args['list_id']),
      'class' => 'control-width-4'
    ));
    $form .= data_entry_helper::location_select(array(
      'label' => lang::get('Select location'),
      'helpText' => lang::get('If you want to restrict the alerts to records within a certain boundary, select it here.'),
      'fieldname' => 'location_id',
      'id' => 'imp-location',
      'blankText' => lang::get('<Select boundary>'),
      'extraParams' => $auth['read'] + array('location_type_id' => $args['location_type_id'], 'orderby' => 'name'),
      'class' => 'control-width-4'
    ));
    $form .= data_entry_helper::checkbox(array(
      'label' => lang::get('Alert on initial entry'),
      'helpText' => lang::get('Tick this box if you want to receive a notification when the record is first input into the system.'),
      'fieldname' => 'alert_on_entry'
    ));
    $form .= data_entry_helper::checkbox(array(
      'label' => lang::get('Alert on verification'),
      'helpText' => lang::get('Tick this box if you want to receive a notification when the record has been verified.'),
      'fieldname' => 'alert_on_verify'
    ));
    $form .= "</fieldset>\n";
    $form .= '<input type="Submit" value="Subscribe" />';
    $form .= '</form>';
    data_entry_helper::enable_validation('entry_form');
    iform_load_helpers(array('map_helper'));
    $mapOptions = iform_map_get_map_options($args, $auth['read']);
    $map = map_helper::map_panel($mapOptions);
    global $indicia_templates;
    return str_replace(array('{col-1}', '{col-2}'), array($form, $map), $indicia_templates['two-col-50']);
  }
  
  private static function subscribe($args, $auth) {
    $url = data_entry_helper::$base_url . 'index.php/services/species_alerts/register?';
    $params = array(
      'auth_token' => $auth['write_tokens']['auth_token'],
      'nonce' => $auth['write_tokens']['nonce'],
      'first_name' => $_POST['first_name'],
      'surname' => $_POST['surname'],
      'email' => $_POST['email'],
      'website_id' => $args['website_id'],
      'alert_on_entry' => $_POST['alert_on_entry'] ? 't' : 'f',
      'alert_on_verify' => $_POST['alert_on_verify'] ? 't' : 'f'
    );
    if (!empty($_POST['location_id']))
      $params['location_id'] = $_POST['location_id'];
    if (!empty($_POST['user_id']))
      $params['user_id'] = $_POST['user_id'];
    // We've got a taxa_taxon_list_id in the post data. But, it is better to subscribe via a taxon
    // meaning ID, or even better, the external key.
    $taxon = data_entry_helper::get_population_data(array(
      'table' => 'taxa_taxon_list',
      'extraParams' => $auth['read'] + array('id' => $_POST['taxa_taxon_list_id'], 'view' => 'cache')
    ));
    if (count($taxon)!==1) 
      throw new exception('Unable to find unique taxon when attempting to subscribe');
    $taxon = $taxon[0];
    if (!empty($taxon['external_key']))
      $params['external_key'] = $taxon['external_key'];
    else
      $params['taxon_meaning_id'] = $taxon['taxon_meaning_id'];
    $url .= data_entry_helper::array_to_query_string($params, true);
    $result = data_entry_helper::http_post($url);
    if ($result['result']) 
      hostsite_show_message(lang::get('Your subscription has been saved.'));
    else {
      hostsite_show_message(lang::get('There was a problem saving your subscription.'));
      if (function_exists('watchdog')) {
        watchdog('iform', 'Species alert error on save: '.print_r($result, true));
      } 
    }
  }

}
