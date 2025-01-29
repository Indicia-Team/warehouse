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
 * @license	http://www.gnu.org/licenses/gpl.html GPL
 * @link 	https://github.com/indicia-team/warehouse/
 */

 /**
  * Simple controller class to output the log browser view.
  */
class Browse_server_logs_Controller extends Indicia_Controller {

  /**
   * Index is the default method for the controller.
   */
  public function index() {
    // Get a list of the available logs.
    if ($dir = opendir(DOCROOT . 'application/logs')) {
      while (false !== ($file = readdir($dir))) {
        if (substr($file, 0, 1)!=='.') {
          $files[$file] = str_replace('.log.php', '', $file);
        }
      }
    }
    // put the most recent first
    arsort($files);
    $this->template->title='Browse server logs';
    $this->template->content = new View('browse_server_logs');
    $this->template->content->files = $files;
  }

}