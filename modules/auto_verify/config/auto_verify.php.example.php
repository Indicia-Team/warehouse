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

$config['auto_accept_occurrences_with_null_id_difficulty'] = 'true';

// Note that -1 (or less) is unlimited, 0 processes nothing.
$config['max_num_records_to_process_at_once'] = 0;
$config['oldest_record_created_date_to_process'] = '01/01/2000';

// 1 (or less) is effectively unlimited.
$config['oldest_occurrence_id_to_process'] = 1;