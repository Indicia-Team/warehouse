<?php
/**
 * INDICIA
 * @link http://code.google.com/p/indicia/
 * @package Indicia
 */

/**
 * System Model
 *
 * @package Indicia
 * @subpackage Model
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @author Armand Turpel <armand.turpel@gmail.com> / $Author$
 * @version $Rev$ / $LastChangedDate$
 */
class System_Model extends Model
{
    /**
     * @var object $system_data
     */
    private $system_data;

    public function __construct()
    {
        parent::__construct();

        $result = $this->db->query('SELECT * FROM "system" ORDER BY id DESC LIMIT 1');

        $this->system_data = $result[0];
    }

    /**
     * get indicia version
     *
     * @return string
     */
    public function getVersion()
    {
        return $this->system_data->version;
    }
}

?>
