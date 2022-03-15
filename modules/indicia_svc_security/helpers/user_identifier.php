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
 * @author Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL 3.0
 * @link https://github.com/indicia-team/warehouse/
 */

defined('SYSPATH') or die('No direct script access.');
class user_identifier {

  /**
   * Database connection.
   *
   * @var obj
   */
  protected $db;

  /**
   * Retrieve a user ID web service endpoint.
   *
   * Helper method that takes a list of user identifiers such as email addresses
   * and returns the appropriate user ID from the warehouse, which can then be
   * used in subsequent calls to save the data. Takes the following parameters
   * in the $request (which is a merge of $_GET or $_POST data) in addition to a
   * nonce and auth_token for a write operation:
   * * identifiers - Required. A JSON encoded array of identifiers known for
   *   the user. Each array entry is an object with a type property (e.g.
   *   twitter, openid) and identifier property (e.g. twitter account). An
   *   identifier of type email must be provided in case a new user account has
   *   to be created on the warehouse.
   * * surname - Required. Surname of the user, enabling a new user account to
   *   be created on the warehouse.
   * * first_name - Optional. First name of the user, enabling a new user
   *   account to be created on the warehouse.
   * * cms_user_id - Optional. User ID from the client website's login system.
   *   Allows existing records to be linked to the created account when
   *   migrating from a CMS user ID based authentication to Easy Login based
   *   authentication.
   * * warehouse_user_id - Optional. Where a user ID is already known but a new
   *   identifier is being provided (e.g. an email switch), provide the
   *   warehouse user ID.
   * * force - Optional. Only relevant after a request has returned an array of
   *   several possible matches. Set to merge or split to define the action.
   * * users_to_merge - If force=merge, then this parameter can be optionally
   *   used to limit the list of users in the merge operation. Pass a JSON
   *   encoded array of user IDs.
   * * attribute_values - Optional list of custom attribute values for the
   *   person which have been modified on the client website and should be
   *   synchronised into the warehouse person record. The custom attributes
   *   must already exist on the warehouse and have a matching caption, as well
   *   as being marked as synchronisable or the attribute values will be
   *   ignored. Provide this as a JSON object with the properties being the
   *   caption of the attribute and the values being the values to change.
   * * shares_to_prevent - If the user has opted out of allowing their records
   *   to be shared with other websites, the sharing tasks which they have
   *   opted out of should be passed as a comma separated list here. Valid
   *   sharing tasks are: reporting, peer_review, verification, data_flow,
   *   moderation, editing. They will then be stored against the user account.
   *
   * @return JSON
   *   JSON object containing the following properties:
   *   * userId - If a single user account has been identified then returns the
   *     Indicia user ID for the existing or newly created account. Otherwise
   *     not returned.
   *   * attrs - If a single user account has been identifed then returns a
   *     list of captions and values for the attributes to update on the client
   *     account.
   *   * possibleMatches - If a list of possible users has been identified then
   *     this property includes a list of people that match from the warehouse -
   *     each with the user ID, website ID and website title they are members
   *     of. If this happens then the client must ask the user to confirm that
   *     they are the same person as the users of this website and if so, the
   *     response is sent back with a force=merge parameter to force the merge
   *     of the people. If they are the same person as only some of the other
   *     users, then use users_to_merge to supply an array of the user IDs that
   *     should be merged. Alternatively, if force=split is passed through then
   *     the best fit user ID is returned and no merge operation occurs.
   *   error - Error string if an error occurred.
   */
  public static function get_user_id($request, $websiteId) {
    // Test/escape $request parameters that are passed in to queries to prevent
    // SQL injection.
    // identifiers: looks like these are explicitly escaped and go through ORM.
    // surname: looks like only goes through ORM so escaped.
    // first_name: looks like only goes through ORM so escaped.
    // warehouse_user_id: only goes through query builder so escaped.
    // force: not passed to any query.
    // users_to_merge: looks like these all go through query builder so are escaped.
    // attribute_values: looks like these all go through ORM so are escaped.
    // shares_to_prevent: not passed to any query.

    if (!array_key_exists('identifiers', $request)) {
      throw new exception('Error: missing identifiers parameter');
    }
    $identifiers = json_decode($request['identifiers']);
    if (!is_array($identifiers)) {
      throw new Exception('Error: identifiers parameter not of correct format');
    }
    if (empty($request['surname'])) {
      throw new exception('Call to get_user_id requires a surname in the GET or POST data.');
    }
    $userPersonObj = new stdClass();
    $userPersonObj->db = new Database();
    if (!empty($request['warehouse_user_id'])) {
      $userId = $request['warehouse_user_id'];
      $qry = $userPersonObj->db->select('person_id')
        ->from('users')
        ->where([
          'id' => $userId,
          'deleted' => 'f',
        ])
        ->get()->result_array(FALSE);
      if (!isset($qry[0])) {
        throw new exception("Error: unknown warehouse_user_id ($userId)");
      }
      $userPersonObj->person_id = $qry[0]['person_id'];
    }
    else {
      $existingUsers = [];
      // Work through the list of identifiers and find the users for the ones
      // we already know about, plus find the list of identifiers we've not
      // seen before.
      // Email is a special identifier used to create person.
      $email = NULL;
      foreach ($identifiers as $identifier) {
        $ident = pg_escape_string($identifier->identifier);
        $type = pg_escape_string($identifier->type);
        $sql = '';
        if ($identifier->type === 'email') {
          $email = $identifier->identifier;
          // For emails. do a direct check on person.email_address in addition
          // to the query on user_identifiers.
          $sql = <<<SQL
SELECT DISTINCT u.id as user_id, u.person_id
FROM users u
JOIN people p ON p.id=u.person_id AND p.deleted=false
WHERE u.deleted=false
AND lower(p.email_address) = lower('$ident')
UNION

SQL;

        }
        // SQL must look in existing user_identifiers.
        $sql .= <<<SQL
SELECT DISTINCT u.id as user_id, u.person_id
FROM users u
JOIN people p
  ON p.id=u.person_id
  AND p.deleted=false
JOIN user_identifiers AS um
  ON um.user_id = u.id
  AND um.deleted=false
  AND lower(um.identifier)=lower('$ident')
JOIN cache_termlists_terms t
  ON t.id=um.type_id
  AND t.preferred_term='$type'
WHERE u.deleted=false
SQL;
        if (isset($request['users_to_merge'])) {
          // If limiting to a known set of users...
          $usersToMerge = implode(',', json_decode($request['users_to_merge']));
          $sql .= "\nAND u.id IN ($usersToMerge)";
        }
        $r = $userPersonObj->db->query($sql)->result_array(TRUE);
        kohana::log('debug', $sql);
        foreach ($r as $existingUser) {
          // Create a placeholder for the known user we just found.
          if (!isset($existingUsers[$existingUser->user_id])) {
            $existingUsers[$existingUser->user_id] = [];
          }
          // Add the identifier detail to this known user.
          $existingUsers[$existingUser->user_id][] = [
            'identifier' => $identifier->identifier,
            'type' => $identifier->type,
            'person_id' => $existingUser->person_id,
          ];
        }

      }
      if ($email === NULL) {
        throw new exception('Call to get_user_id requires an email address in the list of provided identifiers.');
      }
      // Now we have a list of the existing users that match this identifier.
      // If there are none, we can create a new user and attach to the current
      // website. If there is one, then we can just return it. If more than
      // one, then we have a resolution task since it probably means 2 user
      // records refer to the same physical person, or someone is sharing their
      // identifiers!
      if (count($existingUsers) === 0) {
        $userId = self::createUser($email, $userPersonObj);
      }
      elseif (count($existingUsers) === 1) {
        // Single, known user associated with these identifiers.
        $keys = array_keys($existingUsers);
        $userId = array_pop($keys);
        $userPersonObj->person_id = $existingUsers[$userId][0]['person_id'];
      }
      if (!isset($userId)) {
        $resolution = self::resolveMultipleUsers($identifiers, $existingUsers, $userPersonObj);
        // Response could be a list of possible users to match against, or a
        // single user ID.
        if (isset($resolution['possibleMatches'])) {
          return $resolution;
        }
        else {
          $userId = $resolution['userId'];
          $userPersonObj->person_id = $existingUsers[$userId][0]['person_id'];
        }
      }
    }
    self::storeIdentifiers($userId, $identifiers, $userPersonObj, $websiteId);
    self::associateWebsite($userId, $userPersonObj, $websiteId);
    self::storeSharingPreferences($userId, $userPersonObj);
    $attrs = self::getAttributes($userPersonObj, $websiteId);
    self::storeCustomAttributes($attrs, $userPersonObj);
    // Convert the attributes to update in the client website account into an
    // array of captions & values.
    $attrsToReturn = [];
    foreach ($attrs as $attr) {
      $attrsToReturn[$attr['caption']] = $attr['value'];
    }
    return [
      'userId' => $userId,
      'attrs' => $attrsToReturn,
    ];
  }

  /**
   * Finds the list of custom attribute values associated whith the person.
   *
   * @return array
   *   List of the attributes to synchronise into the client site.
   */
  private static function getAttributes($userPersonObj, $websiteId) {
    // Find the attribute Ids for the ones we have values for, that are
    // synchronisable and associated with the current website. Note we
    // deliberately read deleted values so that we can return blanks.
    $attrs = $userPersonObj->db->select('DISTINCT ON (pa.id) pa.id, pav.id as value_id, pa.caption, pa.data_type, ' .
          'pav.text_value, pav.int_value, pav.float_value, pav.date_start_value, pav.deleted')
      ->from('person_attributes as pa')
      ->join('person_attributes_websites as paw', 'paw.person_attribute_id', 'pa.id')
      ->join('person_attribute_values as pav', "pav.person_attribute_id=pa.id "
              . "AND (pav.person_id IS NULL OR pav.person_id=$userPersonObj->person_id) AND pav.deleted=false", '', 'LEFT')
      ->where([
        'pa.synchronisable' => 't',
        'pa.deleted' => 'f',
        'paw.deleted' => 'f',
        'paw.website_id' => $websiteId,
      ])
      // Forces the distinct on to prioritise non-deleted records.
      ->orderby(['pa.id' => 'ASC', 'pav.deleted' => 'ASC'])
      ->get()->result_array(FALSE);
    // Discard deletions if they are superceded by another non-deleted value.
    // Convert the diff type value fields into a single variant value.
    foreach ($attrs as &$attr) {
      if ($attr['deleted']) {
        $attr['value'] = NULL;
      }
      else {
        switch ($attr['data_type']) {
          case 'T':
            $attr['value'] = $attr['text_value'];
            break;

          case 'F':
            $attr['value'] = $attr['float_value'];
            break;

          case 'D':
          case 'V':
            $attr['value'] = $attr['date_start_value'];
            break;

          default:
            $attr['value'] = $attr['int_value'];
        }
      }
      unset($attr['text_value']);
      unset($attr['float_value']);
      unset($attr['date_value']);
      unset($attr['int_value']);
    }
    return $attrs;
  }

  /**
   * Creates a new user account.
   *
   * Uses the surname and first_name (if available) in the $_REQUEST.
   */
  private static function createUser($email, $userPersonObj) {
    $person = ORM::factory('person')
      ->where('deleted', 'f')
      // Use like as converts to ilike so case-insensitive.
      ->like('email_address', addcslashes($email, '%\\_'), FALSE)
      ->find();
    if ($person->loaded
        && ((!empty($person->first_name) && $person->first_name != '?'
        && !empty($_REQUEST['first_name']) && strtolower(trim($_REQUEST['first_name'])) !== strtolower(trim($person->first_name)))
        || strtolower(trim($person->surname)) !== strtolower(trim($_REQUEST['surname'])))) {
      throw new exception("The system attempted to use your user account details to register you as a user of the " .
          "central records database, but a different person with email address $email already exists. Please contact your " .
          "site administrator who may be able to help resolve this issue." . print_r($_REQUEST, TRUE));
    }
    $data = [
      'first_name' => isset($_REQUEST['first_name']) ? $_REQUEST['first_name'] : '?',
      'surname' => $_REQUEST['surname'],
      'email_address' => $email,
    ];
    if ($person->loaded) {
      $data['id'] = $person->id;
    }
    $person->validate(new Validation($data), TRUE);
    self::checkErrors($person);
    $user = ORM::factory('user');
    $data = [
      'person_id' => $person->id,
      'username' => $person->newUsername(),
      // User will not actually have warehouse access, so password fairly
      // irrelevant.
      'password' => 'P4ssw0rd',
    ];
    $user->validate(new Validation($data), TRUE);
    self::checkErrors($user);
    $userPersonObj->person_id = $person->id;
    return $user->id;
  }

  /**
   * Stores user_identifier records.
   *
   * For the list of identifiers passed through for a user, ensure they are all
   * persisted in the database.
   */
  private static function storeIdentifiers($userId, $identifiers, $userPersonObj, $websiteId) {
    // Build a list of all the identifier types we will need, to ensure that we
    // have terms for them.
    $typeTerms = [];
    foreach ($identifiers as $identifier) {
      if (!in_array($identifier->type, $typeTerms)) {
        $typeTerms[] = $identifier->type;
      }
    }
    // Now ensure the termlist is populated.
    $defaultLang = kohana::config('indicia.default_lang');
    foreach ($typeTerms as $term) {
      $qry = $userPersonObj->db->select('t.id')
        ->from('terms as t')
        ->join('termlists_terms as tlt', ['tlt.term_id' => 't.id'])
        ->join('termlists as tl', ['tl.id' => 'tlt.termlist_id'])
        ->where([
          't.deleted' => 'f',
          't.term' => $term,
          'tl.external_key' => 'indicia:user_identifier_types',
          'tlt.deleted' => 'f',
          'tl.deleted' => 'f',
        ])
        ->get()->result_array(FALSE);
      if (count($qry) === 0) {
        // Missing term so insert.
        $userPersonObj->db->query("SELECT insert_term('$term', '$defaultLang', null, 'indicia:user_identifier_types');");
      }
    }
    // Check each identifier to see if it already exists for the user.
    foreach ($identifiers as $identifier) {
      $r = $userPersonObj->db->select('ui.user_id')
        ->from('terms as t')
        ->join('termlists_terms as tlt1', ['tlt1.term_id' => 't.id'])
        ->join('termlists_terms as tlt2', ['tlt2.meaning_id' => 'tlt1.meaning_id'])
        ->join('user_identifiers as ui', ['ui.type_id' => 'tlt2.id'])
        ->where([
          't.term' => $identifier->type,
          'ui.user_id' => $userId,
          't.deleted' => 'f',
          'tlt1.deleted' => 'f',
          'tlt2.deleted' => 'f',
          'ui.deleted' => 'f',
        ])
        ->like('ui.identifier', $identifier->identifier)
        ->get()->result_array(FALSE);
      if (!count($r)) {
        // Identifier does not yet exist so create it.
        self::loadIdentifierTypes($userPersonObj);
        $new = ORM::factory('user_identifier');
        $data = [
          'user_id' => $userId,
          'type_id' => $userPersonObj->identifierTypes[$identifier->type],
          'identifier' => $identifier->identifier,
        ];
        $new->validate(new Validation($data), TRUE);
        self::checkErrors($new);
      }
      if ($identifier->type === 'email') {
        self::updateEmailAddress($identifier->identifier, $userPersonObj, $websiteId);
      }
    }
  }

  /**
   * Update stored email address for a person.
   *
   * When updating an email identifier, as this is the latest update copy it
   * into the person record and update all associated sample attribute values
   * from this website.
   */
  private static function updateEmailAddress($email, $userPersonObj, $websiteId) {
    // Check if email address has changed.
    $currentValCheck = $userPersonObj->db->select('email_address')
      ->from('people')
      ->where('id', $userPersonObj->person_id)
      ->get()->result_array(FALSE);
    // If changed, update the person record and any attribute data.
    if (count($currentValCheck) === 1 && $currentValCheck[0]['email_address'] !== $email) {
      $userPersonObj->db->update('people',
        ['email_address' => $email],
        ['id' => $userPersonObj->person_id]
      );
      // Update all sample attribute values matching other email addresses for
      // this account and linked to the user ID and website to this email.
      $userPersonObj->db->query(<<<QRY
update sample_attribute_values sav
set text_value=p.email_address
from people p
join users u on u.person_id=p.id and u.deleted=false
join user_identifiers ui on ui.user_id=u.id and ui.deleted=false
join cache_termlists_terms ctt on ctt.id=ui.type_id and ctt.term='email'
join samples s on s.created_by_id=u.id and s.deleted=false
join surveys su on su.id=s.survey_id and su.deleted=false and su.website_id=$websiteId
where p.id={$userPersonObj->person_id}
and sav.sample_id=s.id
and lower(sav.text_value)<>lower(p.email_address)
and lower(sav.text_value)=lower(ui.identifier)
QRY
      );
    }
  }

  /**
   * Loads the contents of the user identifier types termlist into a memory array, making it quicker to lookup.
   */
  private static function loadIdentifierTypes($userPersonObj) {
    if (!isset($userPersonObj->identifierTypes)) {
      $userPersonObj->identifierTypes = [];
      $terms = $userPersonObj->db
        ->select('termlists_terms.id, term')
        ->from('termlists_terms')
        ->join('terms', 'terms.id', 'termlists_terms.term_id')
        ->join('termlists', 'termlists.id', 'termlists_terms.termlist_id')
        ->where([
          'termlists.external_key' => 'indicia:user_identifier_types',
          'termlists_terms.deleted' => 'f',
          'terms.deleted' => 'f',
          'termlists.deleted' => 'f',
        ])
        ->orderby(['termlists_terms.sort_order' => 'ASC', 'terms.term' => 'ASC'])
        ->get();
      foreach ($terms as $term) {
        $userPersonObj->identifierTypes[$term->term] = $term->id;
      }
    }
  }

  /**
   * Check the errors in a model and throw an exception if there are any.
   */
  private static function checkErrors($model) {
    $errors = $model->getAllErrors();
    if (count($errors)) {
      kohana::log('debug', 'Errors on user identifier saved model: ' . print_r($errors, TRUE));
      throw new exception(print_r($errors, TRUE));
    }
  }

  /**
   * Create the associations between a user and the request website.
   *
   * Only created if the association does not already exist.
   */
  private static function associateWebsite($userId, $userPersonObj, $websiteId) {
    $qry = $userPersonObj->db->select('id')
      ->from('users_websites')
      ->where(['user_id' => $userId, 'website_id' => $websiteId])
      ->get()->result_array(FALSE);

    if (count($qry) === 0) {
      // Insert new join record.
      $uw = ORM::factory('users_website');
    }
    else {
      // Update existing.
      $uw = ORM::factory('users_website', $qry[0]['id']);
      if ($uw->site_role_id === 1 || $uw->site_role_id === 2) {
        // Don't bother updating, they are already admin or editor for this
        // site.
        return;
      }
    }
    $data = [
      'user_id' => $userId,
      'website_id' => $websiteId,
      'site_role_id' => 3,
    ];
    $uw->validate(new Validation($data), TRUE);
    self::checkErrors($uw);
  }

  /**
   * Store a user's sharing preferences.
   *
   * If there are sharing preferences in the $_REQUEST for this user account,
   * then stores them against the user record. E.g. the user might opt of
   * allowing other websites to pass on their records via the sharing
   * mechanism.
   */
  private static function storeSharingPreferences($userId, $userPersonObj) {
    if (isset($_REQUEST['shares_to_prevent'])) {
      // The request parameter is a comma separated list of the tasks this user
      // does not want to share their records with other sites for.
      $preventShares = explode(',', $_REQUEST['shares_to_prevent']);
      // Build an array of values to post to the db.
      $tasks = [
        'reporting',
        'peer_review',
        'verification',
        'data_flow',
        'moderation',
        'editing',
      ];
      $values = [];
      foreach ($tasks as $task) {
        $values["allow_share_for_$task"] = (in_array($task, $preventShares) ? 'f' : 't');
      }
      // Update their user record.
      $userPersonObj->db->update('users', $values, ['id' => $userId]);
    }
  }

  /**
   * Stores any changed custom attribute values supplied in the request data.
   *
   * Person attribute values are stored against the person associated with the
   * user.
   *
   * @param array $attrs
   *   Array of attribute & value data.
   * @param object $userPersonObj
   *   Object containing data including relating to the person/user.
   */
  private static function storeCustomAttributes(array &$attrs, $userPersonObj) {
    if (!empty($_REQUEST['attribute_values'])) {
      $valueData = json_decode($_REQUEST['attribute_values'], TRUE);
      if (count($valueData)) {
        $attrCaptions = array_keys($valueData);
        $pav = ORM::factory('person_attribute_value');
        // Loop through all the possible attributes to save the changed ones
        // from the client site.
        foreach ($attrs as &$attr) {
          // Ignore any attributes we don't have a change value for.
          if (in_array($attr['caption'], $attrCaptions)) {
            $valueFieldName = self::dataTypeToValueFieldName($attr['data_type']);
            $data = [
              'person_id' => $userPersonObj->person_id,
              'person_attribute_id' => $attr['id'],
              $valueFieldName => $valueData[$attr['caption']],
            ];
            // Store the attribute value we are saving in the array of
            // attributes, so the full updated list can be returned to the
            // client website.
            $attr['value'] = $valueData[$attr['caption']];
            if (!empty($attr['value_id'])) {
              $data['id'] = $attr['value_id'];
              $pav->find($attr['value_id']);
            }
            else {
              $pav->clear();
            }
            $pav->validate(new Validation($data), TRUE);
            self::checkErrors($pav);
          }
        }
      }
    }
  }

  /**
   * Convert a data type code to the attribute value table value field name.
   *
   * @param string $dataType
   *   Data type code ('T', 'I' etc).
   *
   * @return string
   *   Value field name ('text_value', 'int_value' etc).
   */
  private static function dataTypeToValueFieldName($dataType) {
    switch ($dataType) {
      case 'T':
        return 'text_value';

      case 'F':
        return 'float_value';

      case 'I':
      case 'L':
      case 'B':
        return 'int_value';

      case 'D':
      case 'V':
        return 'date_start_value';

      default:
        throw new exception('Unsupported data type code ' . $dataType);
    }
  }

  /**
   * Resolves multiple similar user accounts.
   *
   * Handle the case when multiple possible users are found for a list of
   * identifiers. Outcome depends on the settings in $_REQUEST, with options to
   * set force=merge or force=split. If not forced, then the list of possible
   * user IDs along with the websites they belong to are returned so the user
   * can consider the best action. If force=merge then users_to_merge can be set
   * to an array of user IDs that the merge applies to.
   */
  private static function resolveMultipleUsers($identifiers, $existingUsers, $userPersonObj) {
    if (isset($_REQUEST['force'])) {
      if ($_REQUEST['force'] === 'split') {
        $uid = self::findBestFit($identifiers, $existingUsers, $userPersonObj);
        return ['userId' => $uid];
      }
      elseif ($_REQUEST['force'] === 'merge') {
        $uid = self::findBestFit($identifiers, $existingUsers, $userPersonObj);
        // Merge the users into 1. A $_REQUEST['users_to_merge'] array can be
        // used to limit which are merged.
        self::mergeUsers($uid, $existingUsers, $userPersonObj);
        return ['userId' => $uid];
      }
    }
    else {
      // We need to propose that there are several possible existing users
      // which match the supplied identifiers to the client website.
      $users = array_keys($existingUsers);
      $userPersonObj->db->select('users_websites.user_id, users_websites.website_id, websites.title')
        ->from('websites')
        ->join('users_websites', 'users_websites.website_id', 'websites.id')
        ->join('users', 'users.id', 'users_websites.user_id')
        ->join('people', 'people.id', 'users.person_id')
        ->where([
          'websites.deleted' => 'f',
          'users.deleted' => 'f',
          'people.deleted' => 'f',
        ])
        ->where('users_websites.site_role_id is not null')
        ->in('users_websites.user_id', $users);
      if (isset($_REQUEST['users_to_merge'])) {
        $usersToMerge = json_decode($_REQUEST['users_to_merge']);
        $userPersonObj->db->in('users_websites.user_id', $usersToMerge);
      }
      return ['possibleMatches' => $userPersonObj->db->get()->result_array(FALSE)];
    }
  }

  /**
   * Finds best matching user.
   *
   * In the case where there are 2 users identified by a single list of
   * identifiers, resolve the situation. Returns the best matching user (based
   * on first_name and surname match then number of matching identifiers).
   *
   * @todo Note that in conjunction with this, a tool must be provided in the
   * warehouse for admin to check for and merge potential duplicate users.
   */
  private static function findBestFit($identifiers, $existingUsers, $userPersonObj) {
    $nameMatches = [];
    foreach ($identifiers as $identifier) {
      // Find all the existing users which match this identifier.
      $userPersonObj->db->select('ui.user_id, p.first_name, p.surname')
        ->from('users as u')
        ->join('people as p', 'p.id', 'u.person_id')
        ->join('user_identifiers as ui', 'ui.user_id', 'u.id')
        ->join('termlists_terms as tlt1', 'tlt1.id', 'ui.type_id')
        ->join('termlists_terms as tlt2', 'tlt2.meaning_id', 'tlt1.meaning_id')
        ->join('terms as t', 't.id', 'tlt2.term_id')
        ->where([
          't.term' => $identifier->type,
          'u.deleted' => 'f',
          'p.deleted' => 'f',
          'ui.deleted' => 'f',
          'tlt1.deleted' => 'f',
          'tlt2.deleted' => 'f',
          't.deleted' => 'f',
        ])
        ->like('ui.identifier', $identifier->identifier);
      if (isset($_REQUEST['users_to_merge'])) {
        $usersToMerge = json_decode($_REQUEST['users_to_merge']);
        $userPersonObj->db->in('ui.user_id', $usersToMerge);
      }
      $qry = $userPersonObj->db->get()->result();
      foreach ($qry as $match) {
        if (!isset($existingUsers[$match->user_id]['matches'])) {
          $existingUsers[$match->user_id]['matches'] = 1;
        }
        else {
          $existingUsers[$match->user_id]['matches'] = $existingUsers[$match->user_id]['matches'] + 1;
        }
        // Keep track of any exact name matches as they have priority.
        if ($match->first_name == (isset($_REQUEST['first_name']) ? $_REQUEST['first_name'] : '')
            && $match->surname == $_REQUEST['surname'] && !in_array($match->user_id, $nameMatches)) {
          $nameMatches[] = $match->user_id;
        }
      }
    }
    // Skim through the list of users to find the one that has the best fit. We
    // try any with a name match first.
    $bestFitUid = 0;
    $bestFitMatchCount = 0;
    foreach ($nameMatches as $uid) {
      if ($existingUsers[$uid]['matches'] > $bestFitMatchCount) {
        $bestFitUid = $uid;
        $bestFitMatchCount = $existingUsers[$uid]['matches'];
      }
    }
    // No need to check non-name matches if we've got something.
    if ($bestFitUid !== 0) {
      return $bestFitUid;
    }
    foreach ($existingUsers as $uid => $user) {
      if ($user['matches'] > $bestFitMatchCount) {
        $bestFitUid = $uid;
        $bestFitMatchCount = $user['matches'];
      }
    }
    // Now we know the user ID to keep.
    return $bestFitUid;
  }

  /**
   * Merge several user accounts into one.
   *
   * If a request is received with the force parameter set to merge, this means
   * we can merge the detected users into one.
   */
  private static function mergeUsers($uid, $existingUsers, $userPersonObj) {
    foreach ($existingUsers as $userIdToMerge => $websites) {
      if ($userIdToMerge != $uid && (!isset($_REQUEST['users_to_merge']) || in_array($userIdToMerge, $_REQUEST['users_to_merge']))) {
        // Own the occurrences.
        $userPersonObj->db->update('occurrences', ['created_by_id' => $uid], ['created_by_id' => $userIdToMerge]);
        $userPersonObj->db->update('occurrences', ['updated_by_id' => $uid], ['updated_by_id' => $userIdToMerge]);
        $userPersonObj->db->update('samples', ['created_by_id' => $uid], ['created_by_id' => $userIdToMerge]);
        $userPersonObj->db->update('samples', ['updated_by_id' => $uid], ['updated_by_id' => $userIdToMerge]);
        if (in_array(MODPATH . 'cache_builder', Kohana::config('config.modules'))) {
          $userPersonObj->db->update('cache_occurrences_functional', ['created_by_id' => $uid], ['created_by_id' => $userIdToMerge]);
          $userPersonObj->db->update('cache_samples_functional', ['created_by_id' => $uid], ['created_by_id' => $userIdToMerge]);
        }
        // Delete the old user.
        $uidsToDelete[] = $userIdToMerge;
        kohana::log('debug', "User merge operation resulted in deletion of user $userIdToMerge plus the related person");
      }
    }

    // Use the User Ids list to find a list of people to delete.
    $psnIds = $userPersonObj->db->select('person_id')->from('users')->in('id', $uidsToDelete)->get()->result_array();
    $pidsToDelete = [];
    foreach ($psnIds as $psnId) {
      $pidsToDelete[] = $psnId->person_id;
    }
    // Do the actual deletions.
    $userPersonObj->db->from('users')
      ->set([
        'deleted' => 't',
        'updated_on' => date("Ymd H:i:s"),
        'updated_by_id' => $uid,
      ])
      ->in('id', $uidsToDelete)->update();
    $userPersonObj->db->from('people')
      ->set([
        'deleted' => 't',
        'updated_on' => date("Ymd H:i:s"),
        'updated_by_id' => $uid,
      ])
      ->in('id', $pidsToDelete)->update();
  }

}
