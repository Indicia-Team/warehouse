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
 * @author Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link http://code.google.com/p/indicia/
 */

/**
 * Filter helper class to find folders in a db folder that are named version_x_x_x
 * and therefore contain scripts to run for that version.
 *
 * @param string $folder
 *
 * @return boolean
 */
class FolderFilter {
  private $minVersion;
  private $maxVersion;

  /**
   * Capture the current version on construct to allow this effectively as a
   * parameter to the callback filter functions.
   * @param string $min_version Version string for the first version to apply, e.g. 0.9.0
   * @param string $max_version Version string for the last version to apply, e.g. 1.0.0
   */
  function __construct($min_version, $max_version) {
    $this->minVersion = $min_version;
    $this->maxVersion = $max_version;
  }

  /**
   * Does this folder match the naming convention for a database version folder?
   * @param string $folder Folder name
   * @return bool
   */
  private function isDbVersionFolder($folder) {
    return preg_match('/^version_(\d+)_(\d+)_(\d+)/', $folder) ? TRUE : FALSE;
  }

  /**
   * Chacks a folder verion number.
   *
   * Checks if a folder's file name is for a version between the range of
   * versions we are applying.
   *
   * @param string $folder
   *   Folder name.
   *
   * @return bool
   *   True if the folder version number is the current version or higher.
   */
  private function isCurrentVersionOrAbove($folder) {
    // Convert the folder name to a version string.
    preg_match('/^version_(\d+)_(\d+)_(\d+)/', $folder, $matches);
    array_shift($matches);
    $this_version = implode('.', $matches);
    // Use a natural string comparison as it works nicely with 1.10.0 being
    // higher than 1.2.0.
    return strnatcasecmp($this_version, $this->minVersion) >= 0
        && strnatcasecmp($this_version, $this->maxVersion) <= 0;
  }

  /**
   * Function that can be used in an array_filter callback to determine if a folder
   * needs to be included in the current upgrade or not.
   * @param string $path File path
   * @return bool Include the folder?
   */
  function wantFolder($path) {
    $path_parts = explode('/', $path);
    $folder = array_pop($path_parts);
    return $this->isDbVersionFolder($folder) && $this->isCurrentVersionOrAbove($folder);
  }

}

/**
 * Upgrade Model.
 *
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @author Armand Turpel <armand.turpel@gmail.com>
 * @version $Rev$ / $LastChangedDate$ / $Author$
 */
class Upgrade_Model extends Model {

  private $scriptsForPgUser = '';

  private $slowScripts = '';

  private $couldBeSlow = TRUE;

  /**
   * Root directory, used to locate upgrade scripts.
   *
   * @var string
   */
  private $base_dir;

  public $pgUserScriptsToBeApplied = '';

  public $slowScriptsToBeApplied = '';

  public function __construct() {
    parent::__construct();
    $this->base_dir = dirname(dirname(dirname(dirname(__file__))));
  }

  /**
   * Do upgrade. Throws exception if upgrade fails.
   */
  public function run() {
    $cache = Cache::instance();
    // Delete the system table schema data from the cache, as we need to ensure
    // we are not testing against a copy saved during a failed install attempt.
    $cache->delete('list_fieldssystem');
    $system = ORM::Factory('system');
    // Need to ensure system table has a last_run_script. It was not in the
    // original update process pre v0.8, but is required for 0.8 and later. As
    // it is part of the upgrade process it makes sense to add this here rather
    // than via a script.
    if (!array_key_exists('last_run_script', $system->as_array())) {
      $this->db->query('ALTER TABLE system ADD last_run_script VARCHAR(500) null');
    }
    // Version in the file system.
    $new_version = kohana::config('version.version');
    // Version in the database for the main warehouse code.
    $old_version = $system->getVersion();
    // Downgrade not possible if the new version is lower than the database
    // version.
    if (1 == version_compare($old_version, $new_version)) {
      Kohana::log('error', "Current application version ($new_version) is lower than the database version ($old_version). Downgrade not possible.");
      return Kohana::lang('setup.error_downgrade_not_possible');
    }
    // If less than 1000 occurrences, then no script is likely to be slow so we
    // can run the whole upgrade. If more than 1000, then scripts which perform
    // a lot of processing on occurrences can be marked as slow and run after
    // the upgrade. We use an approx count as it is much faster than count(*).

    $this->couldBeSlow = $this->db
      ->query("SELECT reltuples as approx_count FROM pg_class WHERE oid = 'occurrences'::regclass;")
      ->current()->approx_count > 1000;
    // Run the core upgrade.
    $last_run_script = $system->getLastRunScript('Indicia');
    $this->applyUpdateScripts($this->base_dir . "/modules/indicia_setup/", 'Indicia', $old_version, $new_version, $last_run_script);
    $this->setNewVersion($new_version, 'Indicia');
    // Need to look for any module with a db folder, then read its system
    // version and apply the updates.
    foreach (Kohana::config('config.modules') as $path) {
      // Skip the indicia_setup module db files since they are for the main
      // app.
      if (basename($path) !== 'indicia_setup') {
        if (file_exists("$path/db/")) {
          $old_version = $system->getVersion(basename($path));
          $last_run_script = $system->getLastRunScript(basename($path));
          $this->applyUpdateScripts("$path/", basename($path), $old_version, $new_version, $last_run_script);
        }
        else {
          // Update the system table to reflect version of all modules without
          // db folders.
          $this->setNewVersion($new_version, basename($path));
        }
      }
    }
    // In case the upgrade involves changes to supported spatial systems...
    $this->populate_spatial_systems_table();
    // Also clear some cache entries so changes get picked up.
    $this->refreshCache();
  }

  /**
   * Clears old cache entries that might need rebuild after an upgrade.
   */
  private function refreshCache() {
    $cache = Cache::instance();
    $cache->delete('extend-data-services');
    $cache->delete('scheduled-plugin-names');
    $cache->delete('spatial-ref-systems');
    $cache->delete('work-queue-helpers');
    $cache->delete_tag('orm');
    $cache->delete_tag('required-fields');
    $cache->delete_tag('attribute-lists');
    $cache->delete_tag('ui');
  }

  /**
   * Returns the list of currently relevant db version folders, i.e. ones that
   * might contain a script which needs applying.
   *
   * @param $base_dir
   * @param $currentVersionNumbers
   *
   * @return array
   */
  private function get_db_versions($base_dir, $from_version, $to_version) {
    $all = scandir("$base_dir/db");
    $folders = array_filter(
      $all, [new FolderFilter($from_version, $to_version), "wantFolder"]
    );
    natsort($folders);
    return $folders;
  }

  private function applyUpdateScripts($base_dir, $app_name, $old_version, $new_version, $last_run_script) {
    $db_versions = $this->get_db_versions($base_dir, $old_version, $new_version);
    // If we are starting a new folder (i.e. the last folder of db scripts has
    // already been fully applied) then make sure we start at the beginning.
    if (count($db_versions) > 0 && reset($db_versions) !== 'version_' . str_replace('.', '_', $old_version)) {
      $last_run_script = '';
    }
    foreach ($db_versions as $version_folder) {
      kohana::log('debug', "upgrading $app_name database to $version_folder");
      if (file_exists($base_dir . "db/" . $version_folder)) {
        // Start transaction for each folder full of scripts.
        $this->begin();
        try {
          $this->scriptsForPgUser = '';
          $this->slowScripts = '';
          // We have a folder containing scripts.
          $this->executeSqlScripts($base_dir, $version_folder, $app_name, $last_run_script);
          // Update the version number of the db since we succeeded.
          $this->setNewVersion($new_version, $app_name);
        }
        catch (Exception $e) {
          $this->rollback();
          throw $e;
        }
        // Commit transaction.
        $this->commit();
        // Only tell the user if there are superuser or slow scripts, when the
        // transaction has been committed.
        $this->pgUserScriptsToBeApplied .= $this->scriptsForPgUser;
        $this->slowScriptsToBeApplied .= $this->slowScripts;
        kohana::log('info', "Scripts ran for $app_name $version_folder");
        // Reset last run script - if there are more version folders then we
        // start at the top.
        $last_run_script = '';
        // Execute any PHP upgrade methods for this version.
        if ($app_name === 'Indicia' && method_exists($this, $version_folder)) {
          $this->$version_folder();
        }
      }
    }
    kohana::log('debug', "Upgrade of $app_name completed to $new_version");
  }

  /**
   * Start transaction.
   */
  public function begin() {
    $this->db->query("BEGIN READ WRITE");
  }

  /**
   * End transaction.
   */
  public function commit() {
    $this->db->query("COMMIT");
  }

  /**
   * Rollback transaction.
   */
  public function rollback() {
    $this->db->query("ROLLBACK");
  }

  /**
   * Update system table entry to new version.
   *
   * @param array $new_version
   *   New version number.
   */
  private function setNewVersion($new_version, $appName) {
    $sql = "UPDATE system SET version=? WHERE name=?";
    // App name may be empty for the Indicia system record due to upgrade
    // sequence - won't be in future.
    if ($appName == 'Indicia') {
      $sql .= " OR name=''";
    }
    $query = $this->db->query($sql, [$new_version, $appName]);
    // Because pgsql does not handle UPDATE or INSERT etc, do this manually if
    // a new record is required.
    if ($query->count() === 0) {
      $this->db->query(<<<SQL
        INSERT INTO system (version, name, repository, release_date)
        VALUES(?, ?, 'Not specified', now())
      SQL, [$new_version, $appName]);
    }
  }

  /**
   * Execute all sql srips from the upgrade folder.
   *
   * @param string $baseDir
   *   Directory to the module folder updgrades are in.
   * @param string $upgrade_folder
   *   Folder version name.
   */
  public function executeSqlScripts($baseDir, $upgrade_folder, $appName, &$last_run_script) {
    $file_name = [];
    $full_upgrade_folder = $baseDir . "db/" . $upgrade_folder;

    // Get last executed sql file name. If not in the parameter (which loads
    // from the db via the system model), then it could be from an old version
    // of Indicia pre 0.8 so has the last run script saved as a file in the db
    // scripts folder. Or we could just be starting a new folder.
    if (empty($last_run_script)) {
      $last_run_script = $this->get_last_executed_sql_file_name($full_upgrade_folder, $appName);
    }
    $original_last_run_script = $last_run_script;

    if ((($handle = @opendir($full_upgrade_folder))) != FALSE) {
      while (($file = readdir($handle)) != FALSE) {
        // File name must start with at least the date in ISO numerical format.
        if (!preg_match("/^\d{8}.*\.sql$/", $file)) {
          continue;
        }
        $file_name[] = $file;
      }
      @closedir($handle);
    }
    else {
      throw new Exception("Cant open dir " . $full_upgrade_folder);
    }
    sort($file_name);
    $masterListId = warehouse::getMasterTaxonListId();
    try {
      foreach ($file_name as $name) {
        if (strcmp($name, $last_run_script) > 0 || empty($last_run_script)) {
          if (FALSE === ($_db_file = file_get_contents($full_upgrade_folder . '/' . $name))) {
            throw new Exception("Can't open file " . $full_upgrade_folder . '/' . $name);
          }
          kohana::log('debug', "Upgrading file $name");
          // @todo Look into why utf8 files do not run without conversion to ascii.
          if (!utf8::is_ascii($_db_file)) {
            $_db_file = utf8::strip_non_ascii($_db_file);
          }
          // Let upgrade scripts know if there is a master taxon list ID.
          $_db_file = str_replace('#master_list_id#', $masterListId, $_db_file);
          if (substr($_db_file, 0, 18) === '-- #postgres user#') {
            $this->scriptsForPgUser .= $_db_file . "\n\n";
          }
          elseif (substr($_db_file, 0, 16) === '-- #slow script#' && $this->couldBeSlow) {
            $this->slowScripts .= $_db_file . "\n\n";
          }
          else {
            $result = $this->db->query($_db_file);
          }
          $last_run_script = $name;
        }
      }
    }
    catch (Exception $e) {
      kohana::log('error', "Error in file: " . $full_upgrade_folder . '/' . $name);
      kohana::log('error', $e->getMessage());
      throw $e;
    }
    $this->update_last_executed_sql_file(
      $full_upgrade_folder, $appName, $original_last_run_script, $last_run_script
    );
    return TRUE;
  }

  /**
   * Updates the last executed sql file name after each successful script run.
   */
  private function update_last_executed_sql_file($full_upgrade_folder, $appName, $prev, $next) {
    $system = ORM::Factory('system');
    $system->forceSystemEntry($appName);
    $this->db->update('system', ['last_run_script' => $next], ['name' => $appName]);
  }

  /**
   * Find the file in the directory which is prefixed ____, if it exists. This denotes the last run script from a
   * previous upgrade.
   * Note that this approach of handling last upgrade script is no longer used, but the method is kept
   * for handling upgrades from previous versions. The last upgrade script is instead stored in the db
   * as this reduces the requirement to mess around with file privileges.
   */
  private function get_last_executed_sql_file_name($_full_upgrade_folder_path, $appName = '') {
    if (($handle = @opendir($_full_upgrade_folder_path)) != FALSE) {
      while (($file = readdir($handle)) != FALSE) {
        if (!preg_match("/^____(?P<file>.*)____$/", $file, $matches)) {
          continue;
        }
        return $matches['file'] . '.sql';
      }
      @closedir($handle);

      return '';
    }
    else {
      throw new Exception("Can't open dir " . $_full_upgrade_folder_path);
    }
  }

  /**
   * Utility function to remove a directory, required during some upgrade steps.
   */
  function delTree($dir) {
    $files = glob($dir . '*', GLOB_MARK);
    foreach ($files as $file) {
      if (substr($file, -1) == '/') {
        delTree($file);
      }
      else {
        unlink($file);
      }
    }
    if (is_dir($dir)) {
      rmdir($dir);
    }
  }

  /**
   * The upgrade might involve a change to spatial system support in plugins, so now is a good
   * time to refresh the spatial_systems metadata table.
   */
  private function populate_spatial_systems_table() {
    $system_metadata = spatial_ref::system_metadata(TRUE);
    $existing = $this->db->select('id', 'code')
      ->from('spatial_systems')
      ->get()->result_array(FALSE);
    foreach ($system_metadata as $system => $metadata) {
      $id = FALSE;
      foreach ($existing as $idx => $record) {
        if ($record['code'] === $system) {
          // Record already exists.
          $id = $record['id'];
          unset($existing[$idx]);
          break;
        }
      }
      $metadata['treat_srid_as_x_y_metres'] = isset($metadata['treat_srid_as_x_y_metres']) && $metadata['treat_srid_as_x_y_metres'] ? 't' : 'f';
      if ($id) {
        $this->db->update('spatial_systems', array_merge($metadata, ['code' => $system]), ['id' => $id]);
      }
      else {
        $this->db->insert('spatial_systems', array_merge($metadata, ['code' => $system]));
      }
    }
    // Delete any that remain in $existing, since they are no longer supported.
    foreach ($existing as $idx => $record) {
      $this->db->delete('spatial_systems', ['id' => $record['id']]);
    }
  }

  /**
   * Method called for v2 upgrade.
   *
   * Removes location_id_* columns from cache tables as replaced by
   * location_ids[].
   */
  private function version_2_0_0() {
    if (in_array(MODPATH . 'cache_builder', kohana::config('config.modules'))) {
      $baseTables = ['samples', 'occurrences'];
      foreach ($baseTables as $baseTable) {
        $qry = <<<SQL
select column_name from information_schema.columns
where table_schema='indicia'
and table_name='cache_{$baseTable}_functional'
and column_name like 'location\_id\_%'
SQL;
        $columns = $this->db->query($qry)->result();
        foreach ($columns as $column) {
          $qry = <<<SQL
ALTER TABLE cache_{$baseTable}_functional
DROP COLUMN $column->column_name
SQL;
          $this->db->query($qry);
        }
      }
    }
  }

}
