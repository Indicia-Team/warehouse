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
 * @link https://github.com/indicia-team/warehouse
 */

defined('SYSPATH') or die('No direct script access.');

/**
 * Model class for the notifications table.
 */
class Notification_Model extends ORM {

  protected $has_many = ['taxa'];

  protected $belongs_to = ['user'];

  public function validate(Validation $array, $save = FALSE) {
    // Uses PHP trim() to remove whitespace from beginning and end of all
    // fields before validation.
    $array->pre_filter('trim');
    $array->add_rules('source', 'required');
    $array->add_rules('source_type', 'required', 'regex[/^(A|C|GU|M|PT|Q|RD|S|T|V|VT)$/]');
    $array->add_rules('digest_mode', 'regex[/^[NDWI]$/]');
    $array->add_rules('data', 'required');
    $array->add_rules('acknowledged', 'required');
    $array->add_rules('user_id', 'required');
    $array->add_rules('triggered_on', 'required');
    $this->unvalidatedFields = [
      'cc',
      'linked_id',
    ];
    return parent::validate($array, $save);
  }

}
