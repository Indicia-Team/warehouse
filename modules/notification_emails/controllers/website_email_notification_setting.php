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

/**
 * Controller for website email notification_settings.
 */
class Website_email_notification_setting_Controller extends Indicia_Controller {

  /**
   * Controller action for the email settings edit form.
   */
  public function index() {
    $view = new View('website_email_notification_setting/edit');
    $website_id = (int) $this->uri->last_segment();
    if ($website_id <= 0) {
      throw new Kohana_404_Exception('The page you requested could not be found.');
    }
    $data = [];
    $rows = $this->db->select('notification_source_type, notification_frequency')
      ->from('website_email_notification_settings')
      ->where([
        'deleted' => 'f',
        'website_id' => $website_id,
      ])->get();
    foreach ($rows as $row) {
      $data[$row->notification_source_type] = $row->notification_frequency;
    }
    $view->website_id = $website_id;
    $view->data = $data;
    $this->template->content = $view;
  }

  public function save() {
    $this->set_website_access('editor');
    $website_id = (int) ($_POST['website_id'] ?? 0);
    $this->auto_render = FALSE;
    if ($website_id <= 0) {
      http_response_code(400);
      echo json_encode(['status' => 400, 'msg' => 'Invalid website ID']);
      return;
    }
    if (!is_null($this->auth_filter) && $this->auth_filter['field'] === 'website_id') {
      $allowedWebsiteIds = array_map('intval', $this->auth_filter['values']);
      if (!in_array($website_id, $allowedWebsiteIds, TRUE)) {
        http_response_code(401);
        echo json_encode(['status' => 401, 'msg' => 'Unauthorized']);
        return;
      }
    }
    $allowedFrequencies = ['IH', 'D', 'W'];
    $types = [
      'V',
      'Q',
      'RD',
      'C',
      'A',
      'T',
      'S',
      'VT',
      'M',
      'PT',
    ];
    foreach ($types as $type) {
      $postedFrequency = $_POST[$type] ?? '';
      if (!empty($postedFrequency) && !in_array($postedFrequency, $allowedFrequencies, TRUE)) {
        http_response_code(400);
        echo json_encode(['status' => 400, 'msg' => 'Invalid notification frequency']);
        return;
      }
      $obj = ORM::factory('website_email_notification_setting')->find([
        'website_id' => $website_id,
        'notification_source_type' => $type,
        'deleted' => 'f',
      ]);
      if (empty($postedFrequency) && !$obj->loaded) {
        // No value in DB and no value to save for this type.
        continue;
      }
      if (!empty($postedFrequency)) {
        // Either a new setting, or update the existing found one.
        $obj->website_id = $website_id;
        $obj->notification_source_type = $type;
        $obj->notification_frequency = $postedFrequency;
      }
      else {
        // An existing setting needs to be deleted.
        $obj->deleted = TRUE;
      }
      $obj->set_metadata();
      $obj->save();
      if (count($obj->getAllErrors())) {
        http_response_code(500);
        echo json_encode(['status' => 500, 'msg' => 'Internal server error']);
        return;
      }
    }
    echo json_encode(['status' => 200, 'msg' => 'Ok']);
  }

}
