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
 * This hook is only enabled if there is a config setting upgrade.continuous_upgrade set to true.
 *
 * @package Indicia
 * @subpackage Hook
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @author Indicia Team
 * @version $Rev$ / $LastChangedDate$ / $Author$
 */
class Dev
{
  public static function __upgrade()
  {            
    $uri = URI::instance();
    // we havent to proceed futher if a setup call was made
    if($uri->segment(1) == 'setup_check' || $uri->segment(2) == 'upgrade') {
      return;
    }
    // also do not proceed when responding to a web service call 
    // as we may not have update permission on the database    
    if($uri->segment(1) == 'services') {
      return;
    }
    // Invoke the upgrader
    $upgrader = new Upgrade_Model();
    $upgrader->run();
  }
}

// load the optional config file to specify continuous updates
$upgradeConfig = kohana::config_load('upgrade', false);
if ($upgradeConfig && $upgradeConfig['continuous_upgrade']) {
  Event::add('system.pre_controller', array('Dev', '__upgrade'));
  
}

?>
