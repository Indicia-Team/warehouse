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
 * @package    Modules
 * @subpackage Verification_templates
 * @author     Indicia Team
 * @license    http://www.gnu.org/licenses/gpl.html GPL
 * @link       https://github.com/Indicia-Team/
 */

/**
 * Controller providing CRUD access to the surveys list.
 */
class Verification_template_Controller extends Gridview_Base_Controller {

  public function __construct() {
    parent::__construct('verification_template');
    $this->columns = array(
        'id' => 'ID',
        'title' => 'Title',
        'template_statuses' => 'Statuses',
    );
    $this->pagetitle = 'Verification_templates definition';
  }

  /**
   * Prevent users accessing Verification_templates if they are not core admin or an editor on a website.
   * @return boolean True if access granted.
   */
  protected function page_authorised() {
    return $this->auth->logged_in('CoreAdmin') || $this->auth->has_any_website_access('editor');
  }

  /**
   * Verification_templates only editable by core admin or editor of the website associated with the template.
   * @return boolean True if access granted.
   */
  public function record_authorised($id) {
    if ($this->auth->logged_in('CoreAdmin')) {
      return TRUE;
    } else {
      if (!is_null($this->auth_filter) && $this->auth_filter['field'] === 'website_id') {
        $vt = new Verification_template_Model($id);
        return (in_array($vt->website_id, $this->auth_filter['values']));
      }
    }
    // Should not get here as auth_filter populated if not core admin.
    return FALSE;
  }

  /**
   * Prepare additional information for the edit view.
   *
   * This converts the array fields into values suitable for a textarea.
   *
   * @param array $values
   *   Existing data values for the view.
   *
   * @return array
   *   Array of additional data items required.
   */
  protected function prepareOtherViewData(array $values) {
    // $values can be empty, or populated from either the database, or from a failed POST
    // For the failed post, the special fields/formats already exist.
    if (isset($values['verification_template:restrict_to_external_keys_list'])) {
      $restrictToExternalKeysList = $values['verification_template:restrict_to_external_keys_list'];
    }
    else {
      $restrictToExternalKeysList = implode("\n", self::array_parse(html::initial_value($values, 'verification_template:restrict_to_external_keys')));
    }
    if (isset($values['verification_template:restrict_to_family_external_keys_list'])) {
      $restrictToFamilyExternalKeysList = $values['verification_template:restrict_to_family_external_keys_list'];
    }
    else {
      $restrictToFamilyExternalKeysList = implode("\n", self::array_parse(html::initial_value($values, 'verification_template:restrict_to_family_external_keys_list')));
    }
    if (isset($values['verification_template:template_statuses']) && is_array($values['verification_template:template_statuses'])) {
      $templateStatuses = $values['verification_template:template_statuses'];
    }
    else {
      $templateStatuses = self::array_parse(html::initial_value($values, 'verification_template:template_statuses'));
    }
    $websites = ORM::factory('website');
    if (!$this->auth->logged_in('CoreAdmin') && $this->auth_filter['field'] === 'website_id') {
      $websites = $websites->in('id', $this->auth_filter['values']);
    }
    $arr = array();
    foreach ($websites->where('deleted', 'false')->orderby('title', 'asc')->find_all() as $website) {
      $arr[$website->id] = $website->title;
    }
    // Convert the status into an array.
    // convert the 2 arrays for the keys from the postgres format string to a value that can be used on the form
    return array(
      'websites' => $arr,
      'restrict_to_external_keys_list' => $restrictToExternalKeysList,
      'restrict_to_family_external_keys_list' => $restrictToFamilyExternalKeysList,
      'template_statuses' => $templateStatuses,
    );
  }

  /**
   * Parse a single dimension postgres array represented as a string into a PHP array.
   *
   * @param string $s Postgres array represented as a string.
   * @return array Array.
   */
  function array_parse($s, $start = 0, &$end = null)
  {
    if (empty($s) || $s[0] != '{') return array();
    $return = array();
    $string = false;
    $quote='';
    $s = str_replace('&quot;','"',$s);
    $len = strlen($s);
    $v = '';
    for ($i = $start + 1; $i < $len; $i++) {
      $ch = $s[$i];
      if (!$string && $ch == '}') {
        if ($v !== '' || !empty($return)) {
          $return[] = $v;
        }
        $end = $i;
        break;
      } elseif (!$string && $ch == ',') {
        $return[] = $v;
        $v = '';
      } elseif (!$string && ($ch == '"' || $ch == "'")) {
        $string = true;
        $quote = $ch;
      } elseif ($string && $ch == $quote && $s[$i - 1] == "\\") {
        $v = substr($v, 0, -1) . $ch;
      } elseif ($string && $ch == $quote && $s[$i - 1] != "\\") {
        $string = false;
      } else {
        $v .= $ch;
      }
    }
    return $return;
  }
}
