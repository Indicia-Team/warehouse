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
 * @link http://code.google.com/p/indicia/
 * @license http://www.gnu.org/licenses/gpl.html GPL
 */

/**
 * Controller for the language page.
 *
 * @package Core
 */
class Language_Controller extends Gridview_Base_Controller {

  /**
     * Constructor
     */
  public function __construct()
  {
    parent::__construct('language', 'language', 'language/index');
    $this->columns = array(
      'iso'=>'',
      'language'=>'');
    $this->pagetitle = "Languages";
    $this->model = ORM::factory('language');
  }

  /**
   * Action for language/create page/
   * Displays a page allowing entry of a new language.
   */
  public function create()
  {
    if (!$this->page_authorised())
    {
      $this->access_denied();
    }
    else
    {
          $this->setView('language/language_edit', 'Language');
    }
  }

    /**
     * Action for language/edit page
     * Edit website data
     */
  public function edit($id  = null)
  {
    if (!$this->page_authorised())
    {
      $this->access_denied();
    }
    else if ($id == null)
        {
         $this->setError('Invocation error: missing argument', 'You cannot call edit language without an ID');
        }
        else
        {
            $this->model = new Language_Model($id);
            $this->setView('language/language_edit', 'language');
        }
  }

  public function page_authorised ()
  {
    return $this->auth->logged_in('CoreAdmin');
  }
}
?>
