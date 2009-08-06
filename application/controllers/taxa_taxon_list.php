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
 * @subpackage Controllers
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL
 * @link 	http://code.google.com/p/indicia/
 */

/**
 * Controller providing CRUD access to the taxa that belong to a checklist.
 *
 * @package	Core
 * @subpackage Controllers
 */
class Taxa_taxon_list_Controller extends Gridview_Base_Controller
{

  private $taxonListId;
  private $taxonListName;

  public function __construct()
  {
    parent::__construct('taxa_taxon_list', 'gv_taxon_lists_taxon', 'taxa_taxon_list/index');
    $this->base_filter['parent_id']=null;
    $this->base_filter['preferred']='t';
    $this->columns = array(
      'taxon'=>'',
      'authority'=>'',
      'language'=>'',
    );
    $this->pagetitle = "Species";
    $this->pageNoUriSegment = 4;
    $this->model = ORM::factory('taxa_taxon_list');
  }

  private function formatScientificSynonomy(ORM_Iterator $res)
  {
    $syn = "";
    foreach ($res as $synonym)
    {
      if ($synonym->taxon->language->iso == "lat")
      {
  $syn .= $synonym->taxon->taxon;
  $syn .=	",".$synonym->taxon->authority."\n";
      }
    }
    return $syn;
  }

  private function formatCommonSynonomy(ORM_Iterator $res)
  {
    $syn = "";
    foreach ($res as $synonym)
    {
      if ($synonym->taxon->language->iso != "lat")
      {
        $syn .= $synonym->taxon->taxon;
        $syn .=	($synonym->taxon->language_id != null) ?
        ",".$synonym->taxon->language->iso."\n" :
        '';
      }
    }
    return $syn;
  }

  /**
  * Override the default page functionality to filter by taxon_list.
  */
  public function page($taxon_list_id, $page_no, $limit)
  {
    // At this point, $taxon_list_id has a value - the framework will trap the other case.
    // No further filtering of the gridview required as the very fact you can access the parent taxon list
    // means you can access all the taxa for it.
    if (!$this->taxon_list_authorised($taxon_list_id))
    {
      $this->access_denied('table to view records with a taxon list ID='.$taxon_list_id);
      return;
    }
    parent::page($page_no, $limit);
    $this->base_filter['taxon_list_id'] = $taxon_list_id;
    $this->pagetitle = "Species in ".ORM::factory('taxon_list',$taxon_list_id)->title;
    $this->view->taxon_list_id = $taxon_list_id;
    $this->upload_csv_form->staticFields = array
    (
      'taxon_list_id' => $taxon_list_id,
      'preferred' => 't'
    );    $this->upload_csv_form->returnPage = $taxon_list_id;

  }

  public function page_gv($taxon_list_id, $page_no, $limit)
  {
    $this->base_filter['taxon_list_id'] = $taxon_list_id;
    $this->view->taxon_list_id = $taxon_list_id;
    parent::page_gv($page_no, $limit);
  }

  public function edit($id,$page_no,$limit)
  {
    // At this point, $id is provided - the framework will trap the empty or null case.
    if (!$this->record_authorised($id))
    {
      $this->access_denied('record with ID='.$id);
      return;
    }
    // Generate model
    $this->model->find($id);
    $gridmodel = ORM::factory('gv_taxon_lists_taxon');

    // Add grid component
    $grid =	Gridview_Controller::factory(
      $gridmodel,
      $page_no,
      $limit,
      4
    );
    $grid->base_filter = $this->base_filter;
    $grid->base_filter['parent_id'] = $id;
    $grid->columns = $this->columns;
    $grid->actionColumns = array(
      'edit' => 'taxa_taxon_list/edit/£id£'
    );

    // Add items to view
    $vArgs = array(
      'taxon_list_id' => $this->model->taxon_list_id,
      'table' => $grid->display(),
      'synonomy' => $this->formatScientificSynonomy(
        $this->model->getSynonomy('taxon_meaning_id', $this->model->taxon_meaning_id)),
      'commonNames' => $this->formatCommonSynonomy(
        $this->model->getSynonomy('taxon_meaning_id', $this->model->taxon_meaning_id))
    );
    $this->setView('taxa_taxon_list/taxa_taxon_list_edit', 'Taxon', $vArgs);

  }


  // Auxilliary function for handling Ajax requests from the edit method gridview component
  public function edit_gv($id,$page_no,$limit)
  {
    $this->auto_render=false;

    $gridmodel = ORM::factory('gv_taxon_taxon_list');

    $grid =	Gridview_Controller::factory(
        $gridmodel,
        $page_no,
        $limit,
        4
    );
    $grid->base_filter = $this->base_filter;
    $grid->base_filter['parent_id'] = $id;
    $grid->columns =  $this->columns;
    $grid->actionColumns = array(
      'edit' => 'taxa_taxon_list/edit/£id£'
    );
    return $grid->display();
  }
  /**
  * Creates a new taxon given the id of the taxon_list to initially attach it to
  */
  public function create($taxon_list_id)
  {
    // At this point, $taxon_list_id has a value - the framework will trap the other case.
    if (!$this->taxon_list_authorised($taxon_list_id))
    {
      $this->access_denied('table to create records with a taxon list ID='.$taxon_list_id);
      return;
    }
    $this->taxonListId = $taxon_list_id;
    $this->taxonListName = ORM::factory('taxon_list', $taxon_list_id)->title;
    $this->model = ORM::factory('taxa_taxon_list');
    $parent = $this->input->post('parent_id', null);
    $this->model->parent_id = $parent;

    $vArgs = array
    (
    'table' => null,
    'taxon_list_id' => $taxon_list_id,
    'synonomy' => null,
    'commonNames' => null);

    $this->setView('taxa_taxon_list/taxa_taxon_list_edit', 'Taxon', $vArgs);

  }

  public function save()
  {
    $_POST['preferred'] = 't';
    if (!array_key_exists('language_id', $_POST) || !is_numeric($_POST['language_id']))
      $_POST['language_id']=2; // latin
    // If we have an image, upload it and set the image path as required.
    $ups = Kohana::config('indicia.maxUploadSize');
    syslog(LOG_DEBUG, "Maximum upload size is $ups.");
    $_FILES = Validation::factory($_FILES)->add_rules(
      'image_upload', 'upload::valid', 'upload::required',
      'upload::type[png,gif,jpg]', "upload::size[$ups]"
    );
    if ($_FILES->validate())
    {
      $fTmp = upload::save('image_upload');
      syslog(LOG_DEBUG, "Media validated and saved as $fTmp.");
      $_POST['image_path'] = array_pop(explode('/', $fTmp));
    }
    else
    {
      syslog(LOG_DEBUG, "Media did not validate.");
    }
    parent::save();
  }

  protected function wrap($array, $linkFk = false)
  {
    $sa = array(
      'id' => 'taxa_taxon_list',
      'fields' => array(),
      'fkFields' => array(),
      'superModels' => array(),
      'metaFields' => array()
    );

    // Declare which fields we consider as native to this model
    $nativeFields = array_intersect_key($array, $this->model->table_columns);

    // Use the parent method to wrap these
    $sa = parent::wrap($nativeFields, $linkFk);

    // Declare parent models
    if (array_key_exists('taxon_meaning_id', $array) == false ||
      $array['taxon_meaning_id'] == '')
    {
      $sa['superModels'][] = array(
        'fkId' => 'taxon_meaning_id',
        'model' => parent::wrap(
          array_intersect_key($array, ORM::factory('taxon_meaning')->table_columns),
          $linkFk,
          'taxon_meaning'
        )
      );
    }

    $taxonFields = array_intersect_key($array, ORM::factory('taxon')
    ->table_columns);
    if (array_key_exists('fk_language', $array))
    {
      $taxonFields['fk_language'] = $array['fk_language'];
    }
    if (array_key_exists('fk_taxon_group', $array))
    {
      $taxonFields['fk_taxon_group'] = $array['fk_taxon_group'];
    }
    if (array_key_exists('taxon_id', $array) && $array['taxon_id'] != '')
    {
      $taxonFields['id'] = $array['taxon_id'];
    }
    $sa['superModels'][] = array
    (
      'fkId' => 'taxon_id',
      'model' => parent::wrap($taxonFields, $linkFk, 'taxon')
    );

    if (array_key_exists('synonomy', $array))
    {
      $sa['metaFields']['synonomy'] = array(
        'value' => $array['synonomy']
      );
    }

    if (array_key_exists('commonNames', $array))
    {
      echo "here";
      $sa['metaFields']['commonNames'] = array(
        'value' => $array['commonNames']
      );
    }

    return $sa;
  }

  /**
  * Overrides the fail functionality to add args to the view.
  */
  protected function submit_fail()
  {
    $mn = $this->model->object_name;
    $vArgs = array(
      'taxon_list_id' => $this->model->taxon_list_id,
      'synonomy' => null,
      'commonNames' => null,
    );
    $this->setView($mn."/".$mn."_edit", ucfirst($mn), $vArgs);
  }

  protected function record_authorised ($id)
  {
    // note this function is not accessed when creating a record
    // for this controller, any null ID taxa_taxon_list can not be accessed
    if (is_null($id)) return false;
    $taxa = new Taxa_taxon_list_Model($id);
    // for this controller, any taxon_list that does not exist can not be accessed.
    // ie prevent sly creation using the edit function
    if (!$taxa->loaded) return false;
    return ($this->taxon_list_authorised($taxa->taxon_list_id));
  }

  protected function taxon_list_authorised ($id)
  {
    // for this controller, any null ID taxon_list can not be accessed
    if (is_null($id)) return false;
    if (!is_null($this->gen_auth_filter))
    {
      $taxon_list = new Taxon_list_Model($id);
      // for this controller, any taxon_list that does not exist can not be accessed.
      if (!$taxon_list->loaded) return false;
      return (in_array($taxon_list->website_id, $this->gen_auth_filter['values']));
    }
    return true;
  }

}
?>
