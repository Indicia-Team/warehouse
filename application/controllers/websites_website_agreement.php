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
 * @package	Core
 * @subpackage Controllers
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL
 * @link 	http://code.google.com/p/indicia/
 */

/**
 * Controller providing CRUD access to the list of websites in agreements.
 *
 * @package	Core
 * @subpackage Controllers
 */
class Websites_website_agreement_Controller extends Gridview_Base_Controller
{
  /**
   * Constructor
   */
  public function __construct()
  {
    parent::__construct('websites_website_agreement');

    $this->columns = array(
        'id'          => 'ID',
        'website'     => '',
        'agreement'   => ''
    );

    $this->pagetitle = "Agreements for website";
    $this->set_website_access('admin');
  }
  
  /**
  * Override the default index functionality to filter by website.
  */
  public function index()
  {
    $website_id = $this->uri->argument(1);
    $this->base_filter['website_id'] = $website_id;
    parent::index();
  }

  /**
   * Returns an array of all values from this model and its super models ready to be
   * loaded into a form. For this controller, we need to double up the password field.
   */
  protected function getModelValues() {
    $r = parent::getModelValues();
    return $r;
  }

  /**
   *  Setup the default values to use when loading this controller to edit a new page.
   */
  protected function getDefaults() {
    $r = parent::getDefaults();
    if ($this->uri->method(false)=='create') {
      // Website id is passed as first argument in URL when creating
      $r['websites_website_agreement:website_id'] = $this->uri->argument(1);
    }
    return $r;
  }

  
  /**
   * Website agreements only editable by core admin or admin of website.
   */
  public function record_authorised ($id) {
    if ($this->auth->logged_in('CoreAdmin'))
      return true;
    else {
      if (!is_null($this->auth_filter))
      {
        $wwa = new Websites_Website_Agreement_Model($id);
        return (in_array($wwa->website_id, $this->auth_filter['values']));
      }
    }
    // should not get here as auth_filter populated if not core admin
    return false;
  }

  /**
   * Core admin can see the list of websites
   */
  public function page_authorised() {
    return $this->auth->logged_in('CoreAdmin') || $this->auth->has_any_website_access('admin');
  }
  
  /**
   * Define non-standard behaviour for the breadcrumbs, since this is accessed via a website list
   */
  protected function defineEditBreadcrumbs() {
    $this->page_breadcrumbs[] = html::anchor('website', 'Websites');
    if ($this->model->id) {
      // editing an existing item, so our argument is the website_id
      $websiteId = $this->model->website_id;
    } else {
      // creating a new one so our argument is the website id
      $websiteId = $this->uri->argument(1);
    }
    $websiteTitle = ORM::Factory('website', $websiteId)->title;
  	$this->page_breadcrumbs[] = html::anchor('website/edit/'.$websiteId.'?tab=Agreements', $websiteTitle);
	  $this->page_breadcrumbs[] = $this->model->caption();
  }
  
  /**
   * Override the default return page behaviour so that after saving an agreement participation you
   * are returned to the list of agreements on the sub-tab of the website.
   */
  protected function get_return_page() {
    if (array_key_exists('websites_website_agreement:website_id', $_POST)) {
      // after saving a record, the website id to return to is in the POST data
      // user may select to continue adding new terms
      if (isset($_POST['what-next'])) {
        if ($_POST['what-next']=='add')
          return 'websites_website_agreement/create/'.$_POST['websites_website_agreement:website_id'];
      }
      // or just return to the website page
      return "website/edit/".$_POST['websites_website_agreement:website_id']."?tab=Agreements";
    } elseif (array_key_exists('websites_website_agreement:website_id', $_GET))
      // after uploading records, the website id is in the URL get parameters
      return "website/edit/".$_GET['websites_website_agreement:website_id']."?tab=Agreements";
    else
      // last resort if we don't know the list, just show the whole lot of agreements
      return $this->model->object_name;
  }

}

?>
