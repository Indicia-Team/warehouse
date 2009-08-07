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
 * Controller providing CRUD access to the list of websites registered on this Warehouse instance.
 *
 * @package	Core
 * @subpackage Controllers
 */
class Website_Controller extends Gridview_Base_Controller
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct('website', 'website', 'website/index');

        $this->columns = array('title'       =>'',
                               'description' =>'',
                               'url' =>'' );

        $this->pagetitle = "Websites";
    $this->model = ORM::factory('website');
    }

    /**
     * Action for website/create page.
     * Displays a page allowing entry of a new website.
     *
     * @todo auth and permission check
     *       May you should rename this methode to "new". I find "create" a bit confusing
     *       because this methode create nothing but just loads a view with empty form fields
     */
    public function create()
    {
    if (!$this->page_authorised())
    {
      $this->access_denied();
    }
    else
    {
        $this->setView('website/website_edit', 'Website');
    }
    }

    /**
     * Edit website data
     *
     * @todo auth and permission check
     */
    public function edit($id = null)
    {
    if (!$this->page_authorised())
    {
      $this->access_denied();
    }
    else if ($id == null)
        {
         $this->setError('Invocation error: missing argument', 'You cannot call edit website without an ID');
        }
        else
        {
            $this->model = new Website_Model($id);
            $this->model->password2 = $this->model->password;
            $this->setView('website/website_edit', 'Website');
        }
    }

    protected function show_submit_fail() {
      $this->model->password2 = $_POST['password2'];
      $mn = $this->model->object_name;
      $this->setView($mn."/".$mn."_edit", ucfirst($mn));
    }

  public function page_authorised ()
  {
    return $this->auth->logged_in('CoreAdmin');
  }
}

?>
