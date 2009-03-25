<?php
/**
 * INDICIA
 * @link http://code.google.com/p/indicia/
 * @package Indicia
 */

/**
 * Occurrence_Attributes_Website Model
 *
 *
 * @package Indicia
 * @subpackage Model
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @author xxxxxxx <xxx@xxx.net> / $Author$
 * @copyright xxxx
 * @version $Rev$ / $LastChangedDate$
 */
class Occurrence_attributes_website_Model extends ORM
{

    protected $has_one = array(
			'occurrence_attribute',
    		'website',
	);
    protected $belongs_to = array(
			'created_by'=>'user',
	);

	public function validate(Validation $array, $save = FALSE) {
		// uses PHP trim() to remove whitespace from beginning and end of all fields before validation

		$array->pre_filter('trim');

		$this->occurrence_attribute_id = $array['occurrence_attribute_id'];
		$this->website_id = $array['website_id'];
		$this->restrict_to_survey_id = $array['restrict_to_survey_id'];
		
		return parent::validate($array, $save);
	}
	
	public function set_metadata() {
		// Set up the created and updated metadata for the record
		if (!$this->id) {
			$this->created_on = date("Ymd H:i:s");
			$this->created_by_id = 1; // dummy user
		}
	}
	
}

?>
