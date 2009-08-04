<?php
/**
 * INDICIA
 * @link http://code.google.com/p/indicia/
 * @package Indicia
 */

/**
 * development hook tool
 *
 * It run the sql files from the upgrade folder during the development process.
 * The name of the upgrade folder must be defined in the main indicia.php config file.
 *
 * @package Indicia
 * @subpackage Hook
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @author Armand Turpel <armand.turpel@gmail.com>
 * @version $Rev$ / $LastChangedDate$ / $Author$
 */
class Dev
{
    public static function __upgrade()
    {
        $uri = URI::instance();
        // we havent to proceed futher if a setup call was made
        if($uri->segment(1) == 'setup_check')
        {
            return;
        }

        // get upgrade folder name
        $dev_version_upgrade_folder = Kohana::config('indicia.devUpgradeFolder', false, false);

        if( (null === $dev_version_upgrade_folder) || (false === $dev_version_upgrade_folder))
        {
            return;
        }

        $_full_upgrade_folder_path = dirname(dirname(__file__)) . '/db/' . $dev_version_upgrade_folder;

        $upgrade = new Upgrade_Model;

        try
        {
            if(!is_dir($_full_upgrade_folder_path))
            {
                throw new  Exception("The folder does not exist: " . $_full_upgrade_folder_path);
            }

            if(!is_writeable($_full_upgrade_folder_path))
            {
                throw new  Exception("The folder isn't writeable: " . $_full_upgrade_folder_path);
            }

            // get last executed sql file name
            $tmp_last_executed_sql_file = Dev::get_last_executed_sql_file_name( $_full_upgrade_folder_path );

            $last_executed_sql_file = str_replace("____", "", $tmp_last_executed_sql_file);

            $upgrade->begin();

            $upgrade->execute_sql_scripts( $dev_version_upgrade_folder, $last_executed_sql_file );

            // write the new last executed file name
            if(!empty($upgrade->last_executed_file))
            {
                if( false === @file_put_contents( $_full_upgrade_folder_path . '/____' . str_replace(".sql", "", $upgrade->last_executed_file) . '____', 'nop' ))
                {
                    throw new  Exception("Couldnt write last executed file name: ". $_full_upgrade_folder_path . '/____' . str_replace(".sql", "", $upgrade->last_executed_file) . '____');
                }

              // remove the previous last executed file name
              if(!empty($tmp_last_executed_sql_file))
              {
                  if( false === @unlink($_full_upgrade_folder_path . '/' . $tmp_last_executed_sql_file))
                  {
                      throw new  Exception("Couldnt delete previous executed file name: " . $_full_upgrade_folder_path . '/' . $tmp_last_executed_sql_file);
                  }
              }
          }

            $upgrade->commit();
        }
        catch(Kohana_Database_Exception $e)
        {
            $upgrade->log($e);
        }
        catch(Exception $e)
        {
            $upgrade->log($e);
        }
    }

    private static function get_last_executed_sql_file_name( $_full_upgrade_folder_path )
    {
        if ( (($handle = @opendir( $_full_upgrade_folder_path ))) != FALSE )
        {
            while ( (( $file = readdir( $handle ) )) != false )
            {
                if ( !preg_match("/^____.*____$/", $file) )
                {
                    continue;
                }

                return $file;
            }
            @closedir( $handle );

            return '';
        }
        else
        {
            throw new  Exception("Cant open dir " . $_full_upgrade_folder_path);
        }
    }
}

Event::add('system.pre_controller', array('Dev', '__upgrade'));

?>
