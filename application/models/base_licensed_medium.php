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
 * Base class for the models which represent a media file that has a licence.
 *
 * @link http://indicia-docs.readthedocs.io/en/latest/developing/data-model.html
 */
class Base_licensed_medium_Model extends ORM {

  /**
   * Files uploaded by REST API's media queue pending transfer to upload dir.
   *
   * @var string
   */
  private $queuedFile;

  /**
   * Fill in licence link and media type default before submission.
   *
   * If a submission is for an insert and does not contain the licence ID for
   * the data it contains, look it up from the user's settings and apply it to
   * the submission. If the media_type_id is not populated, default to
   * the ID for image:local.
   */
  protected function preSubmit() {
    if (!array_key_exists('id', $this->submission['fields']) || empty($this->submission['fields']['id']['value'])) {
      $userId = $this->getUserId();
      // Set user's default media licence unless already specified in the
      // submission.
      if (isset($userId) && (empty($this->submission['fields']['licence_id']) || empty($this->submission['fields']['licence_id']['value']))) {
        $row = $this->db
          ->select('media_licence_id')
          ->from('users_websites')
          ->where([
            'user_id' => $userId,
            'website_id' => $this->identifiers['website_id'],
          ])
          ->get()->current();
        if ($row) {
          $this->submission['fields']['licence_id']['value'] = $row->media_licence_id;
        }
      }
      // Now fill in the media_type_id if not in submission.
      if (!array_key_exists('media_type_id', $this->submission['fields'])
          || empty($this->submission['fields']['media_type_id']['value'])) {
        $cache = Cache::instance();
        if ($cached = $cache->get('image-local-media_type_id')) {
          $mediaTypeId = $cached;
        }
        else {
          $mediaTypeId = $this->db
            ->select('id')
            ->from('cache_termlists_terms')
            ->where([
              'termlist_title' => 'Media types',
              'term' => 'Image:Local',
            ])
            ->get()->current()->id;
          $cache->set('image-local-media_type_id', $mediaTypeId);
        }
        $this->submission['fields']['media_type_id'] = ['value' => $mediaTypeId];
      }
    }
    // If using a queued media file, store this in the path. After validation
    // we'll copy it over.
    if (!empty($this->submission['fields']['queued'])) {
      $this->submission['fields']['path'] = $this->submission['fields']['queued'];
      $this->queuedFile = $this->submission['fields']['queued']['value'];
    }
  }

  /**
   * Overreide Validate() to add check that any queued file exists.
   */
  public function validate(Validation $array, $save = FALSE) {
    $r = TRUE;
    // If a queued file in submission, check it exists.
    if (isset($this->queuedFile)) {
      if (!file_exists(DOCROOT . 'upload-queue/' . $this->queuedFile)) {
        $this->errors['queued'] = "Requested file does not exist in the queue: $this->queuedFile";
        $r = FALSE;
      }
    }
    return $r && parent::validate($array, $save);
  }

  /**
   * Override postSubmit to copy queued file from queue to final destination.
   */
  public function postSubmit($isInsert) {
    if (isset($this->queuedFile)) {
      $queuedFile = DOCROOT . 'upload-queue/' . $this->queuedFile;
      // Recreate the sub-directories based on the timestamp.
      $subdir = dirname($this->queuedFile);
      $destDir = Kohana::config('upload.directory', TRUE) . $subdir;
      if (!is_dir($destDir)) {
        mkdir($destDir, 0755, TRUE);
      }
      $destFile = $destDir . '/' . basename($this->queuedFile);
      $r = rename($queuedFile, $destFile);
      if (!$r) {
        $this->errors['queued'] = "Failed to move queued file to upload folder: $this->queuedFile";
      }
      // Create thumbnails and other versions.
      Image::create_image_files(Kohana::config('upload.directory', TRUE), basename($this->queuedFile), $subdir, $this->identifiers['website_id']);
    }
    return TRUE;
  }

}
