<?php

/**
 * @file
 * Plugin for the Process Checker warehouse module.
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
 * @link https://github.com/indicia-team/warehouse/
 */

function process_checker_scheduled_task($last_run_date, $db) {
  $processChecker = new ProcessChecker();
  $config = Kohana::config('process_checker.checks', TRUE, FALSE);
  if (empty($config)) {
    kohana::log('alert', 'Process Checker warehouse module is enabled but not configured.');
    return;
  }
  foreach ($config as $title => $processItem) {
    $processChecker->process($db, $title, $processItem);
  }
}
