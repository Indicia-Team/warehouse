<?php

/**
 * @file
 * Helper class for CRUD operations via the REST API.
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

class rest_utils {

  public static function purgeOldFiles($folder, $age) {
    // don't do this every time.
    if (rand(1, 10) === 1) {
      // First, get an array of files sorted by date.
      $files = array();
      $folder = DOCROOT . "$folder/";
      $dir = opendir($folder);
      // Skip certain file names.
      $exclude = array('.', '..', '.htaccess', 'web.config', '.gitignore');
      if ($dir) {
        while ($filename = readdir($dir)) {
          if (is_dir($filename) || in_array($filename, $exclude)) {
            continue;
          }
          $lastModified = filemtime($folder . $filename);
          $files[] = array($folder . $filename, $lastModified);
        }
      }
      // Sort the file array by date, oldest first.
      usort($files, array('rest_utils', 'dateCmp'));
      // Iterate files, ignoring the number of files we allow in the cache
      // without caring.
      foreach ($files as $file) {
        // If we have reached a file that is not old enough to expire, don't
        // go any further. Expiry set to 1 hour.
        if ($file[1] > (time() - $age)) {
          break;
        }
        // Clear out the old file.
        if (is_file($file[0])) {
          // Ignore errors, will try again later if not deleted.
          @unlink($file[0]);
        }
      }
    }
  }


  /**
   * Custom sort function for date comparison of files.
   *
   * @param int $a
   *   Date value 1 as Unix timestamp.
   * @param int $b
   *   Date value 2 as Unix timestamp.
   */
  private static function dateCmp($a, $b) {
    if ($a[1] < $b[1]) {
      $r = -1;
    }
    elseif ($a[1] > $b[1]) {
      $r = 1;
    }
    else {
      $r = 0;
    }
    return $r;
  }


}