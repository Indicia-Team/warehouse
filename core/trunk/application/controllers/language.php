<?php
/**
 * INDICIA
 * @link http://code.google.com/p/indicia/
 * @package Indicia
 */

/**
 * Language page controller
 *
 *
 * @package Indicia
 * @subpackage Controller
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @author xxxxxxx <xxx@xxx.net> / $Author$
 * @copyright xxxx
 * @version $Rev$ / $LastChangedDate$
 */
class Language_Controller extends Gridview_Base_Controller {

	/**
     * Constructor
     */
	public function __construct()
	{
		parent::__construct('language', 'language', 'language/index');
		$this->columns = array(
			'iso'=>'',
			'language'=>'');
		$this->pagetitle = "Languages";
		$this->model = ORM::factory('language');
	}

	/**
	 * Action for language/create page/
	 * Displays a page allowing entry of a new language.
	 */
	public function create()
	{
		if (!$this->page_authorised())
		{
			$this->access_denied();
		}
		else
		{
	        $this->setView('language/language_edit', 'Language');
		}
	}

    /**
     * Action for language/edit page
     * Edit website data
     */
	public function edit($id  = null)
	{
		if (!$this->page_authorised())
		{
			$this->access_denied();
		}
		else if ($id == null)
        {
	   		$this->setError('Invocation error: missing argument', 'You cannot call edit language without an ID');
        }
        else
        {
            $this->model = new Language_Model($id);
            $this->setView('language/language_edit', 'language');
        }
	}

	public function page_authorised ()
	{
		return $this->auth->logged_in('CoreAdmin');
	}
}
?>
