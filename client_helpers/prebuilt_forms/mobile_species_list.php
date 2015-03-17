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
 * @package Client
 * @subpackage PrebuiltForms
 * @author  Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL 3.0
 * @link  http://code.google.com/p/indicia/
 */

/**
 * 
 * 
 * @package Client
 * @subpackage PrebuiltForms
 * @todo Provide form description in this comment block.
 * @todo Rename the form class to iform_...
 */
require_once("mobile_sample_occurrence.php");

global $list_templates;
$list_templates['gallery'] = <<<'EOD'
      <div class="gallery" id="{gallery_id}" style="display:none">
           {gallery}
      </div>
EOD;
$list_templates['list_entry'] = <<<'EOD'
      <li data-corners="false" data-shadow="false" data-iconshadow="true"
      data-wrapperels="div" data-icon="arrow-r" data-iconpos="right" data-theme="c">
        <a href="{href}">
          <img src="{pic}">
          <h3>{name}</h3>
          <p>{caption}</p>
        </a>
        <a href="{form_href}" data-icon="action" data-ajax="false">Record</a>
      </li>
EOD;
$list_templates['gallery_link'] = '<div class="ui-btn-right"
data-role="controlgroup" data-type="horizontal"><a href="#" onclick="app.navigation.galleries[\'{gallery_id}\'].show(0)"' .
  'data-icon="eye" data-iconpos="notext" data-role="button">Gallery</a></div>';

$list_templates['picture_link'] = '<a href="{url}"><img src="{url}" /></a>';


class iform_mobile_species_list{


  /**
   * The list of JQM pages in a structured array.
   *
   * ATTR
   *
   * Array element format:
   *  ATTR => [],
      CONTENT => [
        HEADER =>  [
          ATTR => [],
          CONTENT => []
          ],
        CONTENT => [
          ATTR => [],
          CONTENT => []
          ],
        FOOTER =>  [
          ATTR => [],
          CONTENT => []
          ]
        ]
      ]
   * @var array
   */
  protected $pages_array = array();

  /** 
   * Return the form metadata. Note the title of this method includes the name of the form file. This ensures
   * that if inheritance is used in the forms, subclassed forms don't return their parent's form definition.
   * @return array The definition of the form.
   * @todo rename this method.
   */
  public static function get_mobile_species_list_definition() {
    return array(
      'title'=>'Mobile Species List',
      'category' => 'Mobile',
      'helpLink'=>'<optional help URL>',
      'description'=>'Generates a species list.'
    );
  }
  
  /**
   * Get the list of parameters for this form.
   * @return array List of parameters that this form requires.
   * @todo: Implement this method
   */
  public static function get_parameters() {
     return array(
        array(
          'name' => 'form_path',
          'caption' => 'Form Path',
          'description' => 'Path to the form where the species recording is linked.',
          'type' => 'textfield',
          'required' => TRUE
        ),
        array(
          'name' => 'species',
          'caption' => 'Species list',
          'description' => 'Define a list of species with various configuration options.',
          'type' => 'jsonwidget',
          'schema' => '{
              "type":"seq",
              "title":"Species List",
              "sequence":
              [
                {
                  "type":"map",
                  "title":"props",
                  "mapping": {
                      "name":{"type":"str", "title": "name", "required":"true"},
                      "taxon":{"type":"str", "title": "taxon", "required":"true"},
                      "taxon_id":{"type":"str", "title": "taxon id", "required":"true"},
                      "pic":{"type":"str", "title": "pic", "required":"true"},
                      "content":{"type":"map", "title":"content", 
                                 "mapping":{description:{"type":"str", "title":"description"},
                                            distribution:{"type":"str", "title":"distribution"},
                                            habitat:{"type":"str", "title":"habitat"}
                                 }
                      },
                      "gallery":{"type":"seq", "title":"gallery", 
                                 "sequence":[{"type":"str","title":"pic" }]
                      }
                      
                  }
                }
              ]
              
            }',
          'required' => false
          ),
          array(
            'name' => 'content',
            'caption' => 'Content Template',
            'description' => 'Keywords in {} are replaced with arguments passed in Species list',
            'type' => 'textarea',
            'default' => '
              <center>
                <ul data-role="listview" data-inset="true" style="max-width:800px;">
                    <li><strong>Scientific name</strong>: <p><em>{taxon}</em></p></li>
                    <li ><strong>Description</strong>: 
                         <p style="white-space: normal;">{description}</p>
                    </li>
                    <li><strong>Distribution</strong>: 
                         <p style="white-space: normal;">{distribution}</p></li>
                    <li><strong>Habitat</strong>: 
                         <p style="white-space: normal;">{habitat}</p></li>
                </ul>
              </center>'
          )
        );
  }
  
  /**
   * Return the generated form output.
   * @param array $args List of parameter values passed through to the form depending on how the form has been configured.
   * This array always contains a value for language.
   * @param object $node The Drupal node object.
   * @param array $response When this form is reloading after saving a submission, contains the response from the service call.
   * Note this does not apply when redirecting (in this case the details of the saved object are in the $_GET data).
   * @return Form HTML.
   * @todo: Implement this method 
   */
  public static function get_form($args, $node, $response=null) {
    global $pages_array;
    global $list_templates;
    iform_load_helpers(array('mobile_entry_helper'));

    //generate list page
    $args['species']= str_replace("\r\n", "", $args['species']);
    $species = json_decode($args['species'], true);

    $list = '<ul data-role="listview" data-split-icon="gear" data-split-theme="d"> ';
    foreach ($species as $entry){
        $id = '#' . str_replace(" ", "_", $entry['name']);
        $form_href = $args['form_path'] . '?taxon=' . $entry['taxon_id'];
        $list .= str_replace( array('{name}','{href}','{caption}', '{pic}', '{form_href}'),
          array($entry['name'], $id, $entry['taxon'], $entry['pic'], $form_href),
          $list_templates['list_entry']);
    }
    $list .= '</ul>';

    $caption = "List";
    $caption = "<h1 id='" . $caption . "_heading'>" . $caption . "</h1>";
    $page = static::getFixedBlankPage("list", $caption);
    $page[JQM_CONTENT][JQM_CONTENT][JQM_CONTENT][] = $list;
    $pages_array[] = $page;

    //creating species specific pages
    foreach ($species as $entry){
        $id = str_replace(" ", "_", $entry['name']);
        $gallery_id = $id . "_gallery";
        $gallery = "";

        $content_keys = array('{taxon}', '{description}', '{distribution}', '{habitat}');
        $content_args = array($entry['taxon'], $entry['content']['description'], $entry['content']['distribution'], $entry['content']['habitat']);
        $content = str_replace($content_keys, $content_args, $args['content']);
            
        //creating gallery
        if (array_key_exists('gallery', $entry)){
          foreach ($entry['gallery'] as $gallery_pic){
            $gallery .= str_replace('{url}', $gallery_pic, $list_templates['picture_link']);
          } 
        }
        //todo: add _ instead of spaces in id
        $caption = "<h1 id='" . $entry['name'] . "_heading'>" . $entry['name'] . "</h1>";
        $page = static::getFixedBlankPage($id, $caption);

        //header
        $page[JQM_CONTENT][JQM_HEADER][JQM_CONTENT][] = str_replace('{gallery_id}',
          $gallery_id, $list_templates['gallery_link']);

        //content
        $page[JQM_CONTENT][JQM_CONTENT][JQM_CONTENT][] = str_replace(array('{gallery_id}', '{gallery}'),
          array($gallery_id, $gallery), $list_templates['gallery']);

        $page[JQM_CONTENT][JQM_CONTENT][JQM_CONTENT][] = '<center><img src="' .
         $entry['pic'] . '"></center>';
        $page[JQM_CONTENT][JQM_CONTENT][JQM_CONTENT][] = $content;
        $pages_array[] = $page;
    }

    //render pages
    $r = "";
    foreach($pages_array as $cur_page){
      $r .= static::renderOnePage($cur_page);
    }

    return $r;
  }
  public static function renderOnePage($page){
    return iform_mobile_sample_occurrence::renderOnePage($page);
  }

  public static function getFixedBlankPage($id, $caption){
    return iform_mobile_sample_occurrence::get_blank_page($id, $caption);
  }
}
