<?php
/**
 * INDICIA
 * @link http://code.google.com/p/indicia/
 * @package Indicia
 */

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

    private $upgrade_error = array();

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
      $system = new System_Model();
      // version in the file system
      $new_version = kohana::config('version.version');
      // version in the database
      $old_version = $system->getVersion();
      // Downgrade not possible if the new version is lower than the database version      
      if (1 == version_compare($old_version, $new_version) )
      {
        Kohana::log('error', "Current application version ($new_version) is lower than the database version ($old_version). Downgrade not possible.");
        return Kohana::lang('setup.error_downgrade_not_possible');
      }
      // This upgrade process was only introduced in version 0.2.3
      if (1 == version_compare('0.2.3', $old_version) ) {
        $old_version='0.2.3';
      }
      $this->applyUpdateScripts($this->base_dir . "/modules/indicia_setup/", 'Indicia', $old_version);
      // need to look for any module with a db folder, then read its system version and apply the updates.
      foreach (Kohana::config('config.modules') as $path) {
        // skip the indicia_setup module db files since they are for the main app
        if (basename($path)!=='indicia_setup' && file_exists("$path/db/")) {
          $old_version = $system->getVersion(basename($path));
          $this->applyUpdateScripts("$path/", basename($path), $old_version);
        }
      }
      // @todo set_new_version for diff modules
    }

    private function applyUpdateScripts($baseDir, $appName, $old_version) {
      try
      {
        $currentVersionNumbers = explode('.', $old_version);
        $stuffToDo = true;
        while ($stuffToDo) {
          // Get a version name, to search for a suitable script upgrade folder or an upgrade method with this name
          $version_name = 'version_'.implode('_', $currentVersionNumbers);
          kohana::log('debug', "upgrading to $version_name");
          if (method_exists($this, $version_name)) {
            // dynamically execute an upgrade method with this version name
            $this->$version_name();
            $updatedTo = implode('.', $currentVersionNumbers);
            kohana::log('debug', "Method ran for $version_name");
          }
          if (file_exists($baseDir . "db/" . $version_name)) {
            // upgrades cannot proceed without directory permissions being sorted, because otherwise we cannot write the ____*____ files which
            // track the last run script.
            if (!is_writeable($baseDir."db/$version_name")) {
              throw new Exception("You cannot upgrade at the moment until the directory permissions on the Warehouse are corrected. ".
                 "Write access is required to the $baseDir"."db/$version_name folder on the server.");
            }
            // start transaction for each folder full of scripts
            $this->begin();
            // we have a folder containing scripts
            $this->execute_sql_scripts($baseDir, $version_name);
            $updatedTo = implode('.', $currentVersionNumbers);
            // update the version number of the db since we succeeded
            $this->set_new_version($updatedTo, $appName);
            // commit transaction
            $this->commit();
            kohana::log('debug', "Scripts ran for $version_name");
          }
          
          // Now find the next version number. We start by incrementing the smallest part of the version (level=2), if that does not work
          // then we look to the next largest part (level 1) then finally the major version (level 0).
          $level=2;
          $stuffToDo=false;
          while ($level>=0 && $stuffToDo==false) {
            $currentVersionNumbers[$level]++;
            $version_name = 'version_'.implode('_', $currentVersionNumbers);
            if (file_exists($baseDir . "db/" . $version_name) || (method_exists($this, $version_name)))
              $stuffToDo = true;            
            else {
              // Couldn't find anything of this version name. Move up a level (e.g. we have searched 0.2.5 and found nothing, so try 0.3.0)            
              $currentVersionNumbers[$level]=0;
              $level--;
            }
          }        
        }
        // update system table entry to new version
        if (isset($updatedTo)) {
          kohana::log('debug', "Upgrade completed to $updatedTo");
          kohana::log('debug', "Upgrade committed");
        }
      }
      catch(Exception $e)
      {
        $this->rollback();
        kohana::log('error', 'Updates have been rolled back');
        throw $e;
      }      
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
     * log error message
     *
     * @param object $e
     */
    public function log($e)
    {
        $message  = "\n\n\n________________________________________________\n";
        $message .= "Upgrade Error - Time: " . date(DATE_RFC822) . "\n";
        $message .= "MESSAGE: "  .$e->getMessage()."\n";
        $message .= "CODE: "     .$e->getCode()."\n";
        $message .= "FILE: "     .$e->getFile()."\n";
        $message .= "LINE: "     .$e->getLine()."\n";

        Kohana::log('error', $message);

        return $message;
    }

    /**
     * Build the system config content of indicia.php
     *
     * @param string $new_version New version number
     */
    private function buildConfigFileContent($new_version)
    {
      $str = "<?php \n";
      $str .= "\$config['version'] = '$new_version';\n";
      $str .= "\$config['upgrade_date'] = '".date("F j, Y, g:i a")."';\n";
      $str .= "?>";

      return $str;
    }
    
    /**
     * execute all sql srips from the upgrade folder
     *
     * @param string $baseDir directory to the module folder updgrades are in.
     * @param string $upgrade_folder folder version name
     */
    public function execute_sql_scripts($baseDir, $upgrade_folder)
    {
      $file_name = array();
      $full_upgrade_folder = $baseDir . "db/" . $upgrade_folder;
      
      // get last executed sql file name
      $orig_last_executed_file = $this->get_last_executed_sql_file_name($full_upgrade_folder);

      $orig_last_executed_file = str_replace("____", "", $orig_last_executed_file).".sql";
      $last_executed_file=$orig_last_executed_file;

      if ( (($handle = @opendir( $full_upgrade_folder ))) != FALSE )
      {
          while ( (( $file = readdir( $handle ) )) != false )
          {
              if ( !preg_match("/^20.*\.sql$/", $file) )
              {
                  continue;
              }

              $file_name[] = $file;
          }
          @closedir( $handle );
      }
      else
      {
          throw new  Exception("Cant open dir " . $full_upgrade_folder);
      }

      sort($file_name);        
      try
      {
        foreach($file_name as $name) {
          if (strcmp($name, $last_executed_file)>0 || empty($last_executed_file)) {
            if(false === ($_db_file = file_get_contents( $full_upgrade_folder . '/' . $name ))) {
              throw new  Exception("Cant open file " . $full_upgrade_folder . '/' . $name);
            }
            kohana::log('debug', "Upgrading file $name");
            // @todo Look into why utf8 files do not run without conversion to ascii.
            if (!utf8::is_ascii($_db_file)) {
              $_db_file = utf8::strip_non_ascii($_db_file);
            }
            kohana::log('debug', $_db_file);
            $result = $this->db->query($_db_file);
            $last_executed_file = $name;
          }
        }
      }
      catch(Exception $e)
      {
        kohana::log('error', "Error in file: " . $full_upgrade_folder . '/' . $name);
        throw $e;
      }      
      $this->update_last_executed_sql_file($full_upgrade_folder, $orig_last_executed_file, $last_executed_file);        
      return true;
    }

  /**
   * Updates the last executed sql file name after each successful script run.
   */
  private function update_last_executed_sql_file($full_upgrade_folder, $prev, $next) {
    if ($prev!=$next) {
      if (false === @file_put_contents( $full_upgrade_folder . '/____' . str_replace('.sql', '', $next) . '____', 'nop' ))
      {
        throw new  Exception("Couldn't write last executed file name: ". $full_upgrade_folder . '/____' . str_replace(".sql", "", $prev) . '____');
      }
  
      // remove the previous last executed file name
      if ($prev!=".sql")
      {
        if( false === @unlink($full_upgrade_folder . '/____' . str_replace('.sql', '', $prev) .'____'))
        {
          throw new  Exception("Couldn't delete previous executed file name: " . $full_upgrade_folder . '/' . $prev);
        }
      }
    }  
  }
  
  /**
   * Find the file in the directory which is prefixed ____, if it exists. This denotes the last run script from a 
   * previous upgrade.   
   */
  private  function get_last_executed_sql_file_name( $_full_upgrade_folder_path) {
    if ( (($handle = @opendir( $_full_upgrade_folder_path ))) != FALSE ) {
      while ( (( $file = readdir( $handle ) )) != false ) {
        if ( !preg_match("/^____.*____$/", $file) ) {
          continue;
        }
        return $file;
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
   * Method to handle the upgrade from 0.2.3 to 0.2.4.
   * This needs to clean up the old upgrade 0_1_to_0_2 folder plus move the last upgrade script
   * marker into the new version 0_2_3 folder. 
   */
  private function version_0_2_3 () {
    // Only bother if the old script upgrade folder still exists.
    if (file_exists($this->base_dir . '/modules/indicia_setup/db/upgrade_0_1_to_0_2/')) {
      $last_executed_marker_file = $this->get_last_executed_sql_file_name(
          $this->base_dir . '/modules/indicia_setup/db/upgrade_0_1_to_0_2/'
      );
      if ($last_executed_marker_file) {
        if (false === @file_put_contents($this->base_dir . '/modules/indicia_setup/db/version_0_2_3/'.basename($last_executed_marker_file), 'nop' ))
        {
          throw new  Exception("Couldn't write last executed file name: ". $full_upgrade_folder . '/____' . str_replace(".sql", "", $prev) . '____');
        }
      }
      // remove the old database upgrade folder
      try {
        $this->deltree($this->base_dir . '/modules/indicia_setup/db/upgrade_0_1_to_0_2/');
      } catch (Exception $e) {
        $session = new Session();
        $session->set_flash('flash_error', kohana::lang('setup.failed_delete_old_upgrade_folder'));
      }
    }
  }

}

?>
