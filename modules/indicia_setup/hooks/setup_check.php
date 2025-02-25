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
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link https://github.com/indicia-team/warehouse/
 */
class setup_check {

  public function __construct() {
    // Hook into routing, but not if running unit tests.
    if (!defined('inPhpUnit')) {
      Event::add('system.routing', array($this, 'setupCheck'));
    }
  }

  /**
   * If not in the setup pages, but the indicia config file is missing, go to system setup.
   */
  public static function setupCheck() {
    $uri = URI::instance();
    $isOk = $uri->segment(1) == 'setup' || $uri->segment(1) == 'setup_check' ||
        kohana::config('indicia.private_key', FALSE, FALSE) !== NULL;
    if (!$isOk) {
      url::redirect('setup_check');
    }
  }

}

new setup_check();
