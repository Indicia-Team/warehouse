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
 * Model class for the rest_api_clients table.
 */
class Rest_api_client_Model extends ORM {

  protected $belongs_to = [
    'website' => 'website',
    'created_by' => 'user',
    'updated_by' => 'user',
  ];

  protected $has_many = ['rest_api_client_connections'];

  public function validate(Validation $array, $save = FALSE) {
    $array->pre_filter('trim');
    $array->add_rules('title', 'required');
    $array->add_rules('username', 'required');
    $array->add_rules('website_id', 'required', 'integer');
    $array->add_rules('secret', 'length[7,30]', 'matches_post[secret2]');
    $this->unvalidatedFields = [
      'description',
      'public_key',
    ];
    return parent::validate($array, $save);
  }

  /**
   * Hashes the saved secrets using a one-way algorithm.
   *
   * @param string $key
   *   Field name.
   * @param mixed $value
   *   Value being set.
   */
  public function __set($key, $value) {
    if ($key === 'secret') {
      if (empty($value) && !empty($this->id)) {
        // Don't overwrite secret with empty value if not changing secret.
        return;
      }
      else {
        $value = password_hash($value, PASSWORD_DEFAULT);
      }
    }
    parent::__set($key, $value);
  }

}
