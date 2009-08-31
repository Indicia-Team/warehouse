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
 * @package	Modules
 * @subpackage setup_check
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL
 * @link 	http://code.google.com/p/indicia/
 */

class setup_check {

  public static function _setup_check() {
    // Perform some system checks
    $uri = URI::instance();
    $isOk = $uri->segment(1) == 'setup' || $uri->segment(1) == 'setup_check' ||
        count(config_test::check_config(true))==0;
    if (!$isOk) {
      url::redirect('setup_check');
    }
  }

}

 Event::add('system.pre_controller', array('setup_check', '_setup_check'));

 ?>