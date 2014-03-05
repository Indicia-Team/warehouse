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
 * @package	Survey cleanup
 * @subpackage Controllers
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL
 * @link 	http://code.google.com/p/indicia/
 */

/**
 * Controller class for the survey cleanup plugin module.
 */
class Survey_cleanup_Controller extends Indicia_Controller {

  /**
   * Load the selected survey into the view
   */
  public function index() {
    $this->view = new View('survey_cleanup/index');
    $this->template->content = $this->view;
    if ($this->uri->total_arguments()>0) {
      $this->view->survey = ORM::Factory('survey', $this->uri->argument(1));
    }
  }
  
  public function cleanup() {
    $this->auto_render=false;
    if (empty($_POST['survey_id']) || empty($_POST['mode'])) {
      header(' ', true, 400);
      $this->auto_render=false;
      echo 'Cannot cleanup without a survey ID and mode';
      return;
    }
    $survey=ORM::Factory('survey', $_POST['survey_id']);
    if (!($this->auth->logged_in('CoreAdmin') || $this->auth->has_website_access('admin', $survey->website_id))) {
      header(' ', true, 401);
      echo 'Access denied';
      return;
    }
    $occListQuery = 'select o.id, o.sample_id  into temporary occlist from occurrences o ' .
        'join samples s on s.id=o.sample_id and s.survey_id=' . $survey->id;
    switch ($_POST['mode']) {
      case 'deleted':
        $occListQuery .= ' where o.deleted=true';
        break;
      case 'test':
        $occListQuery .= " where o.record_status='T'";
        break;
      case 'all':
        // no extra filter
        break;
      default:
        header(' ', true, 400);
        echo 'Invalid mode parameter';
        return;
    }
    $this->database = new Database();
    $this->database->query($occListQuery);
    $this->database->query('delete from occurrence_attribute_values where occurrence_id in (select id from occlist)');
    $this->database->query('delete from occurrence_comments where occurrence_id in (select id from occlist)');
    $this->database->query('delete from occurrence_images where occurrence_id in (select id from occlist)');
    $this->database->query('delete from determinations where occurrence_id in (select id from occlist)');
    // the number of occurrences deleted is the fact we need to report back
    $qry = $this->database->query('delete from occurrences where id in (select id from occlist)');
    $count=$qry->count();
    $this->database->query('delete from cache_occurrences where id in (select id from occlist)');
    // remove any samples that this query has left as empty
    $this->database->query('select s.id, s.parent_id into temporary smplist from samples s '.
        'join occlist o on o.sample_id=s.id ' .
        'left join occurrences occ on occ.sample_id=s.id '.
        'where occ.id is null');
    // first any child samples    
    $this->database->query('delete from sample_attribute_values where sample_id in (select id from smplist)');
    $this->database->query('delete from sample_comments where sample_id in (select id from smplist)');
    $this->database->query('delete from sample_images where sample_id in (select id from smplist)');
    $this->database->query('delete from samples where id in (select id from smplist)');
    // then the parents
    $this->database->query('select s.id into temporary parentlist from samples s '.
        'join smplist child on child.parent_id=s.id ' .
        'left join samples smpcheck on smpcheck.id=s.id '.
        'where smpcheck.id is null');
    $this->database->query('delete from sample_attribute_values where sample_id in (select id from parentlist)');
    $this->database->query('delete from sample_comments where sample_id in (select id from parentlist)');
    $this->database->query('delete from sample_images where sample_id in (select id from parentlist)');
    $this->database->query('delete from samples where id in (select id from parentlist)');
    // cleanup
    $this->database->query('drop table occlist');
    $this->database->query('drop table smplist');
    $this->database->query('drop table parentlist');
    echo "$count occurrences deleted";
  }
  
}
?>