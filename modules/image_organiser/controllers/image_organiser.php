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

 const BATCH_SIZE = 1000;

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
    $entity = $_POST['type'];
    if (!preg_match('/^[a-z_]*$/', $entity)) {
      throw new exception('Invalid type parameter');
    }
    if ($entity === 'occurrence' && !$this->checkLogstashOk()) {
      echo json_encode([
        'status' => 'Paused',
        'reason' => 'Logstash has too many pending updates so pausing.',
      ]);
      return;
    }
    $fromId = (int) variable::get("image_organiser_tracking_$entity", 0, FALSE);
    $batchSize = BATCH_SIZE;
    $baseEntityIds = [];
    $entityIdField = $entity === 'taxon' ? 'taxon_meaning_id' : "{$entity}_id";
    $qry = <<<SQL
SELECT id, path, {$entityIdField}, created_on, deleted
FROM {$entity}_media
WHERE id>$fromId
AND media_type_id=(SELECT id FROM cache_termlists_terms WHERE term='Image:Local' AND termlist_title='Media types')
AND path NOT LIKE 'http%'
-- Not already done unless deleted as may need to be moved to deleted subdir.
AND (path !~ '^\d\d[\\\\\\/]' or deleted=true)
ORDER BY id LIMIT $batchSize;
SQL;
    $images = $this->db->query($qry);
    $uploadDir = Kohana::config('upload.directory', TRUE);
    $lastId = $fromId;
    $successCount = 0;
    foreach ($images as $image) {
      $subdir = $this->getImageSubdir($image);
      if (!is_dir($uploadDir . $subdir)) {
        kohana::log('debug', "Creating Directory $destDir$subdir");
        mkdir($uploadDir . $subdir, 0755, TRUE);
      }
      if (!is_dir($uploadDir . 'thumb-' . $subdir)) {
        kohana::log('debug', "Creating Directory {$destDir}thumb-$subdir");
        mkdir($uploadDir . 'thumb-' . $subdir, 0755, TRUE);
      }
      if (!is_dir($uploadDir . 'med-' . $subdir)) {
        kohana::log('debug', "Creating Directory {$destDir}med-$subdir");
        mkdir($uploadDir . 'med-' . $subdir, 0755, TRUE);
      }
      $src = $uploadDir . $image->path;
      $dest = $uploadDir . $subdir . basename($image->path);
      // Proceed only if not already done.
      if (!file_exists($dest)) {
        if (!file_exists($src)) {
          $this->log($entity, $image, 'Main image file missing');
        }
        elseif (copy($src, $dest)) {
          // Do the path update. We don't update the metadata, as we don't want
          // to fire the cache builder (we can rebuild just the images more
          // efficiently).
          $newPath = pg_escape_literal($this->db->getLink(), $subdir . basename($image->path));
          $sql = "UPDATE {$entity}_media SET path = $newPath WHERE id=$image->id;";
          $this->db->query($sql);
          $successCount++;
          // Track the unique base entity (e.g. occurrence or sample) IDs so we
          // can update them.
          $baseEntityIds[$image->$entityIdField] = $image->$entityIdField;
          $dest = $destDir . 'thumb-' . $subdir . basename($image->path);
          if (!file_exists($uploadDir . 'thumb-' . $image->path)) {
            $this->log($entity, $image, 'Thumb image file missing');
          }
          elseif (!file_exists($dest) && !copy($uploadDir . 'thumb-' . $image->path, $dest)) {
            $this->log($entity, $image, 'Thumb image file failed to copy');
          };
          $dest = $destDir . 'med-' . $subdir . basename($image->path);
          if (!file_exists($uploadDir . 'med-' . $image->path)) {
            $this->log($entity, $image, 'Med image file missing');
          }
          elseif (!file_exists($dest) && !copy($uploadDir . 'med-' . $image->path, $dest)) {
            $this->log($entity, $image, 'Med image file failed to copy');
          };
        }
        else {
          $this->log($entity, $image, 'Main image file failed to copy');
        }
      }
      $lastId = $image->id;
    }
    $this->updateCacheMedia($entity, $baseEntityIds);
    variable::set("image_organiser_tracking_$entity", $lastId);
    echo json_encode([
      'status' => count($images) > 0 ? 'OK' : 'Done',
      'moved' => $successCount,
      'entity' => $entity,
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
    $entity = $_POST['type'];
    if (!preg_match('/^[a-z_]*$/', $entity)) {
      throw new exception('Invalid type parameter');
    }
    $fromId = (int) variable::get("image_organiser_tracking_deletes_$entity", 0, FALSE);
    $batchSize = BATCH_SIZE;
    $qry = <<<SQL
SELECT m.id, m.path, m.created_on, m.updated_on, m.deleted
FROM {$entity}_media m
LEFT JOIN image_organiser_problems p ON p.media_id=m.id AND p.entity='$entity'
WHERE m.id>$fromId
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
    variable::set("image_organiser_tracking_deletes_$entity", $lastId);
    header('Content-Type: application/javascript');
    echo json_encode([
      'status' => empty($abortReason) ? (count($images) > 0 ? 'OK' : 'Done') : 'Failed',
      'deleted' => $successCount,
      'entity' => $entity,
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
    // A simple UPSERT - insert if not already in the log.
    $precheck = <<<SQL
SELECT id FROM image_organiser_problems WHERE problem=? AND media_id=? AND entity=?;
SQL;
    if (!$this->db->query($precheck, [$problem, $image->id, $entity])->current()) {
      $sql = <<<SQL
INSERT INTO image_organiser_problems (problem, media_id, entity, created_on, created_by_id)
VALUES (?, ?, ?, now(), ?);
SQL;
      $this->db->query($sql, [$problem, $image->id, $entity, $userId]);
    }
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
   *
   *
   */
  private function deleteOldImageFile($entity, $image, $imageSize) {
    $imageSizePrefix = $imageSize === '' ? '' : "$imageSize-";
    $uploadDir = Kohana::config('upload.directory', TRUE);
    // Strip sub-folders to find original image location.
    $originalPath = basename($image->path);
    $oldFiles = [$originalPath];
    if ($image->deleted === 't') {
      $oldFiles[] = $image->path;
    }
    $destDir = $image->deleted === 't' ? "{$uploadDir}deleted/" : $uploadDir;
    $dest = $destDir . $imageSizePrefix . $image->path;
    if (file_exists($dest)) {
      foreach ($oldFiles as $oldFile) {
        if (file_exists($uploadDir . $imageSizePrefix . $oldFile)) {
          if (!unlink($uploadDir . $imageSizePrefix . $oldFile)) {
            throw new EAbort("Aborting as failed to delete image $oldFile for: {$entity}_media $image->id");
          }
        }
      }
    }
    else {
      throw new EAbort("Aborting old image file deletion as destination file $dest missing: $image->id");
    }
  }

  /**
   * Converts a file date to a sub-directory structure for the file.
   *
   * @param obj $image
   *   Image data loaded from the database.
   *
   * @return string
   *   Sub-folder structure, e.g. '60/20/15/', including trailing slash.
   */
  private function getImageSubdir($image) {
    $subdir = $image->deleted === 't' ? 'deleted/' : '';
    // $levels = Kohana::config('upload.use_sub_directory_levels');
    $levels = 3;
    $ts = strtotime($image->created_on);
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
    warehouse::validateIntCsvListParam($ids);
    // First update the path info in the cache nonfunctional table.
    $qry = <<<SQL
UPDATE cache_{$entity}s_nonfunctional nf
SET media=(
  SELECT array_to_string(array_agg(m.path), ',')
  FROM {$entity}_media m WHERE m.{$entity}_id=nf.id AND m.deleted=false
)
FROM {$entity}s e
WHERE e.id=nf.id
AND e.deleted=false
AND e.id IN ($ids)
SQL;
    $this->db->query($qry);
    // Also ensure the logstash pipeline to Elasticsearch is notified of the
    // update.
    $qry = <<<SQL
UPDATE cache_{$entity}s_functional f
SET website_id=f.website_id
FROM {$entity}s e
WHERE e.id=f.id
AND e.deleted=false
AND e.id IN ($ids)
SQL;
    $this->db->query($qry);
  }

}
