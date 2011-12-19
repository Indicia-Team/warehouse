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
 * @package	Core
 * @subpackage Models
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL
 * @link 	http://code.google.com/p/indicia/
 */

/**
 * Model class for the System table.
 *
 * @package	Core
 * @subpackage Models
 * @link	http://code.google.com/p/indicia/wiki/DataModel
 */
class System_Model extends ORM
{
    protected $table_names_plural = FALSE;
    
    /**
     * @var array $system_data
     */
    private $system_data;

    /**
     * get indicia version
     * @param string $name Name of the module or application to check the version for
     * @return string
     */
    public function getVersion($name='Indicia')
    {
      $this->getSystemData($name);
      if (isset($this->system_data[$name]))
        $data = $this->system_data[$name];
      return isset($data) ? $data->version : '0.0.0';
    }
    
    /**
     * get indicia version
     *
     * @return string
     */
    public function getLastScheduledTaskCheck($name='Indicia')
    {
      $this->getSystemData($name);
      if (isset($this->system_data[$name]))
        $data = $this->system_data[$name];
      return isset($data) ? $data->last_scheduled_task_check : 0;
    }
    
    /**
     * get indicia version
     * @param string $name Name of the script that was run last in the update process
     * @return string
     */
    public function getLastRunScript($name='Indicia')
    {
      $this->getSystemData($name);
      if (isset($this->system_data[$name]))
        $data = $this->system_data[$name];
      // note last_run_script only exists after v0.8.
      return isset($data) && isset($data->last_run_script) ? $data->last_run_script : '';
    }
    
    /**
     * Function which ensures that the system table entry exists for an application or module.
     * @param type $name 
     */
    public function forceSystemEntry($name) {
      $this->getSystemData($name);
      if (!isset($this->system_data[$name])) {
        $this->db->insert('system', array(
            'version'=>'',
            'name'=>$name,
            'repository'=>'Not specified',
            'release_date'=>'now()',
            'last_scheduled_task_check'=>'now()'
        ));
      }
    }

    /**
     * Load on demand for records from the system table.
     * @param <type> $name
     */
    private function getSystemData($name) {
      if (!isset($system_data[$name])) {
        // The following ensures that a blank name in the system table is treated as the system row for the indicia warehouse.
        // Having a blank name should not really occur, but it does seem to in some update sequences. This won't matter after
        // v0.6.
        $this->db->update('system', array('name' => 'Indicia'), array('name' => ''));
        $result = $this->db->select('*')
            ->from('system')
            ->where(array('name'=>"$name"))
            ->limit(1)
            ->get();
        if (count($result)>0)
          $this->system_data[$name] = $result[0];
      }
    }
}

?>
