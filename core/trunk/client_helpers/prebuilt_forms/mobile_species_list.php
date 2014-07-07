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
 
class iform_mobile_species_list{

  
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
     
     $species = array(
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
     
     return $species;
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

    $list = '<div id="tab" data-role="page" data-url="tab">';
    
    $list_entry_template = '<li data-corners="false" data-shadow="false" data-iconshadow="true" data-wrapperels="div" data-icon="arrow-r" data-iconpos="right" data-theme="c">
                              <a href="{href}">
                                 <img src="{pic}">
                                 <h3>{name}</h3>
                                 <p>{caption}</p>
                               </a>
                               <a href="app/form" onclick="Code.photoSwipe(\'a\', \'{gallery_id}\');" data-icon="action"> AAA</a>
                            </li>';
    $species_page_template = '<div id="{id}" data-role="page" data-url="{id}">
                                <header data-role="header" role="banner" >
                                    <a href="#" data-rel="back" data-icon="arrow-l" data-iconpos="left" data-role="button" >Back</a>
                                    <h1 role="heading" aria-level="1">{name}</h1>       
                                    <a href="#" onclick="Code.photoSwipe(\'a\', \'#{gallery_id}\');Code.PhotoSwipe.Current.show(0)" data-icon="eye"  data-role="button">Gallery</a>
                                </header>
                                <div data-role="content">
                                   <div class="gallery" id="{gallery_id}" style="display:none">
                                    {gallery}
                                   </div>
                                   <center>
                                    <img src="{pic}" >
                                   </center>
                                  {content}
                                </div>
                                <footer data-role="footer"></footer>
                              </div>';
    $picture_link_template = '<a href="{url}"><img src="{url}" /></a>';
    
 
    $args['species']= str_replace("\r\n", "", $args['species']);
    $species = json_decode($args['species'], true);
    
    $list .= '<ul data-role="listview" data-split-icon="gear" data-split-theme="d"> ';
    foreach ($species as $entry){
        $id = '#' . str_replace(" ", "_", $entry['name']);
        $list .= str_replace( array('{name}','{href}','{caption}', '{pic}'), array($entry['name'], $id, $entry['taxon'], $entry['pic']), $list_entry_template);
    }
    $list .= '</ul>';
    $list .= '</div>';
    
    //creating species specific pages
    foreach ($species as $entry){
        $id = str_replace(" ", "_", $entry['name']);
        $gallery_id = $id . "_gallery";
        $gallery = "";
        
        $content_keys = array('{taxon}', '{description}', '{distribution}', '{habitat}');
        $content_args = array($entry['taxon'], $entry['content']['description'], $entry['content']['distribution'], $entry['content']['habitat']);
        $content = str_replace($content_keys, $content_args, $args['content']);
            
        //creating gallery
        if (key_exists('gallery', $entry)){
          foreach ($entry['gallery'] as $gallery_pic){
            $gallery .= str_replace('{url}', $gallery_pic, $picture_link_template); 
          } 
        }
        
        $list .= str_replace(array('{id}', '{name}', '{gallery_id}', '{gallery}', '{content}', '{pic}'), array($id, $entry['name'], $gallery_id, $gallery, $content, $entry['pic']), $species_page_template);
    }
    
    
    


    return $list;
  }
  
  /**
   * Optional. Handles the construction of a submission array from a set of form values. 
   * Can be ommitted when the prebuilt form does not submit data via a form post.
   * For example, the following represents a submission structure for a simple
   * sample and 1 occurrence submission.
   * return data_entry_helper::build_sample_occurrence_submission($values);
   * @param array $values Associative array of form data values. 
   * @param array $args iform parameters. 
   * @return array Submission structure.
   * @todo: Implement or remove this method
   */
  public static function get_submission($values, $args) {
        
  }
  
  /**
   * Optional method to override the page that is redirected to after a successful save operation.
   * This allows the destination to be chosen dynamically.
   * @param array $values Associative array of form data values. 
   * @param array $args iform parameters. 
   * @return string Destination URL.
   * @todo: Implement or remove this method
   */
  public static function get_redirect_on_success($values, $args) {
        
  }  

}
