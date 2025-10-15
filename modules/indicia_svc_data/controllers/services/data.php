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
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link https://github.com/Indicia-Team/warehouse
 */

/**
 * Controller class for data services request.
 */
class Data_Controller extends Data_Service_Base_Controller {

  protected $model;
  protected $entity;
  protected $viewname;
  protected $foreign_keys;
  protected $view_columns;
  protected $db;
  // following are used to store the response till all finished, so we don't output anything
  // if there is an error
  protected $response;

  // Read/Write Access to entities: there are several options:
  // 1) Standard: Restricted read and write access dependant on website id.
  //    There is a public function with the name of the entity in this file, and the entity appears in $allow_updates.
  //    The view list_<plural_entity> must exist and have a website_id column on it. If the website_id is null then
  //    the record may be accessed by all websites.
  // 2) Standard Read Only: Restricted read access dependant on website id. No write Access.
  //    There is a public function with the name of the entity in this file, and the entity does not appear in $allow_updates.
  //    The view list_<plural_entity> must exist and have a website_id column on it. If the website_id is null then
  //    the record may be accessed by all websites.
  // 3) Unrestricted Access: All records may be read and updated.
  //    There is a public function with the name of the entity in this file, and the entity appears in $allow_updates.
  //    Either the view list_<plural_entity> exists and has a website_id column on it which is forced to null, OR
  //    the entity appears in $allow_full_access.
  // 4) Unrestricted Read Only: All records may be read. No write Access.
  //    There is a public function with the name of the entity in this file, and the entity does not appear in $allow_updates.
  //    Either the view list_<plural_entity> exists and has a website_id column on it which is forced to null, OR
  //    the entity appears in $allow_full_access.
  // 5) Unrestricted Read, Restricted Write:
  //    Not currently implemented.
  // 6) No Access:
  //    There is no public function with the name of the entity in this file
  //
  // default to no updates allowed - must explicity allow updates.
  protected $allow_updates = [
    'attribute_sets_survey',
    'attribute_sets_taxon_restriction',
    'comment_quick_reply_page_auth',
    'custom_verification_ruleset',
    'determination',
    'dna_occurrence',
    'filter',
    'filters_user',
    'group',
    'group_page',
    'groups_location',
    'groups_user',
    'group_invitation',
    'group_relation',
    'location',
    'location_attribute_value',
    'location_comment',
    'location_medium',
    'notification',
    'occurrence',
    'occurrence_attribute',
    'occurrence_attribute_value',
    'occurrence_attributes_taxa_taxon_list_attribute',
    'occurrence_comment',
    'occurrence_medium',
    'person_attribute_value',
    'person',
    'sample',
    'sample_attribute_value',
    'sample_comment',
    'sample_medium',
    'species_alert',
    'survey',
    'survey_attribute',
    'survey_attribute_value',
    'survey_comment',
    'survey_medium',
    'taxa_taxon_list',
    'taxa_taxon_list_attribute',
    'taxa_taxon_list_attribute_value',
    'taxon_lists_taxa_taxon_list_attribute',
    'taxon_rank',
    'taxon_relation',
    'taxon_group',
    'termlists_term',
    'termlists_term_attribute_value',
    'user',
    'user_trust',
    'users_website',
  ];

  // Standard functionality is to use the list_<plural_entity> views to provide a mapping between entity id
  // and website_id, so that we can work out whether access to a particular record is allowed.
  // There is a potential issues with this: We may want everyone to have complete access to a particular dataset
  // So if we wish total access to a given dataset, the entity must appear in the following list.
  protected $allow_full_access = [
    'attribute_sets_taxon_restriction',
    'filter',
    'filters_user',
    'comment_quick_reply_page_auth',
    'species_alert',
    'taxa_taxon_list',
    'taxa_taxon_list_attribute',
    'taxon_lists_taxa_taxon_list_attribute',
    'taxon_rank',
    'taxon_relation',
    'taxon_group',
    'taxon_medium',
    'notification',
    'user_trust',
    'cache_taxon_searchterm',
    'cache_taxa_taxon_list',
    'verification_rule_datum',
  ];

  // List of tables that do not use views to expose their data.
  protected $tables_without_views = [
    'cache_taxon_searchterms',
    'cache_taxa_taxon_lists',
    'import_templates',
    'index_websites_website_agreements',
    'verification_rule_data',
    'users_websites',
  ];

  /**
   * Provides the /services/data/cache_taxa_taxon_list service.
   *
   * Retrieves details of a single taxon searchterm.
   */
  public function cache_taxa_taxon_list() {
    $this->handle_call('cache_taxa_taxon_list');
  }

  /**
   * Provides the /services/data/cache_taxon_searchterm service.
   *
   * Retrieves details of a single taxon searchterm.
   */
  public function cache_taxon_searchterm() {
    $this->handle_call('cache_taxon_searchterm');
  }

  /**
   * Provides the /services/data/custom_verification_ruleset service.
   *
   * Retrieves details of a single custom_verification_ruleset.
   */
  public function custom_verification_ruleset() {
    $this->handle_call('custom_verification_ruleset');
  }

  /**
   * Provides the /services/data/dna_occurrence service.
   *
   * Retrieves details of dna occurrences.
   */
  public function dna_occurrence() {
    $this->handle_call('dna_occurrence');
  }

  /**
   * Provides the /services/data/filter service.
   *
   * Retrieves details of a single filter.
   */
  public function filter() {
    $this->handle_call('filter');
  }

  /**
   * Provides the /services/data/filters_user service.
   *
   * Retrieves details of a single filters_user join record.
   */
  public function filters_user() {
    $this->handle_call('filters_user');
  }

  /**
   * Provides the /services/data/group service.
   *
   * Retrieves details of a single group.
   */
  public function group() {
    $this->handle_call('group');
  }

  /**
   * Provides the /services/data/group_page service.
   *
   * Retrieves details of a single group.
   */
  public function group_page() {
    $this->handle_call('group_page');
  }

  /**
   * Provides the /services/data/group_invitation service.
   *
   * Retrieves details of a single group_invitation.
   */
  public function group_invitation() {
    $this->handle_call('group_invitation');
  }

  /**
   * Provides the /services/data/group_relation service.
   *
   * Retrieves details of a single group_relation.
   */
  public function group_relation() {
    $this->handle_call('group_relation');
  }

  /**
   * Provides the /services/data/groups_location service.
   *
   * Retrieves details of a single groups_location.
   */
  public function groups_location() {
    $this->handle_call('groups_location');
  }

  /**
   * Provides the /services/data/groups_user service.
   *
   * Retrieves details of a single groups_user.
   */
  public function groups_user() {
    $this->handle_call('groups_user');
  }

  /**
   * Provides the /services/data/import_template service.
   *
   * Retrieves details of a single groups_user.
   */
  public function import_template() {
    $this->handle_call('import_template');
  }

  /**
   * Provides the /services/data/index_websites_website_agreements service.
   *
   * Retrieves details of a single index_websites_website_agreements record.
   */
  public function index_websites_website_agreement() {
    $this->handle_call('index_websites_website_agreement');
  }

  /**
   * Provides the /services/data/language service.
   *
   * Retrieves details of a single language.
   */
  public function language() {
    $this->handle_call('language');
  }

  /**
   * Provides the /services/data/licence service.
   *
   * Retrieves details of a single location.
   */
  public function licence() {
    $this->handle_call('licence');
  }

  /**
   * Provides the /services/data/licences_website service.
   *
   * Retrieves details of a single location.
   */
  public function licences_website() {
    $this->handle_call('licences_website');
  }


  /**
   * Provides the /services/data/location service.
   * Retrieves details of a single location.
   */
  public function location() {
    $this->handle_call('location');
  }

  /**
   * Provides the /service/data/location_attribute service.
   *
   * Retrieves details of location attributes.
   */
  public function location_attribute() {
    $this->handle_call('location_attribute');
  }

  /**
   * Provides the /service/data/location_attribute_value service.
   *
   * Retrieves details of location attribute values.
   */
  public function location_attribute_value() {
    $this->handle_call('location_attribute_value');
  }

  /**
   * Provides the /services/data/location_comments service.
   */
  public function location_comment() {
    $this->handle_call('location_comment');
  }

  /**
   * Provides the /service/data/location_image service.
   *
   * Retrieves details of location media.
   * @deprecated
   */
  public function location_image() {
    $this->handle_call('location_medium');
  }

  /**
   * Provides the /service/data/location_medium service.
   *
   * Retrieves details of location media.
   */
  public function location_medium() {
    $this->handle_call('location_medium');
  }

  /**
   * Provides the /service/data/sample_image service.
   *
   * Retrieves details of sample media.
   * @deprecated
   */
  public function sample_image() {
    $this->handle_call('sample_medium');
  }

  /**
   * Provides the /service/data/sample_medium service.
   *
   * Retrieves details of sample media.
   */
  public function sample_medium() {
    $this->handle_call('sample_medium');
  }

  /**
   * Provides the /services/data/occurrence service.
   *
   * Retrieves details of notifications.
   */
  public function notification() {
    $this->handle_call('notification');
  }

  /**
   * Provides the /services/data/occurrence service.
   *
   * Retrieves details of occurrences.
   */
  public function occurrence() {
    $this->handle_call('occurrence');
  }

  /**
   * Provides the /service/data/occurrence_attribute service.
   *
   * Retrieves details of occurrence attributes.
   */
  public function occurrence_attribute() {
    $this->handle_call('occurrence_attribute');
  }

  /**
   * Provides the /service/data/occurrence_attribute_value service.
   *
   * Retrieves details of occurrence attribute values.
   */
  public function occurrence_attribute_value() {
    $this->handle_call('occurrence_attribute_value');
  }

  /**
   * Provides the /services/data/occurrence_comments service.
   */
  public function occurrence_comment() {
    $this->handle_call('occurrence_comment');
  }

  /**
   * Provides the /service/data/occurrence_images service.
   *
   * Retrieves details of occurrence media. This is an alias for
   * occurrence_medium, for backwards compatibility.
   */
  public function occurrence_image() {
    $this->handle_call('occurrence_medium');
  }

  /**
   * Provides the /service/data/occurrence_medium service.
   * Retrieves details of occurrence media.
   */
  public function occurrence_medium() {
    $this->handle_call('occurrence_medium');
  }

  /**
   * Provides the /service/data/determination service.
   *
   * Retrieves details of occurrence attributes.
   */
  public function determination() {
    $this->handle_call('determination');
  }

  /**
   * Provides the /services/data/person service.
   *
   * Retrieves details of a single person.
   */
  public function person() {
    $this->handle_call('person');
  }


  /**
   * Provides the /service/data/person_attribute service.
   *
   * Retrieves details of person attributes.
   */
  public function person_attribute() {
    $this->handle_call('person_attribute');
  }


  /**
   * Provides the /service/data/person_attribute_value service.
   *
   * Retrieves details of person attribute values.
   */
  public function person_attribute_value() {
    $this->handle_call('person_attribute_value');
  }

  /**
   * Provides the /services/data/sample service.
   *
   * Retrieves details of a sample.
   */
  public function sample() {
    $this->handle_call('sample');
  }

  /**
   * Provides the /service/data/sample_attribute service.
   *
   * Retrieves details of sample attributes.
   */
  public function sample_attribute() {
    $this->handle_call('sample_attribute');
  }

  /**
   * Provides the /service/data/sample_attribute_value service.
   *
   * Retrieves details of sample attribute values.
   */
  public function sample_attribute_value() {
    $this->handle_call('sample_attribute_value');
  }

  /**
   * Provides the /services/data/sample_comments service.
   */
  public function sample_comment() {
    $this->handle_call('sample_comment');
  }

  /**
   * Provides the /services/data/survey service.
   *
   * Retrieves details of a single survey.
   */
  public function survey() {
    $this->handle_call('survey');
  }

  /**
   * Provides the /service/data/survey_attribute service.
   *
   * Retrieves details of location attributes.
   */
  public function survey_attribute() {
    $this->handle_call('survey_attribute');
  }

  /**
   * Provides the /service/data/survey_attribute_value service.
   *
   * Retrieves details of location attribute values.
   */
  public function survey_attribute_value() {
    $this->handle_call('survey_attribute_value');
  }

  /**
   * Provides the /services/data/survey_comment service.
   */
  public function survey_comment() {
    $this->handle_call('survey_comment');
  }

  /**
   * Provides the /service/data/survey_medium service.
   *
   * Retrieves details of sample media.
   */
  public function survey_medium() {
    $this->handle_call('survey_medium');
  }

  /**
   * Provides the /service/data/taxon_code service.
   *
   * Retrieves details of taxon codes.
   */
  public function taxon_code() {
    $this->handle_call('taxon_code');
  }

  /**
   * Provides the /services/data/taxon_group service.
   *
   * Retrieves details of a single taxon_group.
   */
  public function taxon_group() {
    $this->handle_call('taxon_group');
  }

  /**
   * Provides the /service/data/taxon_image service.
   *
   * Retrieves details of taxon media.
   * @deprecated
   */
  public function taxon_image() {
    $this->handle_call('taxon_medium');
  }

  /**
   * Provides the /service/data/taxon_medium service.
   *
   * Retrieves details of taxon media.
   */
  public function taxon_medium() {
    $this->handle_call('taxon_medium');
  }


  /**
   * Provides the /services/data/taxon_list service.
   *
   * Provides access to taxon_lists.
   */
  public function taxon_list() {
    $this->handle_call('taxon_list');
  }

  /**
  * Provides the /services/data/taxon_rank service.

  * Provides access to taxon ranks.
  */
  public function taxon_rank() {
    $this->handle_call('taxon_rank');
  }

  /**
   * Provides the /services/data/taxa_search service.
   *
   * Provides search for taxon names.
   */
  public function taxa_search() {
    if (array_key_exists('submission', $_POST)) {
      throw new exception('Cannot post to the taxa_search URL.');
    }
    $this->handle_call('taxa_search');
  }

  /**
   * Provides the /services/data/taxon_relation_type service.
   *
   * Provides access to taxon_relation_types.
   */
  public function taxon_relation_type() {
    $this->handle_call('taxon_relation_type');
  }

  /**
   * Provides the /services/data/taxa_taxon_list service.
   *
   * Retrieves details of taxa on a taxon_list.
   */
  public function taxa_taxon_list() {
    $this->handle_call('taxa_taxon_list');
  }

  /**
   * Provides the /service/data/taxa_taxon_list_attribute service.
   *
   * Retrieves details of taxa on taxon list attributes.
   */
  public function taxa_taxon_list_attribute() {
    $this->handle_call('taxa_taxon_list_attribute');
  }

  /**
   * Provides the /service/data/taxa_taxon_list_attribute_value service.
   *
   * Retrieves details of taxa on taxon list attribute values.
   */
  public function taxa_taxon_list_attribute_value() {
    $this->handle_call('taxa_taxon_list_attribute_value');
  }

  /**
   * Provides the /service/data/termlists_term_attribute service.
   *
   * Retrieves details of taxa on taxon list attributes.
   */
  public function termlists_term_attribute() {
    $this->handle_call('termlists_term_attribute');
  }

  /**
   * Provides the /service/data/termlists_term_attribute_value service.
   *
   * Retrieves details of taxa on taxon list attribute values.
   */
  public function termlists_term_attribute_value() {
    $this->handle_call('termlists_term_attribute_value');
  }

  /**
   * Provides the /services/data/taxa_relation service.
   *
   * Retrieves details of taxon_relations.
   */
  public function taxon_relation() {
    $this->handle_call('taxon_relation');
  }

  /**
  * Provides the /services/data/term service.
  * Retrieves details of a single term.
  */
  public function term() {
    $this->handle_call('term');
  }

  /**
   * Provides the /services/data/termlist service.
   *
   * Retrieves details of a single termlist.
   */
  public function termlist() {
    $this->handle_call('termlist');
  }

  /**
   * Provides the /services/data/termlists_term service.
   *
   * Retrieves details of a single termlists_term.
   */
  public function termlists_term() {
    $this->handle_call('termlists_term');
  }

  /**
   * Provides the /services/data/title service.
   *
   * Retrieves details of titles.
   */
  public function title() {
    $this->handle_call('title');
  }

  /**
   * Provides the /services/data/user service.
   *
   * Retrieves details of a single user.
   */
  public function user() {
    $this->handle_call('user');
  }

  /**
   * Provides the /services/data/user service.
   *
   * Retrieves details of a single user identifier.
   */
  public function user_identifier() {
    $this->handle_call('user_identifier');
  }

  /**
   * Provides the /services/data/users_website service.
   */
  public function users_website() {
    $this->handle_call('users_website');
  }

  public function user_trust() {
    $this->handle_call('user_trust');
  }

  public function comment_quick_reply_page_auth() {
    $this->handle_call('comment_quick_reply_page_auth');
  }

  /**
   * Provides the /services/data/verification_rule_data service.
   *
   * Retrieves details of a single taxon searchterm.
   */
  public function verification_rule_datum() {
    $this->handle_call('verification_rule_datum');
  }

  /**
   * Provides the /services/data/website service.
   *
   * Retrieves details of a single website.
   */
  public function website() {
    $this->handle_call('website');
  }

  /**
   * Provides the /services/data/website_agreement service.
   *
   * Retrieves details of a single website.
   */
  public function website_agreement() {
    $this->handle_call('website_agreement');
  }

  /**
   * Provides the /services/data/websites_website_agreement service.
   *
   * Retrieves details of a single website.
   */
  public function websites_website_agreement() {
    $this->handle_call('websites_website_agreement');
  }

  /**
   * Provides the /services/data/trigger service.
   *
   * Retrieves details of a single trigger.
   */
  public function trigger() {
    $this->handle_call('trigger');
  }

  /**
   * Catch calls to non-core entities.
   *
   * Magic method which accepts data service calls for non-core entities that
   * are handled by plugins. Checks to see if the any plugins expose a model
   * which matches the requested entity and checks if the model is only read
   * only then only read requests are accepted.
   * Plugins can use the extend_data_services hook to declare their models to
   * expose via data services.
   * @link https://github.com/indicia-team/warehouse/wiki/WarehousePluginArchitecture
   *
   * @param string $name
   *   Called controller function name (entity).
   */
  public function __call($name, $arguments) {
    $extensions = $this->loadExtensions($name);
    if (array_key_exists(inflector::plural($name), $extensions)) {
      $this->handle_call($name);
    }
    else {
      echo "Unrecognised entity $name";
    }
  }

  /**
   * Load any warehouse modules which extend the data services entity list.
   *
   * @param string $entity
   *   Entity name for this services call.
   *
   * @return array
   *   List of extension definitions.
   */
  protected function loadExtensions($entity) {
    // Use caching, so things don't slow down if there are lots of plugins.
    $cacheId = 'extend-data-services';
    $cache = Cache::instance();
    $extensions = $cache->get($cacheId);
    if (!$extensions) {
      $extensions = [];
      // Now look for modules which plugin to add a data service extension.
      foreach (Kohana::config('config.modules') as $path) {
        $plugin = basename($path);
        if (file_exists("$path/plugins/$plugin.php")) {
          require_once "$path/plugins/$plugin.php";
          if (function_exists($plugin . '_extend_data_services')) {
            $moduleExtensions = call_user_func($plugin . '_extend_data_services');
            $extensions = array_merge($extensions, $moduleExtensions);
          }
        }
      }
      $cache->set($cacheId, $extensions);
    }
    if (array_key_exists(inflector::plural($entity), $extensions)) {
      $extensionOpts = $extensions[inflector::plural($entity)];
    }
    if (isset($extensionOpts) && (!isset($extensionOpts['readOnly']) || $extensionOpts['readOnly'] !== TRUE)) {
      $this->allow_updates[] = $entity;
    }
    // Allow modules to provide an option when extending data services to
    // allow tables without website ids to be written to.
    if (isset($extensionOpts['allow_full_access']) && $extensionOpts['allow_full_access'] == 1) {
      $this->allow_full_access[] = $entity;
    }
    if (isset($extensionOpts['table_without_views']) && $extensionOpts['table_without_views'] == 1) {
      $this->tables_without_views[] = inflector::plural($entity);
    }
    return $extensions;
  }

  /**
   * Internal method to handle calls.
   *
   * Decides if it's a request for data or a submission.
   *
   * @param string $entity
   *   Name of the affected entity.
   */
  protected function handle_call($entity) {
    $tm = microtime(TRUE);
    try {
      $this->entity = $entity;
      if (array_key_exists('submission', $_POST)) {
        $this->handle_submit();
      }
      else {
        $this->handle_request();
      }
      kohana::log('debug', 'Sending reponse size ' . strlen($this->response));
      $this->send_response();
      if (class_exists('request_logging')) {
        // Note that we store the response for submissions as more practical
        // than entire POST.
        request_logging::log(array_key_exists('submission', $_POST) ? 'i' : 'o', 'data', NULL, $entity,
          $this->website_id, $this->user_id, $tm, $this->db, NULL,
          array_key_exists('submission', $_POST) ? $this->response : NULL);
      }
    }
    catch (Exception $e) {
      $this->handle_error($e);
      if (class_exists('request_logging')) {
        request_logging::log(array_key_exists('submission', $_POST) ? 'i' : 'o', 'data', NULL, $entity,
          $this->website_id, $this->user_id, $tm, $this->db, $e->getMessage());
      }
    }
  }

  /**
   * Internal method for handling a generic submission to a particular model.
   */
  protected function handle_submit() {
    $this->authenticate();
    $mode = $this->get_input_mode();
    $s = [];
    switch ($mode) {
      case 'json':
        $s = json_decode($_POST['submission'], TRUE);
    }

    if (array_key_exists('submission', $s)) {
      $id = $this->submit($s);
      // @todo proper handling of result checking.
      $result = TRUE;
    }
    else {
      $this->check_update_access($this->entity, $s);
      $model = ORM::factory($this->entity);
      $model->submission = $s;
      $result = $model->submit();
      kohana::log('debug', "Model submit: $model->object_name $model->id");
      $id = $model->id;
    }
    if ($result) {
      $this->response = json_encode(['success' => $id]);
      $this->delete_nonce();
    }
    elseif (isset($model) && is_array($model->getAllErrors())) {
      if ($model->uniqueKeyViolation) {
        throw new ValidationError('Duplicate key violation', 2004, $model->getAllErrors());
      }
      else {
        throw new ValidationError('Error occurred on model submission', 2003, $model->getAllErrors());
      }
    }
    else {
      throw new Exception('Unknown error on submission of the model');
    }

  }

  /**
   * Decoding of array parameters.
   *
   * Checks that a parameter for the taxon search contains a single parameter
   * value or a valid JSON array.
   *
   * @param string $value
   *   Value to decode.
   *
   * @return mixed
   *   Decoded value.
   */
  private function decodeArrayParameter($value) {
    $decoded = json_decode($value);
    // Strings which contain commas but not valid JSON are almost certainly mistakes.
    if ($decoded === NULL && strpos($value, ',') !== FALSE) {
      throw new ValidationError('Validation error', 2003, 'Invalid format for array parameter.');
    }
    return $decoded === NULL ? $value : $decoded;
  }

  /**
   * Fetches the results of a taxon search query (taxa_search endpoint).
   *
   * @return array
   *   Search results.
   */
  protected function getDataTaxaSearch() {
    $params = $_REQUEST;
    // Accept q as search param, as this is used by autocompletes by default.
    if (!empty($params['q'])) {
      $params['searchQuery'] = $params['q'];
      unset($params['q']);
    }
    unset($params['auth_token']);
    unset($params['nonce']);
    $possibleArrays = [
      'taxon_list_id',
      'language',
      'taxon_group_id',
      'taxon_group',
      'family_taxa_taxon_list_id',
      'taxon_meaning_id',
      'preferred_taxon',
      'external_key',
      'organism_key',
      'taxa_taxon_list_id',
    ];
    foreach ($possibleArrays as $possibleArrayParam) {
      if (isset($params[$possibleArrayParam])) {
        $params[$possibleArrayParam] = $this->decodeArrayParameter($params[$possibleArrayParam]);
      }
    }
    // Convert bool strings to true booleans.
    $possibleBools = ['preferred', 'commonNames', 'synonyms', 'abbreviations', 'marine_flag', 'searchAuthors',
        'wholeWords'];
    foreach ($possibleBools as $possibleBoolParam) {
      if (isset($params[$possibleBoolParam])) {
        if (in_array($params[$possibleBoolParam], array('true', 't', '1'))) {
          $params[$possibleBoolParam] = TRUE;
        } elseif (in_array($params[$possibleBoolParam], array('false', 'f', '0'))) {
          $params[$possibleBoolParam] = FALSE;
        }
      }
    }
    $query = postgreSQL::taxonSearchQuery($this->db, $params);
    $response = $this->db->query($query)->result_array(FALSE);
    return $response;
  }

  /**
   * Retrieve the records for a read request.
   *
   * Also sets the list of columns into $this->columns.
   *
   * @return array
   *   Query results array.
   */
  protected function read_data() {
    if (!$this->db) {
      $this->db = new Database();
    }
    if ($this->entity === 'taxa_search') {
      // Special case for taxa_search end-point as it uses a custom query.
      $result = $this->getDataTaxaSearch();
      kohana::log('debug', "Query ran for service call:\n" . $this->db->last_query());
    }
    else {
      // Store the entity in class member, so less recursion overhead when
      // building XML.
      $this->viewname = $this->get_view_name();
      $this->view_columns = postgreSQL::list_fields($this->viewname, $this->db);
      $result = $this->build_query_results();
    }
    return ['records' => $result];
  }

  /**
   * Handle uploaded files by moving them to the upload folder.
   *
   * Images get resized and duplicated as specified in the indicia config file.
   * If the $_POST array contains name_is_guid=true, then the media file will
   * not be renamed as the name should already be globally unique. Otherwise
   * the current time is prefixed to the name to make it unique.
   */
  public function handle_media() {
    try {
      // Ensure we have write permissions.
      $this->authenticate();
      // We will be using a POST array to send data, and presumably a FILES
      // array for the media.
      // Upload size.
      $ups = Kohana::config('indicia.maxUploadSize');
      // Get comma separated list of allowed file types.
      $config = kohana::config('indicia.upload_file_type');
      if (!$config) {
        // Default list if no entry in config.
        $types = 'png,gif,jpg,jpeg,mp3,wav,pdf';
      }
      else {
        // Implode array of arrays.
        $types = implode(',', array_map(function($a){
          return implode(',', $a);
        }, $config));
      }

      $_FILES = Validation::factory($_FILES)->add_rules(
        'media_upload', 'upload::valid', 'upload::required',
        "upload::type[$types]", "upload::size[$ups]"
      );
      if ($_FILES->validate()) {
        if (array_key_exists('name_is_guid', $_POST) && $_POST['name_is_guid'] == 'true') {
          $finalName = strtolower($_FILES['media_upload']['name']);
        }
        else {
          $finalName = time() . strtolower($_FILES['media_upload']['name']);
        }
        // Time is approx 10 characters long at the moment & will be for
        // forseeable future. If we use first 3 sets of pairs for directory
        // name, then will get a new directory every 3 hours.
        $levels = Kohana::config('upload.use_sub_directory_levels');
        $subdir = '';
        $directory = Kohana::config('upload.directory', TRUE);
        if ($levels) {
          $now = (string) time();
          for ($i = 0; $i < $levels; $i++) {
            $dirname = substr($now, 0, 2);
            if (strlen($dirname)) {
              $subdir .= $dirname . '/';
              $now = substr($now, 2);
            }
          }
          if ($subdir != "" && !is_dir($directory . $subdir)){
            kohana::log('debug', "Creating Directory $directory$subdir");
            mkdir($directory . $subdir, 0755, TRUE);
          }
        }
        $fTmp = upload::save('media_upload', $finalName, $directory . $subdir);
        Image::create_image_files($directory, basename($fTmp), $subdir, $this->website_id);
        $this->response = $subdir . basename($fTmp);
        $this->send_response();
        kohana::log('debug', 'Successfully uploaded media to ' . $subdir . basename($fTmp));
      }
      else {
        kohana::log('info', 'Validation errors uploading media ' . $_FILES['media_upload']['name']);
        throw new ValidationError('Validation error', 2003, $_FILES->errors('form_error_messages'));
      }
    }
    catch (Exception $e) {
      $this->handle_error($e);
    }
  }

  /**
   * Builds a query to extract data from the requested entity.
   *
   * Also include relationships to foreign key tables and the caption fields
   * from those tables.
   *
   * @param bool $count
   *   If set to true then just returns a record count.
   *
   * @todo Review this code for SQL Injection attack!
   * @todo Basic website filter done, but not clever enough.
   */
  protected function build_query_results($count = FALSE) {
    $this->foreign_keys = [];
    $this->db->from($this->viewname);
    // Select all the table columns from the view.
    if (!$count) {
      $fields = array_keys(postgreSQL::list_fields($this->viewname, $this->db));
      $usedFields = [];
      $request = array_merge($_GET, $_POST);
      $columns = isset($request['columns']) ? explode(',', $request['columns']) : FALSE;
      foreach ($fields as &$field) {
        if (!$columns || in_array($field, $columns)) {
          // Geom binary data is no good to anyone. So convert to WKT.
          if (preg_match('/^(.+_)?geom$/', $field)) {
            $usedFields[] = 'st_astext(' . $this->viewname . ".$field) as $field";
          }
          else {
            $usedFields[] = "$this->viewname.$field";
          }
        }
      }
      if (!empty($_REQUEST['attrs'])) {
        $attrTables = ['survey', 'sample', 'occurrence', 'people', 'taxa_taxon_list'];
        if (in_array($this->entity, $attrTables)) {
          $attrs = explode(',', $_REQUEST['attrs']);
          foreach ($attrs as $attr) {
            $usedFields[] = "val_{$this->entity}_$attr.value as attr_{$this->entity}_$attr";
          }
        }
      }
      $select = implode(', ', $usedFields);
      $this->db->select($select);
    }
    // If not in the warehouse, then the entity must explicitly allow full
    // access, or contain a website ID to filter on.
    if (!$this->in_warehouse && !array_key_exists('website_id', $this->view_columns) &&
        !array_key_exists('from_website_id', $this->view_columns) && !in_array($this->entity, $this->allow_full_access)) {
      // If access is from remote website, then either table allows full access
      // or exposes a website ID to filter on.
      Kohana::log('info', "$this->viewname does not have a website_id - access denied");
      throw new EntityAccessError("No access to entity $this->entity allowed through view $this->viewname", 1004);
    }
    if (array_key_exists('website_id', $this->view_columns)) {
      $websiteFilterField = 'website_id';
    }
    elseif (array_key_exists('from_website_id', $this->view_columns)) {
      $websiteFilterField = 'from_website_id';
    }
    // Loading a list of records (no record ID argument)
    if (isset($websiteFilterField)) {
      // We have a filter on website_id to apply.
      if ($this->website_id) {
        // Check if a request for shared data is being made. Also check this is
        // valid to prevent injection.
        if (isset($_REQUEST['sharing']) && preg_match('/(reporting|peer_review|verification|data_flow|moderation|editing)/', $_REQUEST['sharing'])) {
          // Request specifies the sharing mode (i.e. the task being performed,
          // such as verification, moderation). So we can use this to work out
          // access to other website data.
          $this->db->join('index_websites_website_agreements as iwwa', [
            'iwwa.from_website_id' => $this->viewname . '.' . $websiteFilterField,
            'iwwa.provide_for_' . $_REQUEST['sharing'] . "='t'" => '',
          ], NULL, 'LEFT');
          $this->db->where('(' . $this->viewname . '.' . $websiteFilterField . ' IS NULL OR iwwa.to_website_id=' . $this->website_id . ')');
        }
        else {
          $this->db->in($this->viewname . '.' . $websiteFilterField, [NULL, $this->website_id]);
        }
      }
      elseif ($this->in_warehouse && !$this->user_is_core_admin) {
        // User is on Warehouse, but not core admin, so do a filter to all their
        // websites.
        $allowedWebsiteValues = array_merge($this->userWebsites);
        $allowedWebsiteValues[] = NULL;
        $this->db->in('website_id', $allowedWebsiteValues);
      }
    }
    if ($this->uri->total_arguments() == 0) {
      // Filter the list according to the parameters in the call.
      $this->apply_get_parameters_to_db($count);
    }
    else {
      $this->db->where("$this->viewname.id", $this->uri->argument(1));
    }
    try {
      if ($count) {
        $r = $this->db->count_records();
        kohana::log('debug', "Query ran for count service call:\n" . $this->db->last_query());
        return $r;
      }
      else {
        $dbResult = $this->db->get();
        // Either load full data array, or just database result to iterate
        // through which uses less memory, depending on format.
        $r = in_array($this->get_output_mode(), self::STREAMABLE_FORMATS) ? $dbResult->result() : $dbResult->result_array(FALSE);
        kohana::log('debug', "Query ran for service call:\n" . $this->db->last_query());
        // If we got no record but asked for a specific one, check if this was
        // a permissions issue?
        if (!count($r) && $this->uri->total_arguments() !== 0 && !$this->check_record_access($this->entity, $this->uri->argument(1), $this->website_id, isset($_REQUEST['sharing']) ? $_REQUEST['sharing'] : FALSE)) {
          Kohana::log('info', 'Attempt to access existing record failed - website_id ' . $this->website_id . ' does not match website for ' . $this->entity . ' id ' . $this->uri->argument(1));
          throw new EntityAccessError('Attempt to access existing record failed - website_id ' . $this->website_id . ' does not match website for ' . $this->entity . ' id ' . $this->uri->argument(1), 1001);
        }
        return $r;
      }
    }
    catch (Exception $e) {
      kohana::log('error', 'Error occurred running the following query from a service request:');
      kohana::log('error', $e->getMessage());
      kohana::log('error', $this->db->last_query());
      kohana::log('error', 'Request detail:');
      kohana::log('error', $this->uri->string());
      kohana::log('error', kohana::debug($_REQUEST));
      throw $e;
    }
  }

  /**
   * Returns the name of the view for the request.
   *
   * This is a view associated with the entity, but prefixed by either list, gv
   * or max depending on the GET view parameter, or as is if the table has no
   * views.
   */
  protected function get_view_name($table = '', $prefix = '') {
    if (!$table) {
      $table = $this->entity;
    }
    $table = inflector::plural($table);
    if (in_array($table, $this->tables_without_views)) {
      return $table;
    }
    if (!$prefix && array_key_exists('view', $_REQUEST)) {
      $prefix = $_REQUEST['view'];
    }
    // Check for allowed view prefixes, and use 'list' as the default.
    if ($prefix !== 'gv' && $prefix !== 'detail' && $prefix !== 'cache') {
      $prefix = 'list';
    }
    return $prefix . '_' . $table;
  }

  /**
   * Works out what filter and other options to set on the db object according to the
   * $_REQUEST parameters currently available, when retrieving a list of items.
   *
   * @param bool $count
   *   Set to true when doing a count query, so the limit and offset are
   *   skipped.
   */
  protected function apply_get_parameters_to_db($count = FALSE) {
    $sortdir = [];
    $orderby = [];
    $like = [];
    $where = [];
    // Don't use $_REQUEST as it has a tendency to escape values in different
    // ways on different PHP versions.
    $request = array_merge($_GET, $_POST);
    foreach ($request as $param => $value) {
      switch ($param) {
        case 'sortdir':
          if ($count) {
            break;
          }
          $sortdir = explode(',', strtoupper($value));
          // Default to ASC any which are not ASC or DESC for safety.
          foreach ($sortdir as $idx => $dir) {
            if ($dir !== 'ASC' && $dir !== 'DESC') {
              $sortdir[$idx] = 'ASC';
            }
          }
          break;

        case 'orderby':
          if (!$count) {
            $orderby = explode(',', strtolower($value));
            // Strip any which are not field names for safety.
            foreach ($orderby as $idx => $field) {
              if (!array_key_exists($field, $this->view_columns)) {
                unset($orderby[$idx]);
              }
            }
          }
          break;

        case 'limit':
          if (!$count && is_numeric($value)) {
            $this->db->limit($value);
          }
          break;

        case 'offset':
          if (!$count && is_numeric($value)) {
            $this->db->offset($value);
          }
          break;

        case 'qfield':
          if (array_key_exists(strtolower($value), $this->view_columns)) {
            $qfield = strtolower($value);
          }
          break;

        case 'q':
          $q = $value;
          break;

        case 'attrs':
          // Check that we're dealing with 'occurrence', 'location' or 'sample'
          // here.
          // @todo check this works - looks like it does nothing...
          $attrTables = ['survey', 'sample', 'occurrence', 'people', 'taxa_taxon_list'];
          if (in_array($this->entity, $attrTables)) {
            $attrs = explode(',', $value);
          }
          break;

        case 'query':
          // A fix for a bug in data_entry_helper where the query passed in the
          // getAttributes method is double urlencoded.
          if (substr($value, 0, 3) === '%7B') {
            $value = urldecode($value);
          }
          $this->applyQueryDefToDb($value);
          break;

        case 'mode':
        case 'view':
        case 'nonce':
        case 'auth_token':
        case 'callback':
        case 'timestamp':
        case 'columns':
        case '_':
          break;

        default:
          if (array_key_exists(strtolower($param), $this->view_columns)) {
            // A parameter has been supplied which specifies the field name of
            // a filter field.
            if ($value == 'NULL') {
              $value = NULL;
            }
            // Build a where for ints, bools or if there is no * in the search
            // string.
            if ($param === 'occurrence_id') {
              kohana::log('debug', var_export($this->view_columns[$param], TRUE));
              kohana::log('debug', $value);
            }
            if ($this->view_columns[$param]['type'] === 'int') {
              if ($value !== NULL && !preg_match('/^\d+$/', trim($value))) {
                throw new ValidationError('Validation error', 2003, 'Invalid format for integer column filter.');
              }
              $where["$this->viewname.$param"] = $value;
            }
            elseif ($this->view_columns[$param]['type'] === 'bool') {
              if ($value !== NULL && !preg_match('/^([tf]|true|false)$/i', trim($value))) {
                throw new ValidationError('Validation error', 2003, 'Invalid format for boolean column filter.');
              }
              $where["$this->viewname.$param"] = $value;
            }
            elseif ($value === NULL || strpos($value, '*') === FALSE) {
              $where["$this->viewname.$param"] = $value;
            }
            else {
              $like["$this->viewname.$param"] = pg_escape_string($this->db->getLink(), str_replace('*', '%', $value));
            }
          }
          else {
            Kohana::log('debug', "Trying to filter on unknown column $param. Ignoring.");
          }
      }
    }
    if (isset($qfield) && isset($q)) {
      if ($this->view_columns[$qfield]['type'] === 'int' || $this->view_columns[$qfield]['type'] === 'bool') {
        $where[$qfield] = $q;
      }
      else {
        // When using qfield and q parameters, it is from an AJAX call for an
        // autocomplete, so append a wildcard and also switch any service
        // wildcards (*) for sql wildcards (%).
        $searchTerm = str_replace('*', '%', $q) . '%';
        // Special case for taxon searchterm. If the searchterm might be for an
        // abbreviation, we need to use the unsimplified version to search to
        // avoid problems with simplification of ae -> a breaking the
        // abbreviation.
        if ($this->entity === 'cache_taxon_searchterm' && $qfield === 'searchterm'
            // Only bother for 5 char searches that might be abbreviations.
            && !empty($_GET['unsimplified']) && strlen($_GET['unsimplified']) === 5
            // And only bother if searches against abbreviations (which don't
            // use the simplified flag) are allowed.
            && (empty($_GET['query']) || strpos(strtolower($_GET['query']), 'simplified') === FALSE
              || strpos(strtolower($_GET['query']), 'simplified is null') !== FALSE)
        ) {
          $this->db->where("($qfield like '$searchTerm' or ($qfield='$_GET[unsimplified]' AND name_type='A'))");
        }
        else {
          $like[$qfield] = $searchTerm;
        }
      }
    }
    if (count($orderby)) {
      // Build a multi-field order array according to Kohana db builder spec.
      // Default missing sort directions to ASC.
      $order = array_combine($orderby, array_pad($sortdir, count($orderby), 'ASC'));
      $this->db->orderby($order);
    }
    if (count($like)) {
      foreach ($like as $field => $value) {
        $this->db->like($field, $value, FALSE);
      }
    }
    if (count($where)) {
      $this->db->where($where);
    }
    if (isset($attrs)) {
      $attrValTable = "list_{$this->entity}_attribute_values";
      foreach ($attrs as $attr) {
        if (!preg_match('/^\d+$/', $attr)) {
          throw new exception("Request for invalid attribute ID $attr");
        }
        $this->db->join("$attrValTable as val_{$this->entity}_$attr", [
            "val_{$this->entity}_$attr.{$this->entity}_id" => "$this->viewname.id",
            "val_{$this->entity}_$attr.{$this->entity}_attribute_id=$attr" => '',
        ], NULL, 'LEFT');
      }
    }
  }

  /**
   * Apply a query parameter to the db object.
   *
   * Takes the value of a query parameter passed to the data service, and
   * processes it to apply the filter conditions defined in the JSON to
   * $this->db, ready for when the service query is run.
   *
   * @param string $value
   *   The value of the parameter called query, which should contain a JSON
   *   object.
   *
   * @link https://indicia-docs.readthedocs.io/en/latest/developing/web-services/data-services-read-dataset.html
   */
  protected function applyQueryDefToDb($value) {
    $query = json_decode($value, TRUE);
    foreach ($query as $cmd => $params) {
      switch (strtolower($cmd)) {
        case 'in':
        case 'notin':
          unset($foundfield);
          unset($foundvalue);
          foreach ($params as $key => $value) {
            if (is_int($key)) {
              if ($key === 0) {
                $foundfield = $value;
              }
              elseif ($key === 1) {
                $foundvalue = $value;
              }
              else {
                throw new Exception("In clause statement for $key is not of the correct structure");
              }
            }
            elseif (is_array($value)) {
              $this->db->$cmd($this->viewname . '.' . $key, $value);
            }
            else {
              throw new Exception("In clause statement for $key is not of the correct structure");
            }
          }
          // If param was supplied in form "cmd = array(field, values)" then
          // foundfield and foundvalue would be set.
          if (isset($foundfield) && isset($foundvalue))
            $this->db->$cmd($this->viewname . '.' . $foundfield, $foundvalue);
          break;

        case 'where':
        case 'orwhere':
        case 'like':
        case 'orlike':
          $this_cmd = $cmd;
          unset($foundfield);
          unset($foundvalue);
          foreach ($params as $key => $value) {
            if (is_int($key)) {
              if ($key === 0) {
                $foundfield = $value;
              }
              elseif ($key === 1) {
                $foundvalue = $value;
              }
              else {
                throw new Exception("In clause statement for $key is not of the correct structure");
              }
            }
            elseif (!is_array($value)) {
              // Id fields must be queried by Where clause not Like.
              $this_cmd = ($key === 'id') ? str_replace('like', 'where', $cmd) : $cmd;
              // Apply the filter command. if we are switching a like to a
              // where clause, but no value is provided, then don't filter
              // because like '%%' matches anything, but where x='' would break
              // on an int field.
              if ($this_cmd == $cmd || !empty($value)) {
                $this->db->$this_cmd($key, $value);
              }
            }
            else {
              throw new Exception("$cmd clause statement for $key is not of the correct structure. " . print_r($params, TRUE));
            }
          }
          // If param was supplied in form "cmd = array(field, value)" then
          // foundfield and foundvalue would be set.
          if (isset($foundfield) && isset($foundvalue)) {
            // id fields must be queried by Where clause not Like.
            if ($foundfield === 'id') {
              $this_cmd = str_replace('like', 'where', $cmd);
            }
            if ($this_cmd == $cmd || !empty($foundvalue)) {
              $this->db->$this_cmd($foundfield, $foundvalue);
            }
          }
          elseif (isset($foundfield) && ($cmd === 'where' || $cmd === 'orwhere')) {
            // With just 1 parameter passed through, a where can contain
            // something more complex such as an OR in brackets.
            // Check for unsafe values (note value might be an array).
            if (is_array($foundfield)) {
              foreach ($foundfield as $field) {
                if (is_string($field) && preg_match("/'[^'\\\\]*(?:\\.[^'\\\\]*)*'(*SKIP)(?!)|;/", $field)) {
                  kohana::log('alert', "Unsafe query where clause detected: $field");
                  throw new Exception("Unsafe value in where clause");
                }
              }
            }
            else {
              if (preg_match("/'[^'\\\\]*(?:\\.[^'\\\\]*)*'(*SKIP)(?!)|;/", $foundfield)) {
                kohana::log('alert', "Unsafe query where clause detected: $foundfield");
                throw new Exception("Unsafe value in where clause");
              }
            }
            $this->db->$cmd($foundfield);
          }
          break;

        default:
          kohana::log('error', "Unsupported query command $cmd");
      }
    }
  }

  /**
   * Accepts a submission from POST data and attempts to save to the database.
   */
  public function save() {
    $tm = microtime(TRUE);
    try {
      $response = '';
      $this->authenticate();
      if (array_key_exists('submission', $_POST)) {
        $mode = $this->get_input_mode();
        switch ($mode) {
          case 'json':
            // Use parse_str rather than $_POST as copes with encoding in JSON.
            parse_str(file_get_contents('php://input'), $data);
            $s = json_decode($data['submission'], TRUE);
        }
        $response = $this->submit($s);
        // Return a success message plus the id of the topmost record, e.g. the
        // sample created, plus a summary structure of any other records
        // created.
        $response = [
          'success' => 'multiple records',
          'outer_table' => $s['id'],
          'outer_id' => $response['id'],
          'struct' => $response['struct'],
        ];
        // If the saved form contained a transaction Id, return it.
        if (isset($s['fields']['transaction_id']['value'])) {
          $response['transaction_id'] = $s['fields']['transaction_id']['value'];
        }
        echo json_encode($response);
      }
      $this->delete_nonce();
      if (class_exists('request_logging') && isset($s)) {
        request_logging::log('i', 'data', $s['id'], 'save', $this->website_id, $this->user_id, $tm, $this->db, NULL, $response);
      }
    }
    catch (Exception $e) {
      $this->handle_error(
        $e,
        (isset($s) && isset($s['fields']['transaction_id']['value'])) ? $s['fields']['transaction_id']['value'] : null
      );
      if (class_exists('request_logging')) {
        request_logging::log(
          'i', 'data', isset($s) && isset($s['id']) ? $s['id'] : NULL, 'save',
          $this->website_id, $this->user_id, $tm, $this->db,
          $e->getMessage(), $response
        );
      }
    }
  }

  /**
  * Takes a submission array and attempts to save to the database. The submission array
  * can either contain a submission list or a single submission.
  */
  protected function submit($s) {
    kohana::log('info', 'submit');
    if (array_key_exists('submission_list', $s)) {
      foreach ($s['submission_list']['entries'] as $m) {
        $r = $this->submit_single($m);
      }
    }
    else {
      $r = $this->submit_single($s);
    }
    return $r;
  }

  /**
   * Takes a single submission entry and attempts to save to the database.
   */
  protected function submit_single($item) {
    $model = ORM::factory($item['id']); // id is the entity.
    $this->check_update_access($item['id'], $item);
    $model->submission = $item;
    ORM::$authorisedWebsiteId = $this->website_id;
    $result = $model->submit();
    if (!$result) {
      throw new ValidationError('Validation error', 2003, $model->getAllErrors());
    }
    // Return the outermost model's id.
    return [
      'id' => $model->id,
      'struct' => $model->getSubmissionResponseMetadata(),
    ];
  }

 /**
  * Checks that we have update access to a given entity for a given submission array.
  * The submission array is checked to see if there is a primary key ('id').
  * Returns true if access OK, otherwise throws an exception.
  */
  protected function check_update_access($entity, $s) {
    if (!in_array($entity, $this->allow_updates)) {
      // Check if an extension module declares write access to this entity.
      $this->loadExtensions($entity);
    }
    if (!in_array($entity, $this->allow_updates)) {
      $msg = "Attempt to update entity $entity by website $this->website_id: no write access allowed through services.";
      Kohana::log('info', $msg);
      throw new EntityAccessError($msg, 2002);
    }
    if (array_key_exists('id', $s['fields'])) {
      if (is_numeric($s['fields']['id']['value'])) {
        // There is an numeric id field so modifying an existing record.
        if (!$this->check_record_access($entity, $s['fields']['id']['value'],
            isset($_REQUEST['sharing']) && $_REQUEST['sharing'] !== 'reporting' ? $_REQUEST['sharing'] : FALSE)) {
          $msg = "Attempt to update existing record failed - website_id $this->website_id does not match website for " .
              "$entity id " . $s['fields']['id']['value'];
          Kohana::log('info', $msg);
          throw new AuthorisationError($msg, 2001);
        }
      }
    }
    return TRUE;
  }

  protected function check_record_access($entity, $id, $sharing = FALSE) {
    // If $id is null, then we have a new record, so no need to check if we
    // have access to the record.
    if (is_null($id)) {
      return TRUE;
    }
    $viewname = $this->get_view_name($entity, 'list');

    if (!$this->db) {
      $this->db = new Database();
    }
    $fields = postgreSQL::list_fields($viewname, $this->db);
    if (empty($fields)) {
      Kohana::log('info', $viewname . ' not present so cannot access entity');
      throw new EntityAccessError('Access to entity ' . $entity . ' not available via requested view.', 1003);
    }
    $this->db->from("$viewname as record");
    $this->db->where(['record.id' => $id]);

    if (!in_array($entity, $this->allow_full_access)) {
      if (array_key_exists('website_id', $fields)) {
        // Check if a request for shared data is being made. Also check this is
        // valid to prevent injection.
        if ($sharing && preg_match('/(reporting|peer_review|verification|data_flow|moderation|editing)/', $sharing)) {
          // Request specifies the sharing mode (i.e. the task being performed,
          // such as verification, moderation). So we can use this to work out
          // access to other website data.
          $this->db->join('index_websites_website_agreements as iwwa', [
              'iwwa.from_website_id' => 'record.website_id',
              'iwwa.provide_for_' . $sharing . "='t'" => ''
          ], NULL, 'LEFT');
          $this->db->where("(record.website_id IS NULL OR iwwa.to_website_id=$this->website_id)");
        }
        else {
          $this->db->in('record.website_id', [NULL, $this->website_id]);
        }
      }
      elseif (!$this->in_warehouse) {
        Kohana::log('info', "$viewname does not have a website_id - access denied");
        throw new EntityAccessError('No access to entity ' . $entity . ' allowed.', 1004);
      }
    }
    $number_rec = $this->db->count_records();
    return ($number_rec > 0 ? TRUE : FALSE);
  }

  /**
   * Get the record count of the full grid result.
   */
  protected function record_count() {
    return $this->build_query_results(TRUE);
  }

}