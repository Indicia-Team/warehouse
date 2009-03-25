<?php
/**
 * INDICIA
 * @link http://code.google.com/p/indicia/
 * @package Indicia
 */

/**
 * Users_Website Model
 *
 *
 * @package Indicia
 * @subpackage Model
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @author xxxxxxx <xxx@xxx.net> / $Author$
 * @copyright xxxx
 * @version $Rev$ / $LastChangedDate$
 */
class Users_website_Model extends ORM
{

    protected $has_one = array(
			'user',
    		'website',
    		'site_role'
	);
    protected $belongs_to = array(
			'created_by'=>'user',
			'updated_by'=>'user'
	);

	public function validate(Validation $array, $save = FALSE) {
		// uses PHP trim() to remove whitespace from beginning and end of all fields before validation

		$array->pre_filter('trim');

		$this->user_id = $array['user_id'];
		$this->website_id = $array['website_id'];
		$this->site_role_id = $array['site_role_id'];

		return parent::validate($array, $save);
	}
}

?>
