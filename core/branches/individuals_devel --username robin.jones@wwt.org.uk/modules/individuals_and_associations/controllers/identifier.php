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
 * @package Core
 * @subpackage Controllers
 * @author	Indicia Team
 * @link http://code.google.com/p/indicia/
 * @license http://www.gnu.org/licenses/gpl.html GPL
 */

/**
 * Controller for the identifier page.
 *
 * @package Groups and individuals module
 * @subpackage Controllers
 */
class Identifier_Controller extends Gridview_Base_Controller {

  /**
   * Constructor
   */
  public function __construct()
  {
    parent::__construct('identifier');
    $this->columns = array(
      'id'=>'ID',
      'first_use_date'=>'',
      'identifier_type'=>'',
      'status'=>'',
      'coded_value'=>'',
      'summary'=>'',
      'short_description'=>'Subject description',
    );
    $this->pagetitle = "Identifiers";
  }

  public function page_authorised()
  {
    return $this->auth->logged_in('CoreAdmin') || $this->auth->has_any_website_access('editor');
  }
  
  protected function getModelValues() {
    $r = parent::getModelValues();
    // load data for attributes
    $websiteId = $r['identifier:website_id'];
    $this->loadAttributes($r, array(
        'website_id'=>$websiteId,
    ));
    return $r;      
  }
  
  /**
   * Load default values either when creating a sample new or reloading after a validation failure.
   * This adds the custom attributes list to the data available for the view. 
   */
  protected function getDefaults() {
    $r = parent::getDefaults();
    if (array_key_exists('identifier:id', $_POST)) {
      $websiteId = $r['identifier:website_id'];
      $this->loadAttributes($r, array(
        'website_id'=>$websiteId,
      ));
    }
    return $r;
  }

  /**
   * Get the list of terms ready for the type lists. 
   */
  protected function prepareOtherViewData($values)
  {    
    return array(
      'issue_authority_terms' => $this->get_termlist_terms('indicia:assoc:issue_authority'), 
      'issue_scheme_terms' => $this->get_termlist_terms('indicia:assoc:issue_scheme'), 
      'identifier_type_terms' => $this->get_termlist_terms('indicia:assoc:identifier_type'),  
      'status_options' => array('M' => 'Manufactured', 'I' => 'Issued', 'A' => 'Attached', 'R' => 'Retired', 'U' => 'Unknown'),  
    );   
  }

}
?>
