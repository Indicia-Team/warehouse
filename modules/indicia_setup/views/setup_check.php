<?php

/**
 * @file
 * Setup check page view.
 *
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
 * @author Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link http://code.google.com/p/indicia/
 */

/*
 * Dump out a list of the config checks as either passes or fails, including a link to the
 * repair action if specified.
 */

// First grab a count of the failures.
$failures = 0;
foreach ($checks as $check) {
  if (!$check['success']) {
    $failures++;
  }
}
if ($failures > 0) {
  foreach ($checks as $check) {
    if ($check['success']) {
      // Check allows install to proceed, but can still contain a warning.
      if (isset($check['warning'])) {
        echo html::page_notice($check['title'], $check['description'], 'warning', 'alert');
      }
      else {
        echo html::page_notice($check['title'], $check['description'], 'success', 'ok-sign');
      }
    }
    else {
      if (array_key_exists('action', $check)) {
        echo html::page_notice($check['title'], $check['description'], 'danger', 'minus-sign',
            $check['action']['title'], url::base(TRUE) . 'setup_check/' . $check['action']['link']);
      }
      else {
        echo html::page_notice($check['title'], $check['description'], 'danger', 'minus-sign');
      }
    }
  }
}
else {
  echo html::page_notice(
    'Database installed',
    'The database has been successfully installed. There are just a couple more steps required to complete the ' .
    'installation. You now need to log in to the warehouse to bring the database up to the latest version. ' .
    'An admin account has been created for you with username=admin and no password and you will be asked to enter ' .
    'a password when you first log in.',
    'success',
    'ok',
    'Proceed to the login page',
    url::base() . 'index.php'
  );
}
