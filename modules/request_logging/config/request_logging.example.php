<?php

/**
 * @file
 * Configuration for the request logger.
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
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link https://github.com/indicia-team/warehouse/
 */

$config['logged_requests'] = [
  // Read (output) data.
  'o.report',
  // Read (output) data.
  'o.data',
  // Any update to data (input).
  'i.data',
  // Other data actions.
  'a.data',
  // Imports.
  'i.import',
  // Scheduled tasks.
  'a.scheduled_tasks',
  // Security (e.g. get_user_id).
  'a.security',
  // REST API
  'o.rest',
  'i.rest',
];
