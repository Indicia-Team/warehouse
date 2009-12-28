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
     * Do upgrade
     *
     * @param string $current_version
     * @param array $new_system
     * @return mixed true if successful else error message
     */
    public function run( $current_version, $new_system )
    {
        // Downgrade not possible. The new version is lower than the database version
        //
        if(1 == version_compare($current_version, $new_system['version']) )
        {
            Kohana::log('error', 'Current script version is lower than the database version. Downgrade not possible.');
            return Kohana::lang('setup.error_downgrade_not_possible');
        }

        // start transaction
        $this->begin();
        try
        {

            // remove this upgrade step for the first indicia release
            //
            if(0 == version_compare('0.1', $current_version) )
            {
                // upgrade from version 0.1 to 0.2
                $this->upgrade_0_1_to_0_2();
                $current_version = '0.2';
            }

/* Sample for the next upgrade

            if(0 == version_compare('0.2', $current_version) )
            {
                // upgrade from version 0.2 to 0.3
                $this->upgrade_0_2_to_0_3();
                $current_version = '0.3';
            }

*/

            // update system table entry to new version
            $this->set_new_version( $new_system );

            // update indicia.php config file
            $this->update_config_file( $new_system );

            // commit transaction
            $this->commit();

            return true;
        }
        catch(Kohana_Database_Exception $e)
        {
            $this->log($e);
        }
        catch(Exception $e)
        {
            $this->log($e);
        }

        return $e->getMessage();
    }

    /**
     * upgrade from version 0.1 to 0.2
     *
     */
    private function upgrade_0_1_to_0_2()
    {
        return $this->execute_sql_scripts( 'upgrade_0_1_to_0_2' );
    }

    /**
     * upgrade from version 0.2 to 0.3
     *
     */
    private function upgrade_0_2_to_0_3()
    {
        return $this->execute_sql_scripts( 'upgrade_0_2_to_0_3' );
    }

    /**
     * start transaction
     *
     */
    public function begin()
    {
        $this->db->query("BEGIN");
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
     * update system table entry to new version
     *
     * @param array $new_system  New version number
     */
    private function set_new_version( $new_system )
    {
        $this->db->query("INSERT INTO \"system\"
                          (\"version\", \"name\", \"repository\", \"release_date\")
                          VALUES
                          ('{$new_system['version']}',
                           '{$new_system['name']}',
                           '{$new_system['repository']}',
                           '{$new_system['release_date']}')");
    }

    /**
     * update indicia.php config file with new system info
     * @param array $new_system
     */
    private function update_config_file( $new_system )
    {
        $this->write_config( $this->buildConfigFileContent( $new_system ) );
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
     * @param array $new_system
     */
    private function buildConfigFileContent( & $new_system )
    {
        // get config vars from the existing old config file
        $__config = Kohana::config('indicia');

        $str = "<?php \n\n";

        $str .= '$config["system"]["version"]'      . " = '{$new_system['version']}';\n";
        $str .= '$config["system"]["name"]'         . " = '{$new_system['name']}';\n";
        $str .= '$config["system"]["repository"]'   . " = '{$new_system['repository']}';\n";
        $str .= '$config["system"]["release_date"]' . " = '{$new_system['release_date']}';\n\n";

        $str .= '$config[\'private_key\']     = \'' . $__config['private_key'] . "'; // Change this to a unique value for each Indicia install\n";
        $str .= '$config[\'nonce_life\']      = '   . $__config['nonce_life'] . ";       // life span of an authentication token for services, in seconds\n";
        $str .= '$config[\'maxUploadSize\']   = \'' . $__config['maxUploadSize'] . "'; // Maximum size of an upload\n";
        $str .= '$config[\'defaultPersonId\'] = '   . $__config['defaultPersonId'] . ";\n";

        $str .= "\n?>";

        return $str;
    }

    /**
     * Write indicia.php config file
     *
     * @param string $config_content
     */
    private function write_config( $config_content )
    {
        $config_file = $this->base_dir . "/application/config/indicia.php";

        if( !@is_writeable($config_file) )
        {
            throw new  Exception("Config file indicia.php isnt writeable. Check permission on: ". $config_file);
        }

        if(!$fp = @fopen($config_file, 'w'))
        {
           throw new Exception("Cant open file to write: ". $config_file);
        }

        if( !@fwrite($fp, $config_content) )
        {
            throw new Exception("Cant write file: ". $config_file);
        }

        @fclose($fp);
    }

    /**
     * execute all sql srips from the upgrade folder
     *
     * @param string $upgrade_folder folder name
     */
    public function execute_sql_scripts($upgrade_folder)
    {
        $this->begin();        
        $file_name = array();
        $full_upgrade_folder = $this->base_dir . "/modules/indicia_setup/db/" . $upgrade_folder;
        
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
        kohana::log('debug', implode(', ',$file_name));
        try
        {
            foreach($file_name as $name) {             
              kohana::log('debug', $name);
              if (strcmp($name, $last_executed_file)>0 || empty($last_executed_file)) {
                if(false === ($_db_file = file_get_contents( $full_upgrade_folder . '/' . $name ))) {
                  throw new  Exception("Cant open file " . $full_upgrade_folder . '/' . $name);
                }
                kohana::log('debug', "Upgrading file $name");
                $result = $this->db->query($_db_file);
                $last_executed_file = $name;
              }
            }
        }
        catch(Kohana_Database_Exception $e)
        {
            $_error = "Error in file: " . $full_upgrade_folder . '/' . $name . "\n\n" . $e->getMessage();
            throw new Exception($_error);
        }
        $this->commit();
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
        throw new  Exception("Couldnt write last executed file name: ". $full_upgrade_folder . '/____' . str_replace(".sql", "", $prev) . '____');
      }
  
      // remove the previous last executed file name
      if ($prev!=".sql")
      {
        if( false === @unlink($full_upgrade_folder . '/____' . str_replace('.sql', '', $prev) .'____'))
        {
          throw new  Exception("Couldnt delete previous executed file name: " . $full_upgrade_folder . '/' . $prev);
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
      throw new  Exception("Cant open dir " . $_full_upgrade_folder_path);
    }
  }

}

?>
