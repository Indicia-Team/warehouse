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
 * Controller providing CRUD access to the list of titles for people.
 *
 * @package	Core
 * @subpackage Controllers
 */
class Title_Controller extends Gridview_Base_Controller {

  /**
     * Constructor
     */
  public function __construct()
  {
    parent::__construct('title', 'title', 'title/index');
    $this->columns = array(
      'title'=>''
      );
    $this->pagetitle = "Titles";
    $this->model = ORM::factory('title');
  }

  /**
   * Action for title/create page/
   * Displays a page allowing entry of a new title.
   */
  public function create()
  {
    if (!$this->page_authorised())
    {
      $this->access_denied();
    }
    else
    {
          $this->setView('title/title_edit', 'Title');
    }
  }

    /**
     * Action for title/edit page
     * Edit person title data
     */
  public function edit($id  = null)
  {
    if (!$this->page_authorised())
    {
      $this->access_denied();
    }
    else if ($id == null)
        {
         $this->setError('Invocation error: missing argument', 'You cannot call edit a title without an ID');
        }
        else
        {
            $this->model = new Title_Model($id);
            $this->setView('title/title_edit', 'Title');
        }
  }

  public function page_authorised ()
  {
    return $this->auth->logged_in('CoreAdmin');
  }
}
?>
