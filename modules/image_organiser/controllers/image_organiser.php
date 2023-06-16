<?php

/**
 * @file
 * Controller for an image file re-organiser tool's UI.
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

 const BATCH_SIZE = 100;

 class EAbort extends Exception {};

/**
 * Controller class for an image organiser tool.
 */
class Image_organiser_Controller extends Indicia_Controller {

  /**
   * Image organiser index page has simple UI to trigger processes.
   */
  public function index() {
    $this->template->title = 'Image organiser';
    $this->template->content = new View('image_organiser/index');
    $this->template->siteUrl = url::site();
  }

  /**
   * Ajax controller to process a batch of image relocations.
   */
  public function process_relocate_batch() {
    // No template as this is for AJAX.
    $this->auto_render = FALSE;
    header('Content-Type: application/javascript');
    if (!$this->checkLogstashOk()) {
      echo json_encode([
        'status' => 'Paused',
        'reason' => 'Logstash has too many pending updates so pausing.',
      ]);
      return;
    }
    $moveFrom = variable::get('image_organiser_tracking', 'occurrence:0', FALSE);
    [$entity, $fromId] = explode(':', $moveFrom);
    $batchSize = BATCH_SIZE;
    $baseEntityIds = [];
    $entityIdField = "{$entity}_id";
    $qry = <<<SQL
SELECT id, path, occurrence_id, created_on
FROM {$entity}_media
WHERE id>$fromId
AND deleted=false
AND media_type_id=(SELECT id FROM cache_termlists_terms WHERE term='Image:Local' AND termlist_title='Media types')
AND path NOT LIKE 'http%'
-- Not already done.
AND NOT path ~ '^\d\d[\\\\\\/]'
ORDER BY id LIMIT $batchSize;
SQL;
    $images = $this->db->query($qry);
    $directory = Kohana::config('upload.directory', TRUE);
    $lastId = $fromId;
    $successCount = 0;
    foreach ($images as $image) {
      $subdir = $this->getImageSubdir($image->created_on);
      if (!is_dir($directory . $subdir)) {
        kohana::log('debug', "Creating Directory $directory$subdir");
        mkdir($directory . $subdir, 0755, TRUE);
      }
      if (!is_dir($directory . 'thumb-' . $subdir)) {
        kohana::log('debug', "Creating Directory $directory$subdir");
        mkdir($directory . 'thumb-' . $subdir, 0755, TRUE);
      }
      if (!is_dir($directory . 'med-' . $subdir)) {
        kohana::log('debug', "Creating Directory $directory$subdir");
        mkdir($directory . 'med-' . $subdir, 0755, TRUE);
      }
      $src = $directory . $image->path;
      $dest = $directory . $subdir . $image->path;
      // File already moved, or copy successful.
      if (!file_exists($src)) {
        $this->log($entity, $image, 'Main image file missing');
      }
      elseif (file_exists($dest) || copy($src, $dest)) {
        // Do the path update. We don't update the metadata, as we don't want
        // to fire the cache builder (we can rebuild just the images more
        // efficiently).
        $sql = <<<SQL
UPDATE {$entity}_media SET path = '$subdir$image->path' WHERE id=$image->id;
SQL;
        $this->db->query($sql);
        $successCount++;
        // Track the unique base entity (e.g. occurrence or sample) IDs so we
        // can update them.
        $baseEntityIds[$image->$entityIdField] = $image->$entityIdField;
        if (!file_exists($directory . 'thumb-' . $image->path)) {
          $this->log($entity, $image, 'Thumb image file missing');
        }
        elseif (!file_exists($directory . 'thumb-' . $subdir . $image->path) && !copy($directory . 'thumb-' . $image->path, $directory . 'thumb-' . $subdir . $image->path)) {
          $this->log($entity, $image, 'Thumb image file failed to copy');
        };
        if (!file_exists($directory . 'med-' . $image->path)) {
          $this->log($entity, $image, 'Med image file missing');
        }
        elseif (!file_exists($directory . 'med-' . $subdir . $image->path) && !copy($directory . 'med-' . $image->path, $directory . 'med-' . $subdir . $image->path)) {
          $this->log($entity, $image, 'Med image file failed to copy');
        };
      }
      else {
        $this->log($entity, $image, 'Main image file failed to copy');
      };
      $lastId = $image->id;
    }
    $this->updateCacheMedia($entity, $baseEntityIds);
    $trackingVar = "occurrence:$lastId";
    variable::set('image_organiser_tracking', $trackingVar);
    echo json_encode([
      'status' => count($images) > 0 ? 'OK' : 'Done',
      'moved' => $successCount,
      'entity' => 'occurrence',
      'id' => $lastId,
    ]);
  }

  /**
   * Ajax controller to process a batch of file deletions.
   *
   * For images that have already been copied to their new location, after 3
   * hours it should be safe to delete the original file, as the client website
   * caches will have been updated in most scenarios.
   */
  public function process_delete_batch() {
    // No template as this is for AJAX.
    $this->auto_render = FALSE;
    $deleteFrom = variable::get('image_organiser_tracking_deletes', 'occurrence:0', FALSE);
    [$entity, $fromId] = explode(':', $deleteFrom);
    $batchSize = BATCH_SIZE;
    $qry = <<<SQL
SELECT m.id, m.path, m.created_on, m.updated_on
FROM {$entity}_media m
LEFT JOIN image_organiser_problems p ON p.media_id=m.id AND p.entity='$entity'
WHERE m.id>$fromId
AND m.deleted=false
AND m.media_type_id=(SELECT id FROM cache_termlists_terms WHERE term='Image:Local' AND termlist_title='Media types')
AND m.path NOT LIKE 'http%'
AND p.id IS NULL
ORDER BY m.id LIMIT $batchSize;
SQL;
    $images = $this->db->query($qry);
    $lastId = $fromId;
    $successCount = 0;
    $abortReason = NULL;
    try {
      foreach ($images as $image) {
        /*if (strtotime($image->updated_on) > strtotime('-3 hours')) {
          throw new EAbort("Aborting as image less than 3 hours since update: {$entity}_media $image->id");
        }*/
        if (!preg_match('/^\d\d[\\\\\\/]/', $image->path)) {
          throw new EAbort("Aborting as image path not properly processed: {$entity}_media $image->id");
        }
        $this->deleteOldImageFile($entity, $image, '');
        $this->deleteOldImageFile($entity, $image, 'thumb');
        $this->deleteOldImageFile($entity, $image, 'med');
        $successCount++;
        $lastId = $image->id;
      }
    }
    catch (EAbort $e) {
      $abortReason = $e->getMessage();
    }
    $trackingVar = "occurrence:$lastId";
    variable::set('image_organiser_tracking_deletes', $trackingVar);
    header('Content-Type: application/javascript');
    echo json_encode([
      'status' => empty($abortReason) ? (count($images) > 0 ? 'OK' : 'Done') : 'Failed',
      'deleted' => $successCount,
      'entity' => 'occurrence',
      'id' => $lastId,
      'reason' => $abortReason,
    ]);
  }

  /**
   * Add a log entry to the problems table.
   *
   * @param string $entity
   *   E.g. occurrence or sample.
   * @param object $image
   *   Image data from the db.
   * @param string $problem
   *   Problem text to log.
   */
  private function log($entity, $image, $problem) {
    $userId = $_SESSION['auth_user']->id;
    $sql = <<<SQL
INSERT INTO image_organiser_problems (problem, media_id, entity, created_on, created_by_id)
VALUES ('$problem', $image->id, '$entity', now(), $userId);
SQL;
    $this->db->query($sql);
  }

  /**
   * Deletes the original copy of an image file.
   *
   * @param string $entity
   *   E.g. occurrence or sample.
   * @param object $image
   *   Image data from the db.
   * @param string $imageSize
   *   Empty string for original image, or 'thumb', 'med' etc.
   */
  private function deleteOldImageFile($entity, $image, $imageSize) {
    $imageSizePrefix = $imageSize === '' ? '' : "$imageSize-";
    $directory = Kohana::config('upload.directory', TRUE);
    // Strip sub-folders to find original image location.
    $originalPath = preg_replace('/^\d\d[\\\\\\/]\d\d[\\\\\\/]\d\d[\\\\\\/]/', '', $image->path);
    $src = $directory . $imageSizePrefix . $originalPath;
    $dest = $directory . $imageSizePrefix . $image->path;
    kohana::log('debug', 'Looking to delete ' . $src);
    if (file_exists($src)) {
      if (file_exists($dest)) {
        if (!unlink($src)) {
          throw new EAbort("Aborting as failed to delete image $src for: {$entity}_media $image->id");
        }
      }
      else {
        throw new EAbort("Aborting as path modified but destination file $dest missing: $image->id");
      }
    }
  }

  /**
   * Converts a file date to a sub-directory structure for the file.
   *
   * @param string $fileDate
   *   Image created on value, as a string.
   *
   * @return string
   *   Sub-folder structure, e.g. '60/20/15/', including trailing slash.
   */
  private function getImageSubdir($fileDate) {
    $subdir = '';
    // $levels = Kohana::config('upload.use_sub_directory_levels');
    $levels = 3;
    $ts = strtotime($fileDate);
    for ($i = 0; $i < $levels; $i++) {
      $dirname = substr($ts, 0, 2);
      if (strlen($dirname)) {
        $subdir .= $dirname . '/';
        $ts = substr($ts, 2);
      }
    }
    return $subdir;
  }

  /**
   * Check if the work queue not too long, indicating we should pause.
   *
   * @return bool
   *   True if queue length < 20000.
   */
  private function checkLogstashOk() {
    $todo = $this->db->query("select (select max(tracking) from cache_occurrences_functional) - (select (value::json->0->>'last_tracking_id')::integer from variables where name = 'rest-autofeed-BRC5') as todo")
      ->current()->todo;
    return $todo < 20000;
  }

  /**
   * Update the media paths in the cache tables.
   */
  private function updateCacheMedia($entity, array $baseEntityIds) {
    if (count($baseEntityIds) === 0) {
      return;
    }
    if ($entity !== 'sample' && $entity !== 'occurrence') {
      return;
    }
    $ids = implode(',', $baseEntityIds);
    $qry = <<<SQL
UPDATE cache_{$entity}s_nonfunctional nf
SET media=(
  SELECT array_to_string(array_agg(m.path), ',')
  FROM {$entity}_media m WHERE m.occurrence_id=nf.id AND m.deleted=false
)
FROM {$entity}s e
WHERE e.id=nf.id
AND e.deleted=false
AND e.id IN ($ids)
SQL;
    $this->db->query($qry);
  }

}
