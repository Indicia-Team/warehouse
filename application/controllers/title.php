<?php
/**
 * INDICIA
 * @link http://code.google.com/p/indicia/
 * @package Indicia
 */

/**
 * Title page controller
 *
 *
 * @package Indicia
 * @subpackage Controller
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @author xxxxxxx <xxx@xxx.net> / $Author$
 * @copyright xxxx
 * @version $Rev$ / $LastChangedDate$
 */
class Title_Controller extends Gridview_Base_Controller {

	/**
     * Constructor
     */
	public function __construct()
	{
		parent::__construct('title', 'title', 'title/index');
		$this->columns = array(
			'title'=>''
			);
		$this->pagetitle = "Titles";
		$this->model = ORM::factory('title');
	}

	/**
	 * Action for title/create page/
	 * Displays a page allowing entry of a new title.
	 */
	public function create()
	{
		if (!$this->page_authorised())
		{
			$this->access_denied();
		}
		else
		{
	        $this->setView('title/title_edit', 'Title');
		}
	}

    /**
     * Action for title/edit page
     * Edit person title data
     */
	public function edit($id  = null)
	{
		if (!$this->page_authorised())
		{
			$this->access_denied();
		}
		else if ($id == null)
        {
	   		$this->setError('Invocation error: missing argument', 'You cannot call edit a title without an ID');
        }
        else
        {
            $this->model = new Title_Model($id);
            $this->setView('title/title_edit', 'Title');
        }
	}

	public function page_authorised ()
	{
		return $this->auth->logged_in('CoreAdmin');
	}
}
?>
