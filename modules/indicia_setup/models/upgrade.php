<?php
/**
 * INDICIA
 * @link http://code.google.com/p/indicia/
 * @package Indicia
 */

/**
 * Filter helper class to find folders in a db folder that are named version_x_x_x
 * and therefore contain scripts to run for that version.
 * @param string $folder
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
    return preg_match('/^version_(\d+)_(\d+)_(\d+)/', $folder) ? true : false;
  }

  /**
   * Checks if a folder's file name is for a version between the range of versions
   * we are applying.
   * @param string $folder Folder name
   * @return bool
   */
  private function isCurrentVersionOrAbove($folder) {
    // convert the folder name to a version string
    preg_match('/^version_(\d+)_(\d+)_(\d+)/', $folder, $matches);
    array_shift($matches);
    $this_version = implode('.', $matches);
    // use a natural string comparison as it works nicely with 1.10.0 being higher than 1.2.0
    return strnatcasecmp($this_version, $this->minVersion) >=0
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
 * Upgrade Model
 *
 * @package Indicia
 * @subpackage Model
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @author Armand Turpel <armand.turpel@gmail.com>
 * @version $Rev$ / $LastChangedDate$ / $Author$
 */
class Upgrade_Model extends Model
{

    private $scriptsForPgUser = '';
    
    private $slowScripts = '';
    
    private $couldBeSlow = true;
    
    public $pgUserScriptsToBeApplied = '';
    
    public $slowScriptsToBeApplied = '';

    public function __construct()
    {
      parent::__construct();
      $this->base_dir = dirname(dirname(dirname(dirname(__file__))));
    }

    /**
     * Do upgrade. Throws exception if upgrade fails.   
     */
    public function run()
    {
      $cache = Cache::instance();
      // delete the system table schema data from the cache, as we need to ensure we are not testing against a copy saved during a failed install attempt. 
      $cache->delete('list_fieldssystem');
      $system = ORM::Factory('system');
      // Need to ensure system table has a last_run_script. It was not in the original 
      // update process pre v0.8, but is required for 0.8 and later. As it is part of the upgrade
      // process it makes sense to add this here rather than via a script.
      if (!array_key_exists('last_run_script', $system->as_array())) {
        $this->db->query('ALTER TABLE system ADD last_run_script VARCHAR(500) null');
      }
      // version in the file system
      $new_version = kohana::config('version.version');
      // version in the database for the main warehouse code
      $old_version = $system->getVersion();
      // Downgrade not possible if the new version is lower than the database version      
      if (1 == version_compare($old_version, $new_version) )
      {
        Kohana::log('error', "Current application version ($new_version) is lower than the database version ($old_version). Downgrade not possible.");
        return Kohana::lang('setup.error_downgrade_not_possible');
      }
      // if less than 1000 occurrences, then no script is likely to be slow so we can run the whole upgrade. If more than 1000, then
      // scripts which perform a lot of processing on occurrences can be marked as slow and run after the upgrade.
      $this->couldBeSlow = $this->db->count_records('occurrences')>1000;
      // Run the core upgrade
      $last_run_script = $system->getLastRunScript('Indicia');
      $this->apply_update_scripts($this->base_dir . "/modules/indicia_setup/", 'Indicia', $old_version, $new_version, $last_run_script);
      $this->set_new_version($new_version, 'Indicia');
      // need to look for any module with a db folder, then read its system version and apply the updates.
      foreach (Kohana::config('config.modules') as $path) {
        // skip the indicia_setup module db files since they are for the main app
        if (basename($path)!=='indicia_setup') {
          if (file_exists("$path/db/")) {
            $old_version = $system->getVersion(basename($path));
            $last_run_script = $system->getLastRunScript(basename($path));
            $this->apply_update_scripts("$path/", basename($path), $old_version, $new_version, $last_run_script);
          } else
            // update the system table to reflect version of all modules without db folders
            $this->set_new_version($new_version, basename($path));
        }
      }
      // In case the upgrade involves changes to supported spatial systems...
      $this->populate_spatial_systems_table();
    }

  /**
   * Returns the list of currently relevant db version folders, i.e. ones that
   * might contain a script which needs applying.
   * @param $base_dir
   * @param $currentVersionNumbers
   * @return array
   */
    private function get_db_versions($base_dir, $from_version, $to_version) {
      $all = scandir("$base_dir/db");
      $folders = array_filter($all, array(new FolderFilter($from_version, $to_version), "wantFolder"));
      natsort($folders);
      return $folders;
    }

    private function apply_update_scripts($base_dir, $app_name, $old_version, $new_version, $last_run_script) {
      $db_versions = $this->get_db_versions($base_dir, $old_version, $new_version);
      foreach ($db_versions as $version_folder) {
        kohana::log('debug', "upgrading $app_name database to $version_folder");
        if (file_exists($base_dir . "db/" . $version_folder)) {
          // start transaction for each folder full of scripts
          $this->begin();
          try {
            $this->scriptsForPgUser = '';
            $this->slowScripts = '';
            // we have a folder containing scripts
            $this->execute_sql_scripts($base_dir, $version_folder, $app_name, $last_run_script);
            // update the version number of the db since we succeeded
            $this->set_new_version($new_version, $app_name);
          }
          catch (Exception $e) {
            $this->rollback();
            throw $e;
          }
          // commit transaction
          $this->commit();
          // only tell the user if there are superuser or slow scripts, when the transaction has been committed.
          $this->pgUserScriptsToBeApplied .= $this->scriptsForPgUser;
          $this->slowScriptsToBeApplied .= $this->slowScripts;
          kohana::log('info', "Scripts ran for $app_name $version_folder");
          // reset last run script - if there are more version folders then we start at the top.
          $last_run_script = '';
        }
      }
      kohana::log('debug', "Upgrade of $app_name completed to $new_version");
    }

    /**
     * start transaction
     *
     */
    public function begin()
    {
        $this->db->query("BEGIN READ WRITE");
    }

    /**
     * end transaction
     *
     */
    public function commit()
    {
        $this->db->query("COMMIT");
    }
    
    /**
     * rollback transaction
     *
     */
    public function rollback()
    {
        $this->db->query("ROLLBACK");
    }

    /**
     * update system table entry to new version
     *
     * @param array $new_version  New version number
     */
    private function set_new_version($new_version, $appName)
    {
      $sql = "UPDATE system SET version='$new_version' WHERE name='$appName'";
      // App name may be empty for the Indicia system record due to upgrade sequence - won't be in future
      if ($appName=='Indicia')
        $sql .= " OR name=''";
      $query = $this->db->query($sql);
      // Because pgsql does not handle UPDATE or INSERT etc, do this manually if a new record is required.
      if ($query->count()===0) {
        $this->db->query("INSERT INTO system (version, name, repository, release_date) ".
            "VALUES('$new_version', '$appName', 'Not specified', now())");
      }
    }
    
    /**
     * execute all sql srips from the upgrade folder
     *
     * @param string $baseDir directory to the module folder updgrades are in.
     * @param string $upgrade_folder folder version name
     */
    public function execute_sql_scripts($baseDir, $upgrade_folder, $appName, &$last_run_script)
    {
      $file_name = array();
      $full_upgrade_folder = $baseDir . "db/" . $upgrade_folder;
      
      // get last executed sql file name. If not in the parameter (which loads from the db via the
      // system model), then it could be from an old version of Indicia pre 0.8 so has the last
      // run script saved as a file in the db scripts folder. Or we could just be starting a new folder.
      if (empty($last_run_script))
        $last_run_script = $this->get_last_executed_sql_file_name($full_upgrade_folder, $appName);
      $original_last_run_script = $last_run_script;

      if ( (($handle = @opendir( $full_upgrade_folder ))) != FALSE )
      {
          while ( (( $file = readdir( $handle ) )) != false )
          {
              if ( !preg_match("/^20.*\.sql$/", $file) ) {
                continue;
              }
              $file_name[] = $file;
          }
          @closedir( $handle );
      }
      else {
        throw new  Exception("Cant open dir " . $full_upgrade_folder);
      }
      sort($file_name);      
      try
      { 
        foreach($file_name as $name) {
          if (strcmp($name, $last_run_script)>0 || empty($last_run_script)) {
            if(false === ($_db_file = file_get_contents( $full_upgrade_folder . '/' . $name ))) {
              throw new  Exception("Can't open file " . $full_upgrade_folder . '/' . $name);
            }
            kohana::log('debug', "Upgrading file $name");
            // @todo Look into why utf8 files do not run without conversion to ascii.
            if (!utf8::is_ascii($_db_file)) {
              $_db_file = utf8::strip_non_ascii($_db_file);
            }
            if (substr($_db_file, 0, 18) === '-- #postgres user#')
              $this->scriptsForPgUser .= $_db_file . "\n\n";
            elseif (substr($_db_file, 0, 16) === '-- #slow script#' && $this->couldBeSlow)
              $this->slowScripts .= $_db_file . "\n\n";
            else
              $result = $this->db->query($_db_file);
            $last_run_script = $name;
          }
        }
      }
      catch(Exception $e)
      {
        kohana::log('error', "Error in file: " . $full_upgrade_folder . '/' . $name);
        kohana::log('error', $e->getMessage());
        throw $e;
      }
      $this->update_last_executed_sql_file($full_upgrade_folder, $appName, $original_last_run_script, $last_run_script);        
      return true;
    }

  /**
   * Updates the last executed sql file name after each successful script run.
   */
  private function update_last_executed_sql_file($full_upgrade_folder, $appName, $prev, $next) {
    $system = ORM::Factory('system');
    $system->forceSystemEntry($appName);
    $this->db->update('system', array('last_run_script'=>$next), array('name'=>$appName));  
  }
  
  /**
   * Find the file in the directory which is prefixed ____, if it exists. This denotes the last run script from a 
   * previous upgrade.
   * Note that this approach of handling last upgrade script is no longer used, but the method is kept
   * for handling upgrades from previous versions. The last upgrade script is instead stored in the db
   * as this reduces the requirement to mess around with file privileges.   
   */
  private  function get_last_executed_sql_file_name($_full_upgrade_folder_path, $appName='') {
    if ( (($handle = @opendir( $_full_upgrade_folder_path ))) != FALSE ) {
      while ( (( $file = readdir( $handle ) )) != false ) {
        if ( !preg_match("/^____(?P<file>.*)____$/", $file, $matches) ) {
          continue;
        }
        return $matches['file'].'.sql';
      }
      @closedir( $handle );

      return '';
    }
    else {
      throw new  Exception("Can't open dir " . $_full_upgrade_folder_path);
    }
  }
  
  /**
   * Utility function to remove a directory, required during some upgrade steps.
   *
   */
  function delTree($dir) {
    $files = glob( $dir . '*', GLOB_MARK );
    foreach( $files as $file ){
      if( substr( $file, -1 ) == '/' )
        delTree( $file );
      else
        unlink( $file );
    }
    if (is_dir($dir)) rmdir( $dir );   
  }
  
  /**
   * The upgrade might involve a change to spatial system support in plugins, so now is a good
   * time to refresh the spatial_systems metadata table.
   */
  private function populate_spatial_systems_table() {
    $system_metadata = spatial_ref::system_metadata(true);
    $existing = $this->db->select('id', 'code')
         ->from('spatial_systems')
         ->get()->result_array(false);
    foreach ($system_metadata as $system => $metadata) {
      $id = false;
      foreach ($existing as $idx => $record) {
        if ($record['code'] === $system) {
          // record already exists
          $id = $record['id'];
          unset($existing[$idx]);
          break;
        }
      }
      $metadata['treat_srid_as_x_y_metres'] = isset($metadata['treat_srid_as_x_y_metres']) && $metadata['treat_srid_as_x_y_metres'] ? 't' : 'f';
      if ($id) {
        $this->db->update('spatial_systems', array_merge($metadata, array('code' => $system)), array('id'=>$id));
      } else {
        $this->db->insert('spatial_systems', array_merge($metadata, array('code' => $system)));
      }
    }
    // delete any that remain in $existing, since they are no longer supported
    foreach ($existing as $idx => $record) {
      $this->db->delete('spatial_systems', array('id'=>$record['id']));
    }
  }

}