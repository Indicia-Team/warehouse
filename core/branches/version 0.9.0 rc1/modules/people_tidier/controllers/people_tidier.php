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
 * @package	People Tidier
 * @subpackage Controllers
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL
 * @link 	http://code.google.com/p/indicia/
 */

/**
 * Controller class for the taxon designations plugin module.
 */
class People_tidier_Controller extends Indicia_Controller {

  /**
   * Index controller method for the Tidy tab. Loads the appropriate details into the template from the view.
   * Not available unless core admin.
   */
  public function index() {
    if (!$this->auth->logged_in('CoreAdmin'))
      $this->template->content = 'This facility allows the warehouse administrator to merge people who have accidentally been registered '.
          'separately as separate people in the database because they registered on several different client websites. Please contact the '.
          'warehouse administrator for more information.';
    else {
      $this->view = new View('people_tidier/index');
      $personId = $this->uri->last_segment();
      $this->view->currentPersonPanel = $this->get_person_panel($personId);
      $this->view->personId=$personId;
      $this->template->content = $this->view;
    }
  }
  
  /**
   * AJAX handler to return a details panel for a person.
   */
  public function person_panel($id) {
    $this->auto_render=false;
    if ($this->auth->logged_in('CoreAdmin') || $this->auth->has_any_website_access('admin'))
      echo $this->get_person_panel($id);
  }
  
  /**
   * Controller action for merging 2 peopls, who should be identified in the post data. 
   * Once complete, redirects back to the index of people.
   */
  public function merge_people() {
    $this->do_person_merge();
    $this->session->set_flash('flash_info', 'The people database records have been merged into 1.');
    url::redirect('person/index');
  }
  
  /**
   * Performs the task of merging people (and associated user data if there is any)
   */
  private function do_person_merge() {
    if (!isset($_POST['selected-person-id']) || !isset($_POST['found-person-id'])) 
      throw new exception('This page has not been accessed with the correct parameters for the people to merge.');
    if (isset($_POST['keep-selected'])) {
      $keepId=$_POST['selected-person-id'];
      $loseId=$_POST['found-person-id'];
    }
    elseif (isset($_POST['keep-found'])) {
      $keepId=$_POST['found-person-id'];
      $loseId=$_POST['selected-person-id'];
    }
    else
      throw new exception('This page has not been accessed with the correct parameters for the merge method.');
    $keepUser = $this->db->select('id')->from('users')->where(array('person_id'=>$keepId,'deleted'=>'f'))->limit(1)->get()->result_array();
    $loseUser = $this->db->select('id')->from('users')->where(array('person_id'=>$loseId,'deleted'=>'f'))->limit(1)->get()->result_array();
    if (count($loseUser)) {
      $loseUser = array_pop($loseUser);
      // The person we are losing has a user, therefore they may have some records which they will not want to lose.
      if (count($keepUser)) {
        $keepUser = array_pop($keepUser);
        // switch records between the 2 users
        $this->db->update('occurrences', array('created_by_id'=>$keepUser->id), array('created_by_id'=>$loseUser->id));
        $this->db->update('occurrences', array('updated_by_id'=>$keepUser->id), array('updated_by_id'=>$loseUser->id));
        // Ensure the identifiers are copied over
        $identifiers = $this->db->select('id, identifier, type_id')
          ->from('user_identifiers')
          ->where('user_id', $loseUser->id)
          ->get()->result();
        foreach ($identifiers as $identifier) {
          // Check if this identifier already exists
          $exists = $this->db->select('count(*) as count')
            ->from('user_identifiers')
            ->where(array('user_id' => $keepUser->id, 'identifier'=>$identifier->identifier, 'type_id'=>$identifier->type_id))
            ->get()->result_array(false);
          // If this identifier does not exist for the keep user, then move it across
          if ($exists[0]['count']==0) 
            $this->db->from('user_identifiers')->set(array('user_id'=>$keepUser->id))->where('id', $identifier->id)->update();
        }
        // Ensure the websites are copied over
        $websites = $this->db->select('id, website_id')
          ->from('users_websites')
          ->where('user_id', $loseUser->id)
          ->get()->result();
        foreach ($websites as $website) {
          // Check if this website already exists for the kept user
          $exists = $this->db->select('count(*) as count')
            ->from('users_websites')
            ->where(array('user_id' => $keepUser->id, 'website_id'=>$website->website_id))
            ->get()->result_array(false);
          // If this identifier does not exist for the keep user, then move it across
          if ($exists[0]['count']==0) 
            $this->db->from('users_websites')->set(array('user_id'=>$keepUser->id))->where('id', $website->id)->update();
        }
        // Delete the user
        $this->db->from('users')->set(array('deleted'=>'t'))->where('id', $loseUser->id)->update();
      }
      // The lose person has a user, the keep person does not, so we can point the lose user to the keep person.
      $this->db->from('users')->set(array('person_id'=>$keepId))->where('id', $loseUser->id)->update();
    }
    $this->db->from('people')->set(array('deleted'=>'t'))->where('id', $loseId)->update();
  }
  
  /** 
   * Builds an HTML table containing the details of a person. Used to populate the view directly and
   * also provides the response for AJAX call.s
   */
  private function get_person_panel($id) {
    $person = ORM::Factory('person', $id);
    $name=array();
    if (!empty($person->title_id))       
      $name[] = $person->title->title;
    if (!empty($person->first_name))
      $name[]=$person->first_name;
    elseif (!empty($person->initials))
      $name[]=$person->initials;
    $name[]=$person->surname;
    $details = array('ID' => $person->id, 'Name'=>implode(' ',$name));
    if (!empty($person->email_address))
      $details['Email Address']=$person->email_address;
    if (!empty($person->address)) {
      $address = str_replace("\r\n", "<br/>", $person->address);
      $address = str_replace(array("\r","\n"), "<br/>", $address);
      $details['Address']=$address;
    }
    if (!empty($person->website_url))
      $details['Peronsal Website']=$person->website_url;
    if (!empty($person->external_key))
      $details['External Key']=$person->external_key;
    $r = "<table>\n";
    foreach ($details as $attr => $value) 
      $r .= "<tr><td><strong>$attr</strong></td><td>$value</td></tr>\n";
    $r .= '</table>';
    return $r;
  }

}

?>