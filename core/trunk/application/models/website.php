<?php
/**
 * INDICIA
 * @link http://code.google.com/p/indicia/
 * @package Indicia
 */

/**
 * Website Model
 *
 *
 * @package Indicia
 * @subpackage Model
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @author xxxxxxx <xxx@xxx.net> / $Author$
 * @copyright xxxx
 * @version $Rev$ / $LastChangedDate$
 */
class Website_Model extends ORM
{
    protected $has_many = array(
			'termlists',
			'taxon_lists'
	);
    protected $belongs_to = array(
			'created_by'=>'user',
			'updated_by'=>'user'
	);
    protected $has_and_belongs_to_many = array(
			'locations',
			'users'
	);

	public $password2;
	
    /**
     * Validate and save the data.
     */
    public function validate(Validation $array, $save = FALSE) {
        // uses PHP trim() to remove whitespace from beginning and end of all fields before validation
        $array->pre_filter('trim');
        $array->add_rules('title', 'required', 'length[1,100]');
        $array->add_rules('url', 'required', 'length[1,500]', 'url');
        // NOTE password is stored unencrypted.
        // The repeat password held in password2 does not get through preSubmit during the submit process
        // and is not present in the validation object at this point. The "matches" validation rule does not
        // work in these circumstances, so a new "matches_post" has been inserted into MY_valid.php
        $array->add_rules('password', 'required', 'length[7,30]', 'matches_post[password2]');
	// Explicitly add those fields for which we don't do validation
	$extraFields = array(
		'description',
		'deleted'
	);
	foreach ($extraFields as $a) {
		if (array_key_exists($a, $array->as_array())){
			$this->__set($a, $array[$a]);
		}
	}

	return parent::validate($array, $save);
    }

}

?>
