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
 * Model class for the Users_Websites table.
 */
class Users_website_Model extends ORM {

  private const APPLY_LICENCE_TASK = 'task_users_website_apply_licence';

  /**
   * Pending work queue params for deferred licence application.
   *
   * @var array|null
   */
  private $queuedLicenceUpdateParams = NULL;

  protected $has_one = [
    'user',
    'website',
    'site_role',
  ];
  protected $belongs_to = [
    'created_by' => 'user',
    'updated_by' => 'user'
  ];

  public function validate(Validation $array, $save = FALSE) {
    if ($save) {
      $check = $this->prepareLicenceApplicationTask($array->as_array());
      if (!$check['valid']) {
        $this->queuedLicenceUpdateParams = NULL;
        $this->errors[$check['field']] = $check['message'];
        return FALSE;
      }
      $this->queuedLicenceUpdateParams = $check['params'];
      kohana::log('debug', 'Prepared licence application task with params: ' . json_encode($this->queuedLicenceUpdateParams));
    }
    // Uses PHP trim() to remove whitespace from beginning and end of all
    // fields before validation.
    $array->pre_filter('trim');

    $this->unvalidatedFields = [
      'user_id',
      'website_id',
      'site_role_id',
      'licence_id',
      'media_licence_id',
    ];
    return parent::validate($array, $save);
  }

  /**
   * Queue deferred updates for existing records when default licences change.
   *
   * @param array $new
   *   New data being saved.
   *
   * @return array
   *   Validation result and queued task parameters.
   */
  private function prepareLicenceApplicationTask(array $new) {
    $userId = $new['user_id'] ?? $this->user_id ?? NULL;
    $websiteId = $new['website_id'] ?? $this->website_id ?? NULL;
    if (empty($userId) || empty($websiteId)) {
      return ['valid' => TRUE, 'params' => NULL];
    }

    $applyToExistingRecords = $this->isApplyToExistingDataRequested('apply_licence_to_existing_records');
    $applyToExistingMedia = $this->isApplyToExistingDataRequested('apply_licence_to_existing_media');
    $params = [
      'user_id' => (int) $userId,
      'website_id' => (int) $websiteId,
    ];

    $sampleLicencePlan = $this->buildLicenceChangePlan(
      $new['licence_id'] ?? NULL,
      $this->licence_id ?? NULL,
      $applyToExistingRecords,
      'licence_id',
      'sample/default licence'
    );
    if (!$sampleLicencePlan['valid']) {
      return $sampleLicencePlan;
    }
    if (!empty($sampleLicencePlan['mode'])) {
      $params['licence_id'] = (int) $new['licence_id'];
      $params['licence_mode'] = $sampleLicencePlan['mode'];
    }

    $mediaLicencePlan = $this->buildLicenceChangePlan(
      $new['media_licence_id'] ?? NULL,
      $this->media_licence_id ?? NULL,
      $applyToExistingMedia,
      'media_licence_id',
      'media default licence'
    );
    if (!$mediaLicencePlan['valid']) {
      return $mediaLicencePlan;
    }
    if (!empty($mediaLicencePlan['mode'])) {
      $params['media_licence_id'] = (int) $new['media_licence_id'];
      $params['media_licence_mode'] = $mediaLicencePlan['mode'];
    }

    if (empty($params['licence_mode']) && empty($params['media_licence_mode'])) {
      return ['valid' => TRUE, 'params' => NULL];
    }

    return ['valid' => TRUE, 'params' => $params];
  }

  /**
   * Determine if a licence change should apply to existing data records.
   *
   * @param string $fieldName
   *   Requested meta field name.
   *
   * @return bool
   *   True if metadata flag has requested applying to existing data.
   */
  private function isApplyToExistingDataRequested($fieldName) {
    if (!isset($this->submission['metaFields'][$fieldName])) {
      return FALSE;
    }
    $value = $this->submission['metaFields'][$fieldName];
    if (is_array($value) && array_key_exists('value', $value)) {
      $value = $value['value'];
    }
    if (is_bool($value)) {
      return $value;
    }
    return in_array(strtolower((string) $value), ['1', 'true', 't', 'yes', 'y', 'on'], TRUE);
  }

  /**
   * Build queue mode for a changed licence field and validate permissiveness.
   *
   * @param mixed $newValue
   *   Submitted value.
   * @param mixed $oldValue
   *   Current value in database.
   * @param bool $applyToExistingData
   *   Should existing records be updated when safe.
   * @param string $field
   *   Model field name for reporting validation errors.
   * @param string $label
   *   Human readable field description for messages.
   *
   * @return array
   *   Validation status plus chosen mode if applicable.
   */
  private function buildLicenceChangePlan($newValue, $oldValue, $applyToExistingData, $field, $label) {
    $newId = empty($newValue) ? NULL : (int) $newValue;
    $oldId = empty($oldValue) ? NULL : (int) $oldValue;
    if ($newId === NULL || $newId === $oldId) {
      return ['valid' => TRUE, 'mode' => NULL];
    }
    if ($oldId === NULL) {
      // First assignment: keep legacy behaviour and fill only records lacking a licence.
      return ['valid' => TRUE, 'mode' => 'empty'];
    }
    if (!$applyToExistingData) {
      // Default changed for new records only.
      return ['valid' => TRUE, 'mode' => NULL];
    }
    $permissiveCheck = $this->validatePermissivenessChange($oldId, $newId, $label);
    if (!$permissiveCheck['valid']) {
      return [
        'valid' => FALSE,
        'field' => $field,
        'message' => $permissiveCheck['message'],
      ];
    }
    return ['valid' => TRUE, 'mode' => 'all'];
  }

  /**
   * Ensure a licence change moves from restrictive to more permissive.
   *
   * @param int $oldLicenceId
   *   Existing licence ID.
   * @param int $newLicenceId
   *   New licence ID.
   * @param string $label
   *   Human readable context for error messages.
   *
   * @return array
   *   Validation status and optional message.
   */
  private function validatePermissivenessChange($oldLicenceId, $newLicenceId, $label) {
    $rows = $this->db->query(
      'SELECT id, permissiveness_sort_order FROM licences WHERE deleted=false AND id IN (?, ?)',
      [$oldLicenceId, $newLicenceId]
    )->result();
    $orders = [];
    foreach ($rows as $row) {
      $orders[(int) $row->id] = $row->permissiveness_sort_order;
    }
    if (!array_key_exists($oldLicenceId, $orders) || !array_key_exists($newLicenceId, $orders)) {
      return [
        'valid' => FALSE,
        'message' => "Unable to validate $label change because one or both licences could not be found.",
      ];
    }
    if ($orders[$oldLicenceId] === NULL || $orders[$newLicenceId] === NULL) {
      return [
        'valid' => FALSE,
        'message' => "Unable to validate $label change because permissiveness_sort_order is not defined for one or both licences.",
      ];
    }
    if ((int) $orders[$newLicenceId] <= (int) $orders[$oldLicenceId]) {
      return [
        'valid' => FALSE,
        'message' => "Cannot apply $label to existing data unless it is more permissive than the current licence.",
      ];
    }
    return ['valid' => TRUE];
  }

  /**
   * Queue any required licence propagation task after successful save.
   *
   * @param bool $isInsert
   *   True if this was an insert.
   *
   * @return bool
   *   Always true.
   */
  public function postSubmit($isInsert) {
    if (!empty($this->queuedLicenceUpdateParams)) {
      $q = new WorkQueue();
      $q->enqueue($this->db, [
        'task' => self::APPLY_LICENCE_TASK,
        'entity' => 'users_website',
        'record_id' => $this->id,
        'params' => json_encode($this->queuedLicenceUpdateParams),
        'cost_estimate' => 70,
        'priority' => 2,
      ]);
      $this->queuedLicenceUpdateParams = NULL;
    }
    return TRUE;
  }

  /**
   * Apply default notification email settings for the website to the user.
   */
  public function addEmailSettings() {
    if (in_array(MODPATH . 'notification_emails', kohana::config('config.modules'))) {
      $sql = <<<SQL
INSERT INTO user_email_notification_settings(user_id, notification_source_type, notification_frequency,
  created_on, created_by_id, updated_on, updated_by_id)
SELECT DISTINCT $this->user_id, w.notification_source_type, w.notification_frequency,
  now(), $this->user_id, now(), $this->user_id
FROM website_email_notification_settings w
LEFT JOIN user_email_notification_settings u
  ON u.user_id=$this->user_id
  AND u.notification_source_type=w.notification_source_type
  AND u.notification_frequency=w.notification_frequency
  AND u.deleted=false
WHERE w.website_id=$this->website_id
AND w.deleted=false;
SQL;
      $this->db->query($sql);
    }
  }

}
