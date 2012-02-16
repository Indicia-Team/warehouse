<?php defined('SYSPATH') or die('No direct script access.');
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
 * @package Services
 * @author  Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL 3.0
 * @link  http://code.google.com/p/indicia/
 */

/**
 * Controller for service calls relating to the use of user identifiers to associated
 * client website users with warehouse user accounts. An example usage of this is
 * to use a twitter account identifier to identify a single user across multiple client
 * websites.
 */
class User_Identifier_Controller extends Service_Base_Controller {
  protected $db;
  
  /**
   * Service method that takes list of user identifiers and returns the appropriate user ID
   * from the warehouse, which can then be used in subsequent calls to save the data.
   * Takes a GET or POST parameter called identifiers, with a JSON encoded array of identifiers
   * known for the user. Each array entry is an object with a type (e.g. twitter, openid) and
   * identifier (e.g. twitter account). There should also be a surname parameter
   * enabling the new user account to be created, plus an optional first_name, and
   * a cms_user_id that can be used to identify previously existing records for the user.
   * @return integer The user ID for the existing or newly created account. Alternatively
   * if more than one match is found, then returns a JSON encoded lists of people that 
   * match from the warehouse - each with the first name, surname and list of known
   * identifiers. If this happens then the client must ask the user to confirm that they 
   * are the same person and if so, the response is sent back with a forcemerge=true
   * parameter to force the merge of the people. 
   */
  public function get_user_id() {
    if (!array_key_exists('identifiers', $_REQUEST))
      throw new exception('Error: missing identifiers parameter');
    $identifiers = json_decode($_REQUEST['identifiers']);
    if (!is_array($identifiers))
      throw new Exception('Error: identifiers parameter not of correct format');
    if (!isset($_REQUEST['surname']))
      throw new exception('Call to get_user_id requires a surname in the GET or POST data.');
    // We don't need a website_id in the request as the authentication data contains it, but
    // we do need to know the cms_user_id so that we can ensure any previously recorded data for
    // this user is attributed correctly to the warehouse user.
    if (!isset($_REQUEST['cms_user_id']))
      throw new exception('Call to get_user_id requires a cmsa_user_id in the GET or POST data.');
    try {
      // authenticate requesting website for this service. This can create a user, so need write
      // permission.
      $this->authenticate('write');
      $newIdentifiers = array();
      $existingUsers = array();
      // work through the list of identifiers and find the users for the ones we already know about, 
      // plus find the list of identifiers we've not seen before.
      $this->db = new Database();
      // email is a special identifier used to create person.
      $email = null;
      foreach ($identifiers as $identifier) {
        $r = $this->db->select('user_id')
            ->from('user_identifiers as um')
            ->join('termlists_terms as tlt1', array('tlt1.id'=>'um.type_id'))
            ->join('termlists_terms as tlt2', array('tlt2.meaning_id'=>'tlt1.meaning_id'))
            ->join('terms as t', array('t.id'=>'tlt2.term_id'))
            ->where(array('um.identifier'=>$identifier->identifier, 't.term'=>$identifier->type))
            ->get()->result_array(true);
        foreach($r as $existingUser) {
          // create a placeholder for the known user we just found
          if (!isset($existingUsers[$existingUser->user_id]))
            $existingUsers[$existingUser->user_id]=array();
          // add the identifier detail to this known user
          $existingUsers[$existingUser->user_id][] = array('identifier'=>$identifier->identifier,'type'=>$identifier->type);
        }
        // store the email address, since this is always required to create a person
        if ($identifier->type==='email')
          $email=$identifier->identifier;
      }
      // Now we have a list of the existing users that match this identifier. If there are none, we 
      // can create a new user and attach to the current website. If there is one, then we can
      // just return it. If more than one, then we have a resolution task since it probably
      // means 2 user records refer to the same physical person, or someone is sharing their
      // identifiers!
      if (count($existingUsers)===0)
        $userId = $this->createUser($email);
      elseif (count($existingUsers)===1) {
        // single, known user associated with these identifiers
        $keys = array_keys($existingUsers);
        $userId = array_pop($keys);
      }
      if (isset($userId)) {
        $this->storeIdentifiers($userId, $identifiers);
        $this->associateWebsite($userId);
      } else {
        $userId = $this->resolveMultipleUsers($identifiers, $existingUsers);
      }
      echo $userId;
      // Update the created_by_id for all records that were created by this cms_user_id. This 
      // takes ownership of the records.
      postgreSQL::setOccurrenceCreatorByCmsUser($this->db, $this->website_id, $userId, $_REQUEST['cms_user_id']);
    
    }
    catch (Exception $e) {
      $this->handle_error($e);
    }
  }
  
  /**
   * Creates a new user account using the surname and first_name (if available)
   * in the $_REQUEST.
   */
  private function createUser($email) {
    $person = ORM::factory('person')->where(array('email_address'=>$email))->find();
    if ($person->loaded
        && ((!empty($person->first_name) && $person->first_name != '?'
        && !empty($_REQUEST['first_name']) && $_REQUEST['first_name']!==$person->first_name)
        || $person->surname !== $_REQUEST['surname']))
      throw new exception("The system attempted to use your user account details to register you as a user of the ".
          "central records database, but a different person with email address $email already exists. Please contact your ".
          "site administrator who may be able to help resolve this issue." . print_r($_REQUEST, true));
    $data = array(
      'first_name' => isset($_REQUEST['first_name']) ? $_REQUEST['first_name'] : '?',
      'surname' => $_REQUEST['surname'],
      'email_address'=>$email
    );
    if ($person->loaded)
      $data['id']=$person->id;
    $person->validate(new Validation($data), true);
    $this->checkErrors($person);
    $user = ORM::factory('user');
    // ensure a unique username
    $unique=0;
    do {    
      $username = $person->first_name.'_'. $person->surname.($unique===0 ? '' : '_'.$unique);
      $unique++;
    } while ($this->db->select('id')->from('users')->where(array('username'=>$username))->get()->count()>0);
    
    $data = array(
      'person_id'=>$person->id,
      'email_visible'=>'f',
      'username'=>$username,
      // User will not actually have warehouse access, so password fairly irrelevant
      'password' => 'P4ssw0rd',
    );
    $user->validate(new Validation($data), true);
    $this->checkErrors($user);
    return $user->id;
  }
  
  /**
   * For the list of identifiers passed through to the web service for a user, ensure they are all 
   * persisted in the database. 
   */
  private function storeIdentifiers($userId, $identifiers) {
    // build a list of all the identifier types we will need, to ensure that we have terms for them.
    $typeTerms = array();
    foreach ($identifiers as $identifier) {
      if (!in_array($identifier->type, $typeTerms)) 
        $typeTerms[]=$identifier->type;
    }
    // now ensure the termlist is populated
    $defaultLang = kohana::config('indicia.default_lang');
    foreach ($typeTerms as $term) {
      $qry = $this->db->select('t.id')
          ->from('terms as t')
          ->join('termlists_terms as tlt', array('tlt.term_id'=>'t.id'))
          ->join('termlists as tl', array('tl.id'=>'t.id'))
          ->where(array('t.deleted'=>'f', 't.term'=>$term, 'tl.external_key'=>'indicia:user_identifier_types',
               'tlt.deleted'=>'f', 'tl.deleted'=>'f'))
          ->get()->result_array(false);
      if (count($qry)===0) {
        // missing term so insert
        $this->db->query("SELECT insert_term('$term', '$defaultLang', null, 'indicia:user_identifier_types');");
      }
    }
    foreach ($identifiers as $identifier) {
      $r = $this->db->select('ui.user_id')
          ->from('terms as t')
          ->join('termlists_terms as tlt1', array('tlt1.term_id'=>'t.id'))
          ->join('termlists_terms as tlt2', array('tlt2.meaning_id'=>'tlt1.meaning_id'))
          ->join('user_identifiers as ui', array('ui.type_id'=>'tlt2.id'))
          ->where(array(
              't.term'=>$identifier->type, 
              'ui.user_id' => $userId,
              'ui.identifier' => "'".$identifier->identifier."'",
              't.deleted' => 'f',
              'tlt1.deleted' => 'f',
              'tlt2.deleted' => 'f',
              'ui.deleted' => 'f'))
          ->get()->result_array(false);
      if (!count($r)) {
        // identifier does not yet exist so create it
        $this->loadIdentifierTypes();
        $new=ORM::factory('user_identifier');
        $data = array(
          'user_id'=>$userId,
          'type_id'=>$this->identifierTypes[$identifier->type],
          'identifier'=>$identifier->identifier
        );
        $new->validate(new Validation($data), true);
        $this->checkErrors($new);
      }
    }    
  }
  
  /**
   * Loads the contents of the user identifier types termlist into a memory array, making it quicker to lookup.
   */
  private function loadIdentifierTypes() {
    if (!isset($this->identifierTypes)) {
      $this->identifierTypes=array();
      $terms = $this->db
        ->select('termlists_terms.id, term')
        ->from('termlists_terms')
        ->join('terms', 'terms.id', 'termlists_terms.term_id')
        ->join('termlists', 'termlists.id', 'termlists_terms.termlist_id')
        ->where(array('termlists.external_key' => 'indicia:user_identifier_types', 'termlists_terms.deleted' => 'f', 'terms.deleted' => 'f', 'termlists.deleted'=>'f'))
        ->orderby(array('termlists_terms.sort_order'=>'ASC', 'terms.term'=>'ASC'))
        ->get();
      foreach ($terms as $term) {
        $this->identifierTypes[$term->term] = $term->id;
      }
    }
  }
  
  /**
   * Check the errors in a model and throw an exception if there are any.
   */
  private function checkErrors($model) {
    $errors = $model->getAllErrors();
    if (count($errors))
      throw new exception(print_r($errors, true));
  }
  
  /**
   * Create the associations between a user and the website that this service call was made on,
   * if the association does not already exist.
   */
  private function associateWebsite($userId) {
    $qry = $this->db->select('id')
        ->from('users_websites')
        ->where(array('user_id'=>$userId, 'website_id'=>$this->website_id))
        ->get()->result_array(false);
        
    if (count($qry)===0)
      // insert new join record
      $uw=ORM::factory('users_website');
    else
      // update existing
      $uw=ORM::factory('users_website', $qry[0]['id']);
    $data = array(
      'user_id'=>$userId,
      'website_id'=>$this->website_id,
      'site_role_id'=>3
    );
    $uw->validate(new Validation($data), true);
    $this->checkErrors($uw);
  }
  
  /**
   * In the case where there are 2 users identified by a single list of identifiers, 
   * resolve the situation. Returns the best matching user (based on first_name and surname match then
   * number of matching identifiers). 
   * @todo Note that in conjunction with this, a tool must be provided in the warehouse for admin to 
   * check for and merge potential duplicate users.
   */
  private function resolveMultipleUsers($identifiers, $existingUsers) {
    foreach ($identifiers as $identifier) {
      // find all the existing users which match this identifier.
      $qry=$this->db->select('ui.user_id, p.first_name, p.surname')
          ->from('users as u')
          ->join('people as p', 'p.id', 'u.person_id')
          ->join('user_identifiers as ui', 'ui.user_id', 'u.id')
          ->join('termlists_terms as tlt1', 'tlt1.id', 'ui.type_id')
          ->join('termlists_terms as tlt2', 'tlt2.meaning_id', 'tlt1.meaning_id')
          ->join('terms as t', 't.id', 'tlt2.term_id')
          ->where(array('t.term'=>$identifier->type,
              'u.deleted'=>'f', 'p.deleted'=>'f', 'ui.deleted'=>'f', 'tlt1.deleted'=>'f', 'tlt2.deleted'=>'f', 't.deleted'=>'f'))
          ->get()->result();
      $nameMatches = array();
      
      foreach($qry as $match) {
        if (!isset($existingUsers[$match->user_id]['matches']))
          $existingUsers[$match->user_id]['matches']=1;
        else 
          $existingUsers[$match->user_id]['matches']=$existingUsers[$match->user_id]['matches']+1;
        // keep track of any exact name matches as they have priority
        if ($match->first_name==(isset($_REQUEST['first_name']) ? $_REQUEST['first_name'] : '')
            && $match->surname==$_REQUEST['surname'] && !in_array($match->user_id, $nameMatches))
          $nameMatches[] = $match->user_id;
      }
      
      // Skim through the list of users to find the one that has the best fit. We try any with a name match
      // first.
      $bestFitUid = 0;
      $bestFitMatchCount = 0;
      foreach ($nameMatches as $uid) {
        if ($existingUsers[$uid]['matches']>$bestFitMatchCount) {
          $bestFitUid=$uid;
          $bestFitMatchCount=$existingUsers[$uid]['matches'];
        }
      }
      // No need to check non-name matches if we've got something.
      if ($bestFitUid!==0)
        return $bestFitUid;
      foreach ($existingUsers as $uid=>$user) {
        if ($user['matches']>$bestFitMatchCount) {
          $bestFitUid=$uid;
          $bestFitMatchCount=$user['matches'];
        }
      }
      return $bestFitUid;
    }
  }

}