<?php defined('SYSPATH') or die('No direct script access.');

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
 * @author Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link https://github.com/indicia-team/warehouse
 */

/**
 * Model class for the rest_api_client_connectionss table.
 */
class Rest_api_client_connection_Model extends ORM {

  protected $belongs_to = [
    'rest_api_client' => 'rest_api_client',
    'created_by' => 'user',
    'updated_by' => 'user',
  ];

  public function validate(Validation $array, $save = FALSE) {
    $array->pre_filter('trim');
    $array->add_rules('title', 'required');
    $array->add_rules('rest_api_client_id', 'required', 'integer');
    $array->add_rules('sharing', 'chars[R,V,D,M,P]');
    $array->add_rules('es_sensitivity_blur', 'required', 'chars[B,F]');
    $this->unvalidatedFields = [
      'description',
      'es_endpoint',
      'es_bool_query',
      'allow_reports',
      'limit_to_reports',
      'allow_data_resources',
      'limit_to_data_resources',
      'read_only',
      'allow_confidential',
      'allow_sensitive',
      'allow_unreleased',
    ];
    return parent::validate($array, $save);
  }

  /**
   * Handle field value conversion.
   *
   * E.g. form may submit report limits from a text area so convert to array.
   */
  protected function preSubmit() {
    if (isset($this->submission['fields']['limit_to_reports'])) {
      if (!empty($this->submission['fields']['limit_to_reports']['value'])) {
        $reportsList = str_replace("\r\n", "\n", $this->submission['fields']['limit_to_reports']['value']);
        $reportsList = str_replace("\r", "\n", $reportsList);
        $reportsList = explode("\n", trim($reportsList));
        $this->submission['fields']['limit_to_reports'] = ['value' => $reportsList];
      }
      else {
        // Specified but empty, so null it out.
        $this->submission['fields']['limit_to_reports'] = ['value' => NULL];
      }
    }
  }

}
