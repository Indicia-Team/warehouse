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
    // Invoke the upgrader
    $upgrader = new Upgrade_Model();
    $upgrader->run();
  }
}

if (kohana::config('upgrade.continuous_upgrade')) {
  Event::add('system.pre_controller', array('Dev', '__upgrade'));
}

?>
