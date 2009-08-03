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
 * @subpackage Views
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL
 * @link 	http://code.google.com/p/indicia/
 */

/**
 * Dump out a list of the config checks as either passes or fails, including a link to the
 * repair action if specified.
 */
foreach ($checks as $check) {
  if ($check['success']) {
    echo html::page_notice($check['title'], $check['description']);
  } else {
    echo html::page_error($check['title'], $check['description'],
      'Fix '.strtolower($check['title']).'...', url::base(true).'setup_check/'.$check['action']);
  }
}



