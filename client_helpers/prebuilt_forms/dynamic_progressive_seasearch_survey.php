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
 * @package    Client
 * @subpackage PrebuiltForms
 * @author    Indicia Team
 * @license    http://www.gnu.org/licenses/gpl.html GPL 3.0
 * @link     http://code.google.com/p/indicia/
 */

/**
 * Prebuilt Indicia data entry form.
 * NB has Drupal specific code. Relies on presence of IForm loctools and IForm Proxy.
 *
 * @package    Client
 * @subpackage PrebuiltForms
 */

require_once('includes/dynamic.php');
require_once('dynamic_sample_occurrence.php');

class iform_dynamic_progressive_seasearch_survey extends iform_dynamic_sample_occurrence {
 
  /**
   * @var array List of custom sample attributes in array keyed by caption. Helps to make this form
   * ID independent.
   */
  private static $attrsByCaption = array();
 
  /**
   * Return the form metadata.
   * @return string The definition of the form.
   */
  public static function get_dynamic_progressive_seasearch_survey_definition() {
    return array(
      'title'=>'Progressive survey form for Seasearch',
      'category' => 'Forms for specific surveying methods',
      'description'=>'A form based on Dynamic Sample Occurrence where habitats are sub-samples and occurrences are  ' .
        'attached to a third sample layer. Images are loaded onto the form first.',
    );
  }

  /**
   * Get the list of parameters for this form.
   * @return array List of parameters that this form requires.
   */
 
  public static function get_parameters() {    
    $retVal = array_merge(
      parent::get_parameters(),
      array(
        array(
          'name'=>'habitat_smpAttr_cluster_ids',
          'caption'=>' Habitat Attribute Cluster Ids',
          'description'=>'Id of the first sample attribute associated with a habitat. Required so the system knows how to create habitat attribute groupings.
          Enter the Ids in the order they will appear on the Habitats tab where the attribute at the top appears first.',
          'type'=>'string',
          'group'=>'Other Settings'
        ),
        array(
          'name'=>'in_progress_sample_attr_id',
          'caption'=>'In Progress Sample Atrribute Id',
          'description'=>'The id of the custom attribute that holds whether a sample is in progress.',
          'type'=>'string',
          'group'=>'Other Settings'
        ),
        array(
          'name'=>'gpx_data_attr_id',
          'caption'=>'GPX Data Atrribute Id',
          'description'=>'The id of the custom attribute that holds the GPX file data that is uploaded.',
          'type'=>'string',
          'group'=>'Other Settings'
        ),
        array(
          'name'=>'dive_duration_attr_id',
          'caption'=>'Dive Duration Attribute Id',
          'description'=>'The id of the custom attribute that holds the dive duration.',
          'type'=>'string',
          'group'=>'Other Settings'
        ),  
        array(
          'name'=>'dive_start_time_attr_id',
          'caption'=>'Dive Start Time Attribute Id',
          'description'=>'The id of the custom attribute that holds the dive start time.',
          'type'=>'string',
          'group'=>'Other Settings'
        ),    
        array(
          'name'=>'exif_date_time_attr_id',
          'caption'=>'Exif date time attr id',
          'description'=>'The id of the custom attribute that holds the date and times from the photo exifs. Although already stored in the media database tables, this allows for quick access by Javascript.',
          'type'=>'string',
          'group'=>'Other Settings'
        ),   
        array(
          'name'=>'gps_sync_warning',
          'caption'=>'GPS Sync Warning',
          'description'=>'Warning displayed to the user if they try to upload a GPX file, it should warn them that
                the time needs to be synchronised between the camera and the GPS device otherwise the automatic GPS selection for images will fail.
                If this option is not filled in then the warning will not be displayed.',
          'type'=>'textarea',
          'group'=>'Other Settings'
        ),  
        array(
          'name'=>'no_photos_with_date_warning',
          'caption'=>'No Photos With Date Warning',
          'description'=>'Warning displayed to user if they change the dive date to a date that is not associated with any of the photos.
                Note that the user is able to continue anyway, it is simply a warning.
                If this option is not filled in, then the warning will not be displayed.',
          'type'=>'textarea',
          'group'=>'Other Settings'
        ),   
        //TODO could put in a default form structure
      )
    );
    return $retVal;
  }
 
  /*
   * Function that is run when the user selects to upload a GPX file.
   */
  protected static function get_control_uploadgpxfile($auth, $args, $tabAlias, $options) {
    $r = '<input type="file" id="file_upload"><br>';
    //Read the file, load it into an attribute, also add up all the trackpoints in the file and then 
    //average them to make the spatial reference on the sample (as the main sample only has a single spatial
    //reference it makes sense to use an average of all positions),this can be overridden.
    data_entry_helper::$javascript .= "
    var input_file = document.getElementById('file_upload');
    input_file.onchange = function() {
      $('#imp-sref-system').val(4326);
      //Need a counter for the length of indiciaData.gpxLatLon as it is a string rather than an array
      var gpxLatLonLength = 0;
      var file = this.files[0];
      var el;
      var trkTag;
      var timeTag;
      var latAcc = 0;
      var lonAcc = 0;
      var reader = new FileReader();
      reader.onload = function(ev) {
        //Create fake element
        el = document.createElement( 'div' );
      el.innerHTML = ev.target.result;
      //Split up text that has been read from file into the track points
        trkTag=el.getElementsByTagName(\"trkpt\");
      //Save the spatial references (trackpoints) and times to an attribute. Cycle through each trackpoint and find the time tags.
      //Attribute format is lat,lon,time;lat,long,time;lat,lon,time etc (uses a semi-colon as as the time from the file includes colons)
      for (i=0;i<trkTag.length;i++) {
        //Get time as text from within trackpoint
        timeTag=trkTag[i].innerHTML.split('<time>')[1].split('</time>')[0];
          if (indiciaData.gpxLatLon) {
          indiciaData.gpxLatLon=indiciaData.gpxLatLon+';'+trkTag[i].getAttribute(\"lat\")+','+trkTag[i].getAttribute(\"lon\")+','+timeTag;
            gpxLatLonLength++;
          } else {
          indiciaData.gpxLatLon=trkTag[i].getAttribute(\"lat\")+','+trkTag[i].getAttribute(\"lon\")+','+timeTag;
            gpxLatLonLength++;
          }
        latAcc = latAcc + parseFloat(trkTag[i].getAttribute(\"lat\"));
        lonAcc = lonAcc + parseFloat(trkTag[i].getAttribute(\"lon\"));
        }   
        latAcc=(latAcc/gpxLatLonLength).toFixed(10);
        lonAcc=(lonAcc/gpxLatLonLength).toFixed(10);
        if (latAcc>=0) {
          latAcc=latAcc+'N';
        } else {
          latAcc=(latAcc*-1)+'S';
        }
        if (lonAcc>=0) {
          lonAcc=lonAcc+'E';
        } else {
          lonAcc=(lonAcc*-1)+'W';
        }
        //When the GPX data points are created and then averaged, pass in the spatial reference into the existing function that
        //will calculate the reference in 50:11.1111N 2:22.2222W format, this function will also update the map.
        var data = new Object();
        data.sref=latAcc+ ' ' + lonAcc;
        setClickedPosition(data);";
        data_entry_helper::$javascript .= '
        if (indiciaData.gpxLatLon) {
          $("#smpAttr\\\\:'.$args['gpx_data_attr_id'].'").val(indiciaData.gpxLatLon);
          }
        };
      // Read as plain text
      reader.readAsText(file);  
      };';
    return $r;
  }
 

  /*
   * Control which displays a group of attributes that represent a habitat.
   * This works by having a hidden group of clonable attributes on the page.
   * When a new habitats is created on screen, this hidden group is cloned with the fields in the cloned habitat
   * preceded with the words "new_sample_sub_sample:<sub sample idx number>".
   * If the page loads in edit page, then the fields in each group are preceded with "existing_sample_sub_sample:<sub sample id>".
   */
  protected static function get_control_setuphabitats($auth, $args, $tabalias, $options) {
    $NextHabitatNum=1;
    $r='';
    $r .= "<div id=\"habitats-setup\">\n";
    if (!empty($_GET['sample_id'])) {
      //First load any existing sub-samples
      $existingHabitatSubSamples = data_entry_helper::get_report_data(array(
        'dataSource'=>'library/samples/subsamples',
        'readAuth'=>$auth['read'],
        'extraParams'=>array('parent_sample_id' => $_GET['sample_id'])
      ));
      $attrOptions = self::get_attr_specific_options($options);
      if (!empty($existingHabitatSubSamples)) {
        $existingHabitatSubSamplesIds=array();
        //Setup the html as we initially see it on the page
        $r .= self::initial_habitat_html_setup($existingHabitatSubSamples,$args,$auth,$NextHabitatNum,$attrOptions,$existingHabitatSubSamplesIds);
      }
    }
    //Add New Habitat button
    data_entry_helper::$javascript.="$('#tab-definehabitats').append('<input id=\"add-new-habitat\" type=\"button\" value=\"Add New Habitat\">');\n";
    //Pass data to javascript
    if (!empty($_GET['sample_id']))
      data_entry_helper::$javascript.="var mainSampleId = ".$_GET['sample_id'].";";   
    else
      data_entry_helper::$javascript.="var mainSampleId = '';";
    if (!empty($existingHabitatSubSamplesIds))
      data_entry_helper::$javascript.="var existingHabitatSubSamplesIds=".json_encode($existingHabitatSubSamplesIds).";";
    else
      data_entry_helper::$javascript.="var existingHabitatSubSamplesIds=null;";
    
    
    //Cycle through each habitat, and call a function that will setup the names appropriately ready for submission
    //TODO Note
    //Sorry, when I originally did this I numbered the habitats from 1, possibly should of used 0, as I think that might of been more elegant on second thoughts.
    //Can't move to javascript file easily as PHP variable referenced
    data_entry_helper::$javascript.="
    var nextHabitatNum=$NextHabitatNum;
    var currentHabitatNum=nextHabitatNum-1;
    var nextHabitatIdSampleId;
    //Need nextHabitatNum-1 as the habitats are numbered from 1 not 0
    for (var i = 0; i<nextHabitatNum-1; i++) {
      //existingHabitatSubSamplesIds should always exist at this point, but put an extra test anyway
      if (existingHabitatSubSamplesIds) {
        habitatIdSampleId=existingHabitatSubSamplesIds[i];
      }
      //Need to add 1 to i as habitats are numbered from 1
      setupSubSampleAttrsForHabitat(i+1,false, habitatIdSampleId);
    }
    $('#add-new-habitat').click(function() {
      createNewHabitat();
    });";
    //When creating a new habitat, we make a clone of a hidden cloneable habitat
    //Call the function that will setup the names of the attributes so they are ready for submission
    //Add a hidden field to allow the submission handler to know what the parent of the sub-sample is
    data_entry_helper::$javascript.="function createNewHabitat() {
      var panelId='habitat-panel-'+nextHabitatNum;     
      $('#habitats-setup').append('<div id=\"'+panelId+'\" style=\"display:none;\">');
      $('#habitats-setup').append('<hr width=\"50%\">');
      $('.habitat-attr-cloneable').each(function(index) {
        $('#'+panelId).append($(this).clone().show().removeAttr('class'));
      });
      
      setupSubSampleAttrsForHabitat(nextHabitatNum,true, null);
      $('#habitat-panel'+'-'+nextHabitatNum).append('<input id=\"new_sample_sub_sample:'+nextHabitatNum+':sample:parent_id\" name=\"new_sample_sub_sample:'+nextHabitatNum+':sample:parent_id\" type=\"hidden\" value=\"'+mainSampleId+'\">');
      $('#habitat-panel'+'-'+nextHabitatNum).show();
      currentHabitatNum++;
      nextHabitatNum++;
    }";
    
    $r .= "</div>\n";  
    $cloneableAttrs=explode(',',$args['habitat_smpAttr_cluster_ids']);
    
    foreach ($cloneableAttrs as $cloneableAttrId) {
      data_entry_helper::$javascript.="$('#ctrl-wrap-smpAttr-".$cloneableAttrId."').attr('class','habitat-attr-cloneable').hide();\n";
    }
    return $r;
  }
 
  /*
   * Setup the html for existing habitats as we initially see it on the page
   */
  protected static function initial_habitat_html_setup($existingHabitatSubSamples,$args,$auth,&$NextHabitatNum,$attrOptions,&$existingHabitatSubSamplesIds) {
    $habitatSmpAttrIds=explode(',',$args['habitat_smpAttr_cluster_ids']);
    $r='';
    //Cycle through each existing habitat
    foreach ($existingHabitatSubSamples as $habitat) {
      $existingHabitatSubSamplesIds[]=$habitat['id'];
      //Get the attributes associated with the habitat subsample
      $habitatAttrs = self::getAttributesForSample($args, $auth, $habitat['id']);
      //enclose the attributes in a numbered div
      $r .= "<div id=\"habitat-panel-$NextHabitatNum\">";
      //Cycle through the attributes for the habitat
      foreach ($habitatSmpAttrIds as $habitatSmpAttrId) {       
        //Only get sample attributes and then store them in an array so they can be passed to the existing function that builds the html
        $attrbuteArray=array();
        foreach ($habitatAttrs as $habitatAttr) {  
          if ($habitatSmpAttrId==$habitatAttr['sample_attribute_id']) {
            $attrbuteArray[]=$habitatAttr;  
          }
        }
        $r .= get_attribute_html($attrbuteArray, $args, array('extraParams' => $auth['read']), null, $attrOptions);
      }
      //When dealng with existing habitats, we need to supply the existing id to the submission in a hidden field
      $r .= '<input id="sample:id" name="sample:id" type="hidden" value="'.$habitat['id'].'">';
      $NextHabitatNum++;
      $r .= '</div><hr width="50%">';
    }
    return $r;
  }
 
  /*
   * Page that displays a list of habitat names and allows the user to allocate the photos to the habitats.
   * As the occurrences are displayed in order the photos was taken, and the habitats are saved in order, the user just
   * needs to drag habitat "splitter" rectangles to between the occurrence photos to indicate where that particular habitat comes to an end.
   * Then all previous photos are allocated to the habitat (providing not already allocated in this session).
   * There is a further set of habitat draggers which when dragged to a photo will always override that single photo with the habitat, even
   * if it is already allocated in this session.
   */
  protected static function get_control_linkhabitatstophotos($auth, $args, $tabalias, $options) {
    if (empty($options['imageMediaTypeId']))
      drupal_set_message('Please fill in the imageMediaTypeId option for the Link Habitats To Photos control.');
    $r='';
    //Need to add the query libraries manually
    drupal_add_library('system', 'ui.draggable');
    drupal_add_library('system', 'ui.droppable');
    drupal_add_library('system', 'ui.sortable');
    
    //Set up the splitters on the page so that they are draggable.
    //Also setup snapping into position if the user drags nearby.
    data_entry_helper::$javascript.="$('.habitat-dragzone').draggable({
      revert: true,
      snap: '.droppable-splitter',
      snapMode: 'inner',
      snapTolerance: 20
    });\n";
    
    //We are only interested in using taxa photos, not the sketches, so specify this as the media sub-type to display.
    $options['id']='seasearch-photos-for-habitats';
    $options['subType']='Image:Local:Seasearch_Photo';
    if (!empty($options['id'])&&!empty($options['subType']))   
      data_entry_helper::$javascript.="
      if (indiciaData.subTypes) {
        indiciaData.subTypes.push(['".$options['id']."','".$options['subType']."']);
      } else {
        indiciaData.subTypes=[['".$options['id']."','".$options['subType']."']];
      }\n";
    
    //The name of each habitat is saved to a sample attribute (as each habitat has its own sub-sample)
    $nameAttrId=$options['habitatNameId'];
    //Habitat name is mandatory otherwise the habitat would be nameless
    if (empty($nameAttrId))
      return '<div><h3>Please fill in the id of the habitat name attribute in the page configuration</h3></div>';
    //Get the habitat name data
    if (!empty($_GET['sample_id'])) {
      $habitats = data_entry_helper::get_report_data(array(
        'dataSource'=>'reports_for_prebuilt_forms/seasearch/habitats_for_parent_sample',
        'readAuth'=>$auth['read'],
        'extraParams'=>array('parent_sample_id' => $_GET['sample_id'], 'name_attr_id'=>$nameAttrId)
      ));
    } else {
      $habitats=array();
    }
    //Generate a colour for the habitat based on its index
    $habitatColours=array();
    if (!empty($habitats)) {
      $numberOfHabitats=count($habitats);
      foreach ($habitats as $habIdx=>$habitat) {
        $habitatColours[$habIdx]=self::generateHabitatColour($habIdx,$numberOfHabitats);
      }
    }
    //Draw to screen the control that will actually display the taxa images.
    $r .= self::taxa_image_to_link(array_merge(array(
      'table'=>'sample_medium',
      'readAuth' => $auth['read'],
      'caption'=>lang::get('Photos'),
      'readAuth'=>$auth['read']
    ), $options),$habitats,$args['dive_duration_attr_id'],$habitatColours);
    

    $habitatIds=array();
    $r.='<div class="habitats-div" style="float: left; width: 50%"><h3>Habitats</h3>';
    //Create the html to display the habitats and a splitter for each habitat
    if (!empty($habitats)) {
      $habitatCounter=0;
      foreach ($habitats as $habIdx=>$habitat) {
        $habitatIds[]=$habitat['id'];
        //Note we need a specific "color" attribute as well as a style, this is because if we use .css('color') jquery to retrieve a colour, it converts the hex to rgb(<val>,<val>,<val>) automatically. To get the raw hex when we need it, we need to store it in a separate attribute as well
        $r .= '<span id="habitat-'.$habitat['id'].'"><b>'.$habitat['name'].'</b>
        <span id="habitat-'.$habitat['id'].'-dragzone" color="'.'#'.$habitatColours[$habIdx].'" class="habitat-dragzone" style="border: 5px solid ; height: 100px; width: 10px; display: inline-block; color:'.'#'.$habitatColours[$habIdx].';"></span></span>';
        $habitatCounter++;
        if ($habitatCounter>4) {
          $r .= '<br>';
          $habitatCounter=0;
        }
      }
    }
    $r.='</div>';
    //There are two types are draggable habitat, one will set all previous photos to that habitat providing it has not 
    //already been allocated to a habitat in this session, the "override" drag strip will always override the single
    //photo that is it dragged to, even if previously allocated.
    $r.='<div class="habitats-div" style="float: left; width: 50%"><h3>Habitats - Override individual habitats</h3>';
    if (!empty($habitats)) {
      $habitatCounter=0;
      foreach ($habitats as $habIdx=>$habitat) {
        $habitatIds[]=$habitat['id'];
        //Note we need a specific "color" attribute as well as a style, this is because if we use .css('color') jquery to retrieve a colour, it converts the hex to rgb(<val>,<val>,<val>) automatically. To get the raw hex when we need it, we need to store it in a separate attribute as well
        $r .= '<span id="habitat-override-'.$habitat['id'].'"><b>'.$habitat['name'].'</b>
        <span id="habitat-override-'.$habitat['id'].'-dragzone" color="'.'#'.$habitatColours[$habIdx].'" class="habitat-dragzone habitat-override-dragzone"style="border: 5px solid ; height: 100px; width: 10px; display: inline-block; color:'.'#'.$habitatColours[$habIdx].';"></span></span>';
        $habitatCounter++;
        if ($habitatCounter>4) {
          $r .= '<br>';
          $habitatCounter=0;
        }
      }
    }
    $r.='</div>';
    $r.='<div style="float: left; width: 50%"><br>Drag these habitats to allocate the habitat to the photo and any previous photos. Note: any photos previously allocated using
      this control in this session will not be overidden, use the Override Individual Habitats control to override if needed.</div>';
    return $r;
  }
 
  /*
   * Control which actually displays the taxa photos to link to habitats
   * Displayed as part of get_control_linkhabitatstophotos
   */
  private static function taxa_image_to_link($options,$habitats, $diveDurationAttrId,$habitatColours) {
    iform_load_helpers(array('report_helper'));
    global $user;  
    //Use this report to return the photos
    $reportName = 'reports_for_prebuilt_forms/seasearch/get_media_for_all_sub_samples';
    $reportOptions=array(
      'readAuth' => $options['readAuth'],
      'dataSource'=> $reportName,
      'extraParams'=>array(
        'media_type_id'=>$options['imageMediaTypeId'],
      )
    );
    
    if (!empty($_GET['sample_id'])) {
      $reportOptions['extraParams']['sample_id']=$_GET['sample_id'];
      $photoResults = data_entry_helper::get_report_data($reportOptions);
      //Order using exif
      $photoResults = self::set_photo_order($photoResults);
     
      //NOTE: This function collects the path from configuration file, so that file needs to be setup correctly
      $uploadFolder = data_entry_helper::get_uploaded_image_folder();
      $r= '<div>';
      $photoCountPerRow=0;
      //Also display a splitter for the user to drag habitats onto
      foreach ($photoResults as $idx=> $photoData) {
        //New row if too many items in the row
        if ($photoCountPerRow>5) {
          $r .= '<br>';
          $photoCountPerRow=0;
        }
        $mediaItemNumber=$idx+1;
        if (!empty($photoData['sample_id'])) {
          if ($photoData['sample_id']==$_GET['sample_id']) {
            //Don't use colours if photos not allocated to habitat yet (first time page is opened)
            $style='border: 1px solid ; display: inline-block;';
            $habColour='black';
          } else {
            //Add a new colour to a photo border if is isn't assigned to the same habitat as the previous photo.
            //Otherwise the border will be same as previous habitat when loading page.
            if (empty($previousPhotoSampleId) || ($previousPhotoSampleId!=$photoData['sample_id'])) {
              foreach ($habitats as $habIdx=>$habitat) {
                if ($habitat['id']==$photoData['sample_id']) {
                  $style='border: 5px solid; display: inline-block; color: #'.$habitatColours[$habIdx].';';
                  $habColour=$habitatColours[$habIdx];
                }
              }
            }
          }
          $previousPhotoSampleId=$photoData['sample_id'];
          //Photos could be attached to second or third level samples
          if ($photoData['media_table']==='sample_medium')
            $sampleIdHolderControlName=$photoData['media_table'].':'.$photoData['id'].':sample_id';
          else
            $sampleIdHolderControlName=$photoData['media_table'].':'.$photoData['id'].':sample_id:'.$photoData['level_three_sample_id'];
          //Used when loading existing habitats
          $r .= '<input id="sub-sample-holder-for-media-number-'.$mediaItemNumber.'" type="hidden" name="'.$sampleIdHolderControlName.'" value="'.$photoData['sample_id'].'">';
          $r .= '<input id="'.$photoData['media_table'].':'.$photoData['id'].':image_path" type="hidden" name="'.$photoData['media_table'].':'.$photoData['id'].':image_path" value="'.$photoData['path'].'">';        
        }
        //Note we need a specific "color" attribute as well as a style, this is because if we use .css('color') jquery to retrieve a colour, it converts the hex to rgb(<val>,<val>,<val>) automatically. To get the raw hex when we need it, we need to store it in a separate attribute as well
        $r.='<span id="media-item-for-habitat-'.$photoData['id'].'" number="'.$mediaItemNumber.'" style="'.$style.'" color="'.$habColour.'"/><a href="'.$uploadFolder.''.$photoData['path'].'}"><img src="'.$uploadFolder.'thumb-'.$photoData['path'].'" title="'.$photoData['caption'].'" alt="'.$photoData['caption'].'"/><br>'.$photoData['caption'].'</a></span><span id="droppable-splitter-'.$mediaItemNumber.'" class="droppable-splitter" style="border: 1px solid ; height: 100px; width: 10px; display: inline-block;"></span>';
        $photoCountPerRow++;
      }
      
      if (!empty($photoResults)) {      
        $photoResultDecoded1 = json_decode($photoResults[0]['exif'],true);
        $photoResultDecoded2 = json_decode($photoResults[count($photoResults)-1]['exif'],true);
      }
      //The duration attribute should default to the difference between first and last photos (in minutes)
      //Although the dive duration is not displayed on this tab, it is easiest to calculate it here as this tab processes photos as well.
      if (!empty($photoResultDecoded1['EXIF']['DateTimeOriginal']) && !empty($photoResultDecoded2['EXIF']['DateTimeOriginal'])) {
        $difference = strtotime($photoResultDecoded2['EXIF']['DateTimeOriginal'])-strtotime($photoResultDecoded1['EXIF']['DateTimeOriginal']);
        //Default the duration using minutes.
        $difference = (integer)($difference/60);
        data_entry_helper::$javascript .= "
        if (!$('#smpAttr\\\\:".$diveDurationAttrId."').val()) {
          $('#smpAttr\\\\:".$diveDurationAttrId."').val('".$difference."');
        }\n";
      }

      //TODO
      //Perhaps handle photos without EXIF in a separate gallery?
      /*
      foreach ($photosWithoutExif as $unsortedPhotoId) {
        foreach ($photoResults as $photoData) {
          if ($photoData['id']==$unsortedPhotoId) {
            $r.='<div style="width:120px;"><div id="gallery-item-'.$photoData['id'].'" style="float:left" class="gallery-item"><a href="'.$uploadFolder.''.$photoData['the_text'].'}"><img src="'.$uploadFolder.'thumb-'.$photoData['the_text'].'" title="'.$photoData['caption'].'" alt="'.$photoData['caption'].'"/><br>'.$photoData['caption'].'</a></div><div id="splitter-'.$photoData['id'].'" class="splitter" style="border: 1px solid ; height: 100px; width: 10px; float:right;"></div></div>';
          }
        }
      }*/
      $r.= '</div>';
      return '<h3>Photos</h3>'.$r;
    }
  }
  
  /* 
   * Generates a colour for each habitat on the assign photo to habitat page by using the habitat index to generate the colour.
   */
  private static function generateHabitatColour($habIdx,$numberOfHabitats) {
    //If number of habitats is greater than 5 then the colour gradient would be too small,
    //so we can pretend it is 5 and repeat the colours.
    if ($numberOfHabitats>5) {
      $numberOfHabitats=5; 
    }
    //The size of the colour gradient decreases as the number of habitats increases.
    //Add 1 so we don't divide by 0.
    $stepSize=(integer)255/($numberOfHabitats+1);
    $red=20;
    //For each habitat colour increase blue and decrease green so we get a green to blue gradient
    $blue=((($habIdx%$numberOfHabitats)+1)*$stepSize);
    $green=255-((($habIdx%$numberOfHabitats)+1)*$stepSize);
    
    $hexRed=dechex($red);
    $hexGreen=dechex($green);
    $hexBlue=dechex($blue);
    return $hexRed.$hexGreen.$hexBlue;
  }
  
  /*
   * Order function used by usort function to sort photo array
   */
  private static function orderByDate($a, $b) {
    $aDateTime = json_decode($a['exif'],true);
    $aDateTime = strtotime($aDateTime['EXIF']['DateTimeOriginal']);
    $bDateTime = json_decode($b['exif'],true);
    $bDateTime = strtotime($bDateTime['EXIF']['DateTimeOriginal']);
    if ($aDateTime==$bDateTime)
      return 0;
    return ($aDateTime < $bDateTime) ? -1 : 1;
  }

  /*
   * Order the photos using the exif data held in the JPG files so that the photos are in the order they were taken.
   */
  private static function set_photo_order($photoResults) {
    //TODO if a photo lacks exif data it currently isn't handled.
    $photosWithExif=array();
    //Order pictures by date
    foreach ($photoResults as $photoResult) {
      $photoResultDecoded = json_decode($photoResult['exif'],true);
      if (!empty($photoResultDecoded['EXIF']['DateTimeOriginal']))
        $photosWithExif[]=$photoResult;
    }         
    if (!empty($photosWithExif)) {
      usort($photosWithExif, "self::orderByDate");
    } else {
      $photosWithExif = array();
    }
    return $photosWithExif;
  }
  
  /*
   * Executed on page load
   */
  public static function get_form($args, $node) {
    //Don't use a submit button, as we are saving after each stage of the wizard, so just save on every Next button click. Use additional wizard page at the end as a
    //finish confirmation page, this doesn't need submitting.
    data_entry_helper::$javascript .= "$('#tab-submit').hide();\n";
    data_entry_helper::$javascript .= 'indiciaData.nid = "'.$node->nid."\";\n";
    //Use ajax saving so that we can save without full page reload on a lot of pages.
    data_entry_helper::$javascript .= 'indiciaData.ajaxUrl="'.url('iform/ajax/dynamic_progressive_seasearch_survey')."\";\n";
    if (empty($args['in_progress_sample_attr_id'])) {
      drupal_set_message('Please fill in the edit tab option for the In-Progress Sample attribute id');
      return false;
    }
    if (empty($args['gpx_data_attr_id'])) {
      drupal_set_message('Please fill in the edit tab option for the GPX Data attribute id');
      return false;
    }
    if (empty($args['habitat_smpAttr_cluster_ids'])) {
      drupal_set_message('Please fill in the option for the Habitat Sample Attribute Cluster');
      return false;
    } 
    if (empty($args['dive_duration_attr_id'])) {
      drupal_set_message('Please fill in the option for the Dive Duration attribute id');
      return false;
    } 
    if (empty($args['dive_start_time_attr_id'])) {
      drupal_set_message('Please fill in the option for the Dive Start Time attribute id');
      return false;
    }
    if (empty($args['exif_date_time_attr_id'])) {
      drupal_set_message('Please fill in the option for the Exif Date Times attribute id');
      return false;
    }
    //Hidden and displayed using jQuery
    $r='<div id="loading">
    <br>
    <br>
    <br>
    <h3>Preparing page...</h3>
    <br>
    <br>
    <br>
    </div>';
    //Hide the attribute that holds whether a sample is in progress or not
    //Also need to hide the label associated with the attribute.
    data_entry_helper::$javascript .= "
    $('#smpAttr\\\\:".$args['in_progress_sample_attr_id']."').hide();\n
    $('[for=smpAttr\\\\:".$args['in_progress_sample_attr_id']."]').hide();";
    //Same with attribute that holds GPX data
    data_entry_helper::$javascript .= "
    $('#smpAttr\\\\:".$args['gpx_data_attr_id']."').hide();\n
    $('[for=smpAttr\\\\:".$args['gpx_data_attr_id']."]').hide();";
    //etc
    data_entry_helper::$javascript .= "
    $('#smpAttr\\\\:".$args['exif_date_time_attr_id']."').hide();\n
    $('[for=smpAttr\\\\:".$args['exif_date_time_attr_id']."]').hide();";
    //Clean up the occurrences tab, remove unused images column on the bottom grid
    //Also remove the name of the Add photos column on main grid as the column is empty is edit mode, and obvious what it is in add mode.
    data_entry_helper::$javascript .= "
    $('#first-level-smp-occ-grid-images-0').remove();\n
    $('#third-level-smp-occ-grid-images-0').text('');";
    // A jquery selector for the element which must be at the top of the page when moving to the next page. Could be the progress bar or the
    // tabbed div itself.
    if (isset($options['progressBar']) && $options['progressBar']==true)
      data_entry_helper::$javascript .= "indiciaData.topSelector="."'.wiz-prog'".";";
    else
      data_entry_helper::$javascript .= "indiciaData.topSelector="."'#controls'".";";
    $options['progressBar']=true;
    
    //Safer to still initialise $getSampleId if there is no sample_id in the $_GET, avoid an error
    if (!empty($_GET['sample_id']))
      $getSampleId=$_GET['sample_id'];
    else
      $getSampleId='';
    //When reloading a tab, then we need to move to the next tab
    if (!empty($_GET['load_tab']))
      data_entry_helper::$javascript .= "indiciaData.tabToReloadTo=".$_GET['load_tab'].";";
    else
      data_entry_helper::$javascript .= "indiciaData.tabToReloadTo=0;";
    //Need a jquery selector when referencing the in-progress sample attribute.
    data_entry_helper::$javascript .= "indiciaData.inProgressAttrSelector='#smpAttr\\\\:".$args['in_progress_sample_attr_id']."';";
    
    //Need the number of the occurrences tab, so we can hide the Add Photos button in the species grid.
    data_entry_helper::$javascript .= "
    indiciaData.reloadtabs=[0,4,5,6];
    indiciaData.occTabIdx=6;\n";
    if (!empty($args['gps_sync_warning']))
      data_entry_helper::$javascript .= "indiciaData.gpsSyncWarning=\"".$args['gps_sync_warning']."\";";
    //If option is supplied, warn the user that the GPS device and camera need to be synchronised before they upload GPX file.
    //Also when the screen loads, if there is a "In Progress" attribute (which there should be) and it is not set explicitely as not
    //In Progress, then it must be in progress, so set the attribute to 1.
    data_entry_helper::$javascript.="
    $(window).load(function () {
      if (indiciaData.gpsSyncWarning) {
        $('#file_upload').click(function() {
            var r = confirm(indiciaData.gpsSyncWarning);
            if (r != true) {
              return false;
            }
        });
      }
      if ($(indiciaData.inProgressAttrSelector).length && $(indiciaData.inProgressAttrSelector).val()!=='0') {
        $(indiciaData.inProgressAttrSelector).val(1);
      }
      indiciaData.getSampleId = '$getSampleId';";
      //When the user changes the date, make sure it is still associated with one of the uploaded photos, if not, then warn the user (although they may still continue).
      //All the exif dates are held in a sample attribute, so just check the date appears somewhere in the attribute.
      //Note that a limitation of this is we assume that the Drupal date format is set to is dd/mm/yyyy.
      If (!empty($args['no_photos_with_date_warning'])) {
        data_entry_helper::$javascript.="    
        $('#sample\\\\:date').change(function(evt) {  
          var formattedSampleDate;
          //Date has full year yyyy, so split up, chop the year, and then reconstruct
          var sampleDateArray=$('#sample\\\\:date').val().split('/');  
          //Add some code to be a bit more flexible with shortened user entries (e.g. change 1/1/14 to 01/01/2014 for validation).
          //Can't user built in javascript functions as not good with uk dates
          if (sampleDateArray[0].length===1) {
            sampleDateArray[0]='0'+sampleDateArray[0] 
          }
          if (sampleDateArray[1].length===1) {
            sampleDateArray[1]='0'+sampleDateArray[1]
          }
          if (sampleDateArray[2].length===2) {
            sampleDateArray[2]='20'+sampleDateArray[2]          
          }
          formattedSampleDate=sampleDateArray[0]+'/'+sampleDateArray[1]+'/'+sampleDateArray[2];
          $('#sample\\\\:date').val(formattedSampleDate);
          if (formattedSampleDate && $('#smpAttr\\\\:".$args['exif_date_time_attr_id']."').val()  && 
              $('#smpAttr\\\\:".$args['exif_date_time_attr_id']."').val().indexOf(formattedSampleDate)===-1) {
            alert('".$args['no_photos_with_date_warning']."');
          }
        });";   
      }
        data_entry_helper::$javascript.="    
        });";   
    drupal_add_js(drupal_get_path('module', 'iform') .'/media/js/jquery.form.js', 'module');
    data_entry_helper::add_resource('jquery_form');
    return $r.parent::get_form($args, $node);
  }  
 
  //Override the get_submission from dyamamic_sample_occurrence to stop it running on reloading pages
  //as we have our own ajax_save method for doing this work.
  public static function get_submission($values, $args) {
  }
 
  /*
   * We save using ajax to avoid the need to reload pages all the time when saving (can choose to reload if required using configuration)
   */
  public static function ajax_save($website_id, $password, $node) {
    iform_load_helpers(array('data_entry_helper'));
    //Build submission
    $Model = self::build_three_level_sample_with_occ_submission($_POST,$website_id, $password,$node->params['gpx_data_attr_id'],$node->params['dive_start_time_attr_id'],$node->params['exif_date_time_attr_id']);
    $node = node_load($nid);
    $conn = iform_get_connection_details($node);
    $postargs = "website_id=".$conn['website_id'];
    $response = data_entry_helper::http_post(data_entry_helper::$base_url.'/index.php/services/security/get_nonce', $postargs, false);
    $nonce = $response['output'];
    $writeTokens = array('nonce'=>$nonce, 'auth_token' => sha1($nonce.":".$conn['password']));
    //TODO, when the first page is saved we create a sample but we don't have a spatial reference. An attempt is made to read
    //a position from the first photo exif (elsewhere in code), however if GPS data can't be found on photo, then just fall back on a point on the Isle of Wight (as it is on land it won't get confused with a real position.
    if (empty($Model['fields']['entered_sref']['value'])) {
      drupal_set_message('Unable to find any GPS information, please correct this manually using the GPX upload or map tools');
      $Model['fields']['entered_sref_system']['value']='4277';
      $Model['fields']['entered_sref']['value']='50:41.0994N, 1:17.1864W';
    }  
    //Save submission
    $response = data_entry_helper::forward_post_to('save', $Model, $writeTokens);
    echo json_encode($response);
  }
 
 
  /*
   * Build a submission that can be made up of 3 levels of sample and an occurrence.
   * There can be two types of species grid here, a "normal" species grid which is used 
   * to create extra adhoc species sightings and attach them to the main sample, this can be used if there is no
   * species image, this species grid should be given the id "first-level-smp-occ-grid".
   * Or the main species grid where the images are shown from a second-level or third level sample (depending if last tab has been saved yet), this grid should be given the
   * id "third-level-smp-occ-grid"
   * @param array $values List of the posted values to create the submission from.
   */

  public static function build_three_level_sample_with_occ_submission($values,$website_id, $password,$gpxDataAttrId,$diveStartTimeAttrId,$exifDateTimeAttrId) {
    $standardGridValues=array();
    //Create two different $values arrays.
    //The $standardGridValues array contains all the values you would expect from a normal species grid entry form. This contains the species grid we don't have images for.
    //The $values array contains the values from the form and only includes the other three level sample species grid
    foreach ($values as $key=>$value) {
      if (strpos($key,'sc:') === false || (strpos($key,'sc:') !== false && strpos($key,'first-level-smp-occ-grid') !== false)) {
        $standardGridValues[$key]=$values[$key];
      }
      if (strpos($key,'first-level-smp-occ-grid') !== false) {
        unset($values[$key]);
      }

    }
    //Identify any occurrences which don't have images and will therefore be attached to the top level sample.
    $standardOccurrences = data_entry_helper::wrap_species_checklist($standardGridValues);

    $modelName='sample';
    //Wrap up the main parent sample
    $modelWrapped = data_entry_helper::wrap($values, $modelName, null);
    //2nd level samples are prefixed with the word new_sample_sub_sample:<num>: or existing_sample_sub_sample:<id>: in the name.
    //This function returns an array of possible sub-samples (habitats) which have been extracted from the values on the page by looking
    //for this text on the front of the keys in the values array.
    $possibleSubSampleModels = self::extract_sub_sample_data($values,$website_id, $password,$gpxDataAttrId);
    //Cycle through the sub-samples and then set them up as sub-samples of the main sample
    if (!empty($possibleSubSampleModels)) {
      foreach ($possibleSubSampleModels as $subSample) {
        $modelWrapped['subModels'][] = array(
            'fkId' => 'sample_id',
            'model' => $subSample
        );
      }
    }
    // Build sub-models for the sample media files. Also extract the image exif data
    $media = data_entry_helper::extract_media_data($values, $modelName.'_medium', true, true); 
    if (function_exists('exif_read_data')) {
      $uploadpath = './sites/all/modules/iform/client_helpers/upload/';
      foreach ($media as $idx => $mediaItem) {
        if (file_exists($uploadpath.$mediaItem['path'])) {
          $exif = exif_read_data($uploadpath.$mediaItem['path'], 0, true);
          $media[$idx]['exif'] = json_encode($exif);
          $strToTime=strtotime($exif['EXIF']['DateTimeOriginal']);
          $time=explode(' ',$exif['EXIF']['DateTimeOriginal']);          
          //On the first tab (when we don't have a date field) then collect the date from the exif from the earliest photo
          //and also set a default on the time field in the same way
          //Cycle round the media items and only set the date/time if it is the smallest one so far.
          if ((empty($smallestStrToTime) || $smallestStrToTime > $strToTime)&&
              !empty($exif['EXIF']['DateTimeOriginal'])&&empty($modelWrapped['fields']['sample:date']['value'])) { 
            $smallestStrToTime=$strToTime;
            $gpsFromFirstExif=$exif['GPS'];
            $modelWrapped['fields']['date']['value']=date('d/m/Y',$smallestStrToTime);
            $values['sample:date']=date('d/m/Y',$smallestStrToTime); 
            $modelWrapped['fields']['smpAttr:'.$diveStartTimeAttrId]['value']=$time[1];
            $values['smpAttr:'.$diveStartTimeAttrid]=$time[1];   
          }      
          //Save the dates and times from the photos into an attribute for easy access by javascript, so contruct a string to save first.
          //Note I didn't use json as that dates include colons. So the format is date,time;date,time;date,time;date,time;date,time;
          if (!empty($mediaDates))
            $mediaDates=$mediaDates.';'.date('d/m/Y',$strToTime).','.$time[1];
          else 
            $mediaDates=date('d/m/Y',$strToTime).','.$time[1];
        }
      }
      //When the images are first loaded, we don't have a spatial reference to create the main sample with, so try to read one from the earliest picture exif data
      if (!empty($gpsFromFirstExif['GPSLatitude'])&&!empty($gpsFromFirstExif['GPSLongitude'])&&!empty($gpsFromFirstExif['GPSLatitudeRef'])&&!empty($gpsFromFirstExif['GPSLongitudeRef'])) {
        //Read from exif, concert from degrees, minutes, seconds to decimal degrees
        $gpsLat0=explode('/',$gpsFromFirstExif['GPSLatitude'][0]);
        $gpsLat0=doubleval($gpsLat0[0])/doubleval($gpsLat0[1]);
        $gpsLat1=explode('/',$gpsFromFirstExif['GPSLatitude'][1]);
        $gpsLat1=doubleval($gpsLat1[0])/doubleval($gpsLat1[1])/60;
        $gpsLat2=explode('/',$gpsFromFirstExif['GPSLatitude'][2]);
        $gpsLat2=doubleval($gpsLat2[0])/doubleval($gpsLat2[1])/3600;
        $gpsLon0=explode('/',$gpsFromFirstExif['GPSLongitude'][0]);
        $gpsLon0=doubleval($gpsLon0[0])/doubleval($gpsLon0[1]);
        $gpsLon1=explode('/',$gpsFromFirstExif['GPSLongitude'][1]);
        $gpsLon1=doubleval($gpsLon1[0])/doubleval($gpsLon1[1])/60;
        $gpsLon2=explode('/',$gpsFromFirstExif['GPSLongitude'][2]);
        $gpsLon2=doubleval($gpsLon2[0])/doubleval($gpsLon2[1])/3600;        
        
        $lat=(string)floatval($gpsLat0+$gpsLat1+$gpsLat2);
        $lon=(string)floatval($gpsLon0+$gpsLon1+$gpsLon2);
        
        //Convert back into format that is acceptable to the seasearch on screen spatial reference extension which is again in degrees, minutes
        $latArray=explode('.',$lat);
        $lat=$latArray[0].':'.round(floatval('0.'.$latArray[1])*60,4);
        $lonArray=explode('.',$lon);
        $lon=$lonArray[0].':'.round(floatval('0.'.$lonArray[1])*60,4);
        $gpsFromFirstExif=$lat.$gpsFromFirstExif['GPSLatitudeRef'].' '.$lon.$gpsFromFirstExif['GPSLongitudeRef'];
        
        $modelWrapped['fields']['entered_sref']['value']=$gpsFromFirstExif;
        $values['sample:entered_sref']=$gpsFromFirstExif;
        $modelWrapped['fields']['entered_sref_system']['value']='4277';
        $values['sample:entered_sref_system']='4277';
      }
    }
      if (!empty($mediaDates)) {
        //Need to find the attribute that starts with smpAttr:<exifDateTimeAttrId> as in edit mode it will also have the sample_attribute_value on the end so in that
        //case we need to overwrite existing value instead of creating new one.
     foreach ($values as $theKey=>$theValue) {
        if (substr($theKey, 0, strlen('smpAttr:'.$exifDateTimeAttrId)) === 'smpAttr:'.$exifDateTimeAttrId) {
          $modelWrapped['fields'][$theKey]['value']=$mediaDates;
          $values[$theKey]=$mediaDates;   
        }
      }
    }  
    foreach ($media as $item) {
      //Only add media to the main sample if it isn't already contained in any sub-sample
      if (empty($values['sample_medium:'.$item['id'].':sample_id'])||
          $values['sample_medium:'.$item['id'].':sample_id']==$modelWrapped['fields']['id']['value']) {
        $wrapped = data_entry_helper::wrap($item, $modelName.'_medium');  
        $modelWrapped['subModels'][] = array(
          'fkId' => $modelName.'_id',
          'model' => $wrapped
        );
      }
    }
   
    // Put any extra occurrences (without images)
    // the user has identified onto the end of the main sample model.
    if (array_key_exists('subModels', $modelWrapped)) {
      $modelWrapped['subModels'] = array_merge($modelWrapped['subModels'], $standardOccurrences);
    } else {
      $modelWrapped['subModels'] = $standardOccurrences;
    }
    
    //Create the third level samples with occurrences
    $modelWrapped=self::create_third_level_sample_model($modelWrapped,$values,$website_id, $password,$gpxDataAttrId);
    //TODO Needs further testing
    //The user is can rearrange which third level sample points to which second level sample. When the user does this we just need to attach the
    //change to the parent_id to the end of the submission model
    $thirdLevelSampleShiftModel=array();
    foreach ($values as $key=>$value) {
      //TODO I think this name is misleading, as it isn't the sample_id for the occurrence, it is the sample_id for the occurrence sample's parent sample.
      //Maybe change name at some point.
      if (strpos($key,'occurrence_medium') !== false&&strpos($key,':sample_id') !== false) {
        $splitKey=explode(':',$key);
        $thirdLevelSampleShiftModel['id']='sample';
        $thirdLevelSampleShiftModel['fields']['id']['value']=$splitKey[3];
        $thirdLevelSampleShiftModel['fields']['parent_id']['value']=$value;
        if (!empty($modelWrapped['submission_list']['entries'])) {
          $modelWrapped['submission_list']['entries'][]=$thirdLevelSampleShiftModel;
        } else {
          $multiSubmission['id']='sample';
          $multiSubmission['submission_list']['entries'][0]=$modelWrapped;
          $multiSubmission['submission_list']['entries'][1]=$thirdLevelSampleShiftModel;
          $modelWrapped=$multiSubmission;
        }
      }
    }
    return $modelWrapped;
  }

  //Return an array of possible sub-samples which have been extracted from the values on the page.
  private static function extract_sub_sample_data($values,$website_id, $password,$gpxDataAttrId) {
    //Remove the existing ID of the main sample, otherwise it gets copied to sub-samples
    unset($values['sample:id']);
    $r = array();
    $valuesCollection=array();
    $cleanValues=array();
    $existingValuesCollections=array();
    //Cycle through all the values on the page
    //Extract the attributes related to habitats (sub-samples) into arrays
    //So, for instance, if there happen to be 4 existing habitats, then there will be 4 items in the $existingValuesCollections and each
    //of these items will be an array of attributes.
    foreach ($values as $key => $value) {
      $keyBreakdown=explode(':',$key);
      $newSubSamplePrefix = 'new_sample_sub_sample:';
      $existingSubSamplePrefix = 'existing_sample_sub_sample:';
      //Get the length of the prefix (new_sample_sub_sample or existing_sample_sub_sample) when the sub sample index number is included
      if (!empty($keyBreakdown[0]) && !empty($keyBreakdown[1]))
        $fullSubSamplePrefixLength=strlen($keyBreakdown[0].$keyBreakdown[1])+2;//Need to add 2 here so that we include the colons.
      else
        //If this is hit, then we aren't dealing with a habitat field and the if staement below with revert to the last else option
        $fullSubSamplePrefixLength=0;
      $sampleAttrPrefix='smpAttr:';
      //If the field relates to a new sub-sample
      if ($newSubSamplePrefix == substr($key, 0, strlen($newSubSamplePrefix))) {
        //Get the index number of the sub-sample (which starts from 1), TODO this should of probably ideally started from 0. Maybe rework another time.
        $collectionNum=$keyBreakdown[1];
        //Build up an array where each main key is the number of the sub-sample and each sub-key
        //are the keys with the prefix part removed (as we don't need it anymore now we
        //have placed the value in an array).
        //In effect with have an array containing all the sub-samples
        $valuesCollection[$collectionNum][substr($key, $fullSubSamplePrefixLength)]=$value;   
      } elseif ($existingSubSamplePrefix == substr($key, 0, strlen($existingSubSamplePrefix))) {
        //Get any existing sub-samples (habitats)
        $keyBreakdown=explode(':',$key);
        $collectionId=$keyBreakdown[1];
        $existingValuesCollections[$collectionId][substr($key, $fullSubSamplePrefixLength)]=$value;
      } else {
        //The sub-samples arrays are missing some basic elements required for a sample to work
        //such as the spatial reference. Make an array containing these fundamental sample elements from the main sample, so that we can merge them
        //in with the sub-sample values. These basic elements are anything that aren't sample_attributes.
        if (!($sampleAttrPrefix == substr($key, 0, strlen($sampleAttrPrefix)))) {
          $cleanValues[$key]=$value;
        }
      }
    }
    //Make one array contain both new and existing sub-samples
    //Cycle through the existing sub-sample value collection applying them to the main value collection array.
    //Set the key of the array to the length of the array holding the new sub-samples, but then add a number which relates to the
    //number of times we have cycled around the $existingValuesCollection
    $existingValuesCollectionCounter=1;
    foreach ($existingValuesCollections as $existingValuesCollection) {
      $valuesCollection[count($valuesCollection)+$existingValuesCollectionCounter] = $existingValuesCollection;
      $existingValuesCollectionCounter++;
    }
    $completeValuesCollection=array();
    //Merge each set of sub-sample values with elements required to make a basic sample (such as entered_sref)
    foreach ($valuesCollection as $idx=>$incompleteSubSampleValueSet) {
      $completeValuesCollection[$idx]=array_merge($incompleteSubSampleValueSet,$cleanValues);
      $wrappedCollection[$idx] = data_entry_helper::wrap($completeValuesCollection[$idx], 'sample');
      //Make an array of media which is assigned to the sub-sample (photos attached to habitst)
      foreach($values as $key => $value) {
        $keyBreakdown=explode(':',$key);
        if ($keyBreakdown[0] == 'sample_medium' && $keyBreakdown[2] == 'sample_id') {
          if (!in_array($keyBreakdown[1],$mediaIds))
            $media[]=array('id'=>$keyBreakdown[1],'sample_id'=>$value, 'path'=>$values['sample_medium:'.$keyBreakdown[1].':image_path']);
        }
      }
      $mediaIds=array();
      $mediaIdsSet='';
      //Create a set (string) of media ids ready for use in sql
      foreach ($media as $mediaItem) {
        if ($mediaItem['sample_id']==$completeValuesCollection[$idx]['sample:id']) {
          if ($mediaIdsSet==='') {
            $mediaIdsSet=$mediaItem['id'];
          } else {
            $mediaIdsSet=$mediaIdsSet.','.$mediaItem['id'];
          }
        }
      }
      //Need to get exifs for media items
      $readAuth = data_entry_helper::get_read_auth($website_id, $password);
      //Use this report to return the photos
      $reportName = 'reports_for_prebuilt_forms/seasearch/get_media_for_media_id';
      $reportOptions=array(
        'readAuth' => $readAuth,
        'dataSource'=> $reportName,
        'extraParams'=>array(
          'media_ids'=>$mediaIdsSet,
        )
      );

      $photoResults = data_entry_helper::get_report_data($reportOptions);
      //Get an average of all the spatial references associated with the current sub-sample
      //Do this by finding the spatial references from the GPX file which are closest in time to the times in the photo exifs (can't rely on GPS on photo, as taken underwater)
      //We then average them all to make the sub-sample spatial reference.
      foreach ($values as $key =>$value) {
        $explodedKey=explode(':',$key);
        //Get gps data which is already saved in an attribute
        if (!empty($explodedKey[0]) && !empty($explodedKey[1]) && ($explodedKey[0].':'.$explodedKey[1]=='smpAttr:'.$gpxDataAttrId)&&!empty($value)) {
          $gpsArray=explode(';',$value);
        }
      }
      $mediaSpatialRefs=array();
      $smallestTimeDistance=null;
      //For each photo, we need to cycle through all the gpx file trackpoints. We then use the trackpoint which is closest in time to the time on the photo exif
      //Save an array containing all these photos and associated spatial references.
      foreach ($photoResults as $mediaItem) {
        $photoResultExifDecoded = json_decode($mediaItem['exif'],true);
        $photoResultExifFormatted = strtotime($photoResultExifDecoded['EXIF']['DateTimeOriginal']);
        foreach ($gpsArray as $gpsArrayPosTimeString) {
          $gpsArrayPosTimeArray=explode(',',$gpsArrayPosTimeString);
          $gpsArrayPosTimeArray[2]=self::convertGPXDateToStrToTimeCompatibleFormat($gpsArrayPosTimeArray[2]);
          $timeDistance = strtotime($gpsArrayPosTimeArray[2]) - $photoResultExifFormatted;
          if ($timeDistance < 0)
            $timeDistance = $timeDistance * -1;
          if ($smallestTimeDistance===null || ($timeDistance<$smallestTimeDistance)) {
            $smallestTimeDistance=$timeDistance;
            $mediaSpatialRefs[$mediaItem['id']]=$gpsArrayPosTimeArray[0].','.$gpsArrayPosTimeArray[1];
          }
        }
        $smallestTimeDistance=null;
      }
      //Now create an average of all the spatial references associated with the sub-sample's photos.
      //This is then used as the sub-sample's spatial reference.
      $latAcc=0;
      $lonAcc=0;
      if (!empty($mediaSpatialRefs)) {
        foreach ($mediaSpatialRefs as $mediaSpatialRef) {
          $gpsDataPair = explode(',', $mediaSpatialRef);
          $latAcc=$latAcc+floatval($gpsDataPair[0]);
          $lonAcc=$lonAcc+floatval($gpsDataPair[1]);
        }
        $latAcc=round($latAcc/count($mediaSpatialRefs),10);
        $lonAcc=round($lonAcc/count($mediaSpatialRefs),10);
        //Convert spatial reference from 50,-1 format to 50N 1W format
        $northSouthPos=self::convert_to_north_south_lat_lon($latAcc.','.$lonAcc);
      }
      foreach ($media as $item) {
        $mediaIds[]=$item['id'];
        //Only add the media item to the sub-sample, if the item has been assigned to the sub-sample by the user.
        if (!empty($values['sample_medium:'.$item['id'].':sample_id']) &&
            ($values['sample_medium:'.$item['id'].':sample_id']==$completeValuesCollection[$idx]['sample:id'])) {
          $wrapped = data_entry_helper::wrap($item, 'sample_medium');
          $wrappedCollection[$idx]['subModels'][] = array(
            'fkId' => 'sample_id',
            'model' => $wrapped
          ); 
        }
      }
      //Need to check that $mediaSpatialRefs is empty as we don't want to make the assignment when 
      //$northSouthPos is "0N 0E" which is what it is if the habitats are created but the photos are not assigned yet.
      if (!empty($northSouthPos) && !empty($mediaSpatialRefs)) {
        $wrappedCollection[$idx]['fields']['entered_sref']['value']=$northSouthPos;
      } else {
        //If the user has used a single spatial reference instead of a GPX file
        $wrappedCollection[$idx]['fields']['entered_sref']['value']=$values['sample:entered_sref'];
      }
    }
    if (!empty($wrappedCollection)) {     
      return $wrappedCollection;     
    } else
      return null;
  }
 
  /*
   * When the final tab of the form is saved, then the occurrences will have had their taxa identified. At this time we need to create a full model
   * with a main sample, some sub-samples, and then some third level samples with the occurrences attached.
   */
  private static function create_third_level_sample_model($modelWrapped,$values,$website_id, $password,$gpxDataAttrId) {  
    //Initially when the occurrences grid is loaded, it is loaded with sample images held on the 2nd level sample.
    //Convert these into occurrence images.
    //Also collect a list of present taxa on the grid.
    $presentSpeciesListSubSampleIds=self::convert_grid_sample_media_to_occurrence_media_and_return_present_items($values);
    //Use the existing code to create sub-samples for the occurrences grid.
    //This is based on wrap_species_checklist_with_subsamples in data_entry_helper.
    //Altered for Seasearch to create third-level samples for occurrences grid.
    $thirdLevelSamples = self::wrap_species_checklist_with_third_level_samples($values);
    foreach ($thirdLevelSamples  as &$thirdLevelSample) {
      if (empty($thirdLevelSample['model']['subModels'])) {
        //If the third level sample doesn't include images then delete it.
        if (!empty($thirdLevelSample['model']['fields']['id']['value'])) {
          $id=$thirdLevelSample['model']['fields']['id']['value'];
          $thirdLevelSample['model']['fields']=array();
          $thirdLevelSample['model']['fields']['id']['value']=$id;
          $thirdLevelSample['model']['fields']['deleted']['value']='t';
          unset($thirdLevelSample['model']['subModels']);
        }
      }
    }
    foreach ($values as $key =>$value) {
      $explodedKey=explode(':',$key);
      if (!empty($explodedKey[0]) && !empty($explodedKey[1]) && ($explodedKey[0].':'.$explodedKey[1]=='smpAttr:'.$gpxDataAttrId)&&!empty($value)) {
        $gpsArray=explode(';',$value);
      }
    }
    //As we have used existing code to create 2nd level samples to hold the occurrences, we need to transfer these to the third level samples
    $modelWrapped=self::transfer_occurrences_to_third_level_samples($modelWrapped,$thirdLevelSamples,$presentSpeciesListSubSampleIds,$gpsArray,$website_id, $password,$gpxDataAttrId);
    $multiSubmission['submission_list']['entries']=array();
    //Any third level samples that have been deleted need adding to the model at the top, this because we don't know the parent_id
    //Note, there is a bug in PHP foreach which was causing the foreach to cycle over the first element here twice. From the php docs
    //I think this is caused by previously using foreach ($thirdLevelSamples  as &$thirdLevelSample) {, so using normal "for loop"
    for ($i=0;$i<count($thirdLevelSamples);$i++) {
      if ($thirdLevelSamples[$i]['model']['fields']['deleted']['value']==='t') {
        $multiSubmission['submission_list']['entries'][]=$thirdLevelSamples[$i]['model'];
      }
    }
    if (!empty($multiSubmission['submission_list']['entries'])) {
      $multiSubmission['submission_list']['entries'][]=$modelWrapped;
      $multiSubmission['id']='sample';
      return $multiSubmission;
    }
    return $modelWrapped;
  }
 
 
  /* Convert a lat/lon from this format 50.7794054483 -2.0110088917 to this 50.7794054483N 2.0110088917W*/
  private static function convert_to_north_south_lat_lon($position) {
    $positionSplit=explode(',',$position);
    if ($positionSplit[0]>=0) {
      $positionSplit[0]=$positionSplit[0].'N';
    } else {
      $positionSplit[0]=($positionSplit[0]*-1).'S';
    }
    if ($positionSplit[1]>=0) {
      $positionSplit[1]=$positionSplit[1].'E';
    } else {
      $positionSplit[1]=($positionSplit[1]*-1).'W';
    }
    return $positionSplit[0].' '.$positionSplit[1];
  }
 
  //Existing sub-sample creation code is used to create sub-samples on the grid with occurrences attached.
  //So we need to move these occurrences onto thid level samples.
  private static function transfer_occurrences_to_third_level_samples($modelWrapped,$thirdLevelSamples,$presentSpeciesListSubSampleIds,$gpsArray,$website_id,$password,$gpxDataAttrId) {
    //Loop through each 2nd level sample.
    foreach ($modelWrapped['subModels'] as $secondLevelSampleIdx=> &$secondLevelSample) {
      //Only work on the second level sample in the situation where there are some third level samples to create for it ready to put an occurrence onto.
      if (!empty($secondLevelSample['model']['fields']['id']['value']) && !empty($presentSpeciesListSubSampleIds)) {
        if (in_array($secondLevelSample['model']['fields']['id']['value'],$presentSpeciesListSubSampleIds)) { 
          foreach ($thirdLevelSamples as $thirdLevelSample) {
            //Add the media currently attached to the second-level sample  to the empty third level sample
            foreach ($secondLevelSample['model']['subModels'] as $subSampleMedium) {
              //Add the third level sample to the second level sample, but only if the image added to the third level sample's occurrence matches one on the second level sample (the second level
              //sample image is going to be deleted in a minute)
              if ($subSampleMedium['model']['fields']['path']['value']==$thirdLevelSample['model']['subModels'][0]['model']['subModels'][0]['model']['fields']['path']['value']) {
                //Need to get exifs for media items
                $readAuth = data_entry_helper::get_read_auth($website_id, $password);
                //Use this report to return the photos
                $reportName = 'reports_for_prebuilt_forms/seasearch/get_media_for_media_id';
                $reportOptions=array(
                  'readAuth' => $readAuth,
                  'dataSource'=> $reportName,
                  'extraParams'=>array(
                    'media_ids'=>$subSampleMedium['model']['fields']['id']['value'],
                  )
                );

                $photoResults = data_entry_helper::get_report_data($reportOptions);
                //TODO this code is very similar to code used earlier, perhaps put in separate method when get chance.
                $mediaSpatialRefs=array();
                $smallestTimeDistance=null;
                $photoResultExifDecoded = json_decode($photoResults[0]['exif'],true);
                $photoResultExifFormatted = strtotime($photoResultExifDecoded['EXIF']['DateTimeOriginal']);
                foreach ($gpsArray as $gpsArrayPosTimeString) {
                  $gpsArrayPosTimeArray=explode(',',$gpsArrayPosTimeString);
                  $gpsArrayPosTimeArray[2]=self::convertGPXDateToStrToTimeCompatibleFormat($gpsArrayPosTimeArray[2]);
                  $timeDistance = strtotime($gpsArrayPosTimeArray[2]) - $photoResultExifFormatted;
                  //We are only interested in finding the closet GPX time to the exif one, as exif times can be earlier or later than the GPX one, we need 
                  //to ignore whether it is earlier or later and just find cloest match, so if the number is negative then make it positive.
                  if ($timeDistance < 0)
                    $timeDistance = $timeDistance * -1;
                  if ($smallestTimeDistance===null || ($timeDistance<$smallestTimeDistance)) {
                    $smallestTimeDistance=$timeDistance;
                    $mediaSpatialRef=$gpsArrayPosTimeArray[0].','.$gpsArrayPosTimeArray[1];
                  }
                }
                if (!empty($mediaSpatialRef)) {   
                  $northSouthPos=self::convert_to_north_south_lat_lon($mediaSpatialRef);
                  //Automatically select the correct lat,lon position from the GPS file.
                  $thirdLevelSample['model']['fields']['entered_sref']['value']=$northSouthPos;
                } else {
                  //If there is no GPS match then fall back on the spatial reference for the main sample (e.g. we didn't upload a GPX file)
                  $thirdLevelSample['model']['fields']['entered_sref']['value']=$modelWrapped['fields']['entered_sref']['value'];
                }  
                //Only add third level samples which have had their occurrence setup, else the third level sample isn't intended for this habitat (second level sample)
                if (!empty($thirdLevelSample['model']['subModels']))
                  $secondLevelSample['model']['subModels'][]=$thirdLevelSample;
              }
            }
          }
          //Clear any media already associated with the second level sample, as this media will now be held at the 3rd level.
          self::set_sub_sample_media_items_to_deleted($secondLevelSample);
        }
      }
    }
    return $modelWrapped;
  }
 
  /*
   * When we create the occurrences, we create a third level sample to attach it to. We then move the 2nd level sample images to be
   * occurrence images. This means we need to delete the sample images after they have been moved to be occurrence images.
   */
  private static function set_sub_sample_media_items_to_deleted(&$secondLevelSample) {
    if (!empty($secondLevelSample['model']['subModels'])) {
      foreach ($secondLevelSample['model']['subModels'] as &$secondLevelSampleSubModel) {
        if (!empty($secondLevelSampleSubModel['model']['fields']['path']['value'])) {
          foreach ($secondLevelSampleSubModel['model']['fields'] as $field=>$fieldData) {
            //For deleting models, then only field we want is the id and the deleted field='t'
            if ($field!=='id') {
              unset($secondLevelSampleSubModel['model']['fields'][$field]);
            }
          }
          $secondLevelSampleSubModel['model']['fields']['deleted']['value']='t';
        }
      }
    }
  }
 
  //Does two things, one is return an array of present occurrences that have actually been filled in.
  //The other is to convert the sample_media intially present on the grid into occurrence media.
  private static function convert_grid_sample_media_to_occurrence_media_and_return_present_items(&$values) {
    $presentSpeciesListSubSampleIds=array();
    foreach ($values as $key=>&$value) {  
      $splitKey=explode(':',$key);
      //If we find a field which is a sample_medium on the occurrences grid then save its current second level sample id for use later
      if (strpos($key,'third-level-smp-occ-grid')!==false) {
        if (array_key_exists('sample_medium:'.$value.':sample_id',$values)&&
            $values[$splitKey[0].':'.$splitKey[1].'::present']>0) {
          $presentSpeciesListSubSampleIds[$value]=$values['sample_medium:'.$value.':sample_id'];
        }
        //Need occurrence version of the existing sample media that has been initially placed on the occurrence grid.
        //Remove the original sample media from the grid once once occurrence media created.
        //Need to make sure we don't copy the id field accross from the sample medium to the occurrence_medium as it will be wrong.
        if (strpos($key,':sample_medium')!==false&&$splitKey[4]!='id') {
         $values[$splitKey[0].':'.$splitKey[1].'::occurrence_medium:'.$splitKey[4].':'.$splitKey[5]]=$value;
         unset($key);
       }
      }
    }
    return $presentSpeciesListSubSampleIds;
  }
      
  /**
   * Get the control for species input. An altered copy of the one found in dynamic_sample_occurrence with support for preloading a species grid with sample images from second level samples
   * and then loading it with occurrence images attached to occurrences which are attached to third level samples.
   * May contain coded that is not needed and can be removed if possible
   */
  protected static function get_control_species($auth, $args, $tabAlias, $options) {
    $gridmode = call_user_func(array(self::$called_class, 'getGridMode'), $args);
    //The filter can be a URL or on the edit tab, so do the processing to work out the filter to use
    $filterLines = self::get_species_filter($args);
    // store in the argument so that it can be used elsewhere
    $args['taxon_filter'] = implode("\n", $filterLines);
    //Single species mode only ever applies if we have supplied only one filter species and we aren't in taxon group mode
    if ($args['taxon_filter_field']!=='taxon_group' && count($filterLines)===1 && ($args['multiple_occurrence_mode']!=='multi')) {
      $response = self::get_single_species_data($auth, $args, $filterLines);
      //Optional message to display the single species on the page
      if ($args['single_species_message'])
        self::$singleSpeciesName=$response[0]['taxon'];
      if (count($response)==0)
        //if the response is empty there is no matching taxon, so clear the filter as we can try and display the checklist with all data
        $args['taxon_filter']='';
      elseif (count($response)==1)
        //Keep the id of the single species in a hidden field for processing if in single species mode
        return '<input type="hidden" name="occurrence:taxa_taxon_list_id" value="'.$response[0]['id']."\"/>\n";
    }
    $extraParams = $auth['read'];
    if ($gridmode)
      return self::get_control_species_checklist($auth, $args, $extraParams, $options);
    else
      return self::get_control_species_single($auth, $args, $extraParams, $options);
  }

 
  /**
   * Returns the species checklist input control.
   * @param array $auth Read authorisation tokens
   * @param array $args Form configuration
   * @param array $extraParams Extra parameters array, pre-configured with filters for taxa and name types.
   * @param array $options additional options for the control, e.g. those configured in the form structure.
   * @return HTML for the species_checklist control.
   * Again, this is an altered copy of the one found in dynamic_sample_occurrence with support for preloading a species grid with sample images from second level samples
   * and then loading it with occurrence images attached to occurrences which are attached to third level samples.
   * May contain coded that is not needed and can be removed if possible
   */
  protected static function get_control_species_checklist($auth, $args, $extraParams, $options) {
    global $user;
    
    // Build the configuration options
    if (isset($options['view']))
      $extraParams['view'] = $options['view'];    
    // There may be options in the form occAttr:n|param => value targetted at specific attributes
    $occAttrOptions = array();
    $optionToUnset = array();
    foreach ($options as $option => $value) {
      // split the id of the option into the attribute name and option name.
      $optionParts = explode('|', $option);
      if ($optionParts[0] != $option) {
        // an occurrence attribute option was found
        $attrName = $optionParts[0];
        $optName = $optionParts[1];
        // split the attribute name into the type and id (type will always be occAttr)
        $attrParts = explode(':', $attrName);
        $attrId = $attrParts[1];      
        if (!isset($occAttrOptions[$attrId])) $occAttrOptions[$attrId]=array();
        $occAttrOptions[$attrId][$optName] = apply_user_replacements($value);
        $optionToUnset[] = $option;
      }
    }
    // tidy up options array
    foreach ($optionToUnset as $value) {
      unset($options[$value]);
    }
    // make sure that if extraParams is specified as a config option, it does not replace the essential stuff
    if (isset($options['extraParams']))
      $options['extraParams'] = array_merge($extraParams, $options['extraParams']);
    $species_ctrl_opts=array_merge(array(
        'occAttrOptions' => $occAttrOptions,
        'listId' => $args['list_id'],
        'label' => lang::get('occurrence:taxa_taxon_list_id'),
        'columns' => 1,
        'extraParams' => $extraParams,
        'survey_id' => $args['survey_id'],
        'occurrenceComment' => $args['occurrence_comment'],
        'occurrenceSensitivity' => (isset($args['occurrence_sensitivity']) ? $args['occurrence_sensitivity'] : false),
        'occurrenceImages' => $args['occurrence_images'],
        'PHPtaxonLabel' => true,
        'language' => iform_lang_iso_639_2(hostsite_get_user_field('language')), // used for termlists in attributes
        'cacheLookup' => $args['cache_lookup'],
        'speciesNameFilterMode' => self::getSpeciesNameFilterMode($args),
        'userControlsTaxonFilter' => isset($args['user_controls_taxon_filter']) ? $args['user_controls_taxon_filter'] : false,
        'subSpeciesColumn' => $args['sub_species_column'],
        'copyDataFromPreviousRow' => !empty($args['copy_species_row_data_to_new_rows']) && $args['copy_species_row_data_to_new_rows'],
        'previousRowColumnsToInclude' => empty($args['previous_row_columns_to_include']) ? '' : $args['previous_row_columns_to_include'],
        'editTaxaNames' => !empty($args['edit_taxa_names']) && $args['edit_taxa_names'],
        'includeSpeciesGridLinkPage' => !empty($args['include_species_grid_link_page']) && $args['include_species_grid_link_page'],
        'speciesGridPageLinkUrl' => $args['species_grid_page_link_url'],
        'speciesGridPageLinkParameter' => $args['species_grid_page_link_parameter'],
        'speciesGridPageLinkTooltip' => $args['species_grid_page_link_tooltip'],
    ), $options);
    if ($groups=hostsite_get_user_field('taxon_groups')) {
      $species_ctrl_opts['usersPreferredGroups'] = unserialize($groups);
    }
    if ($args['extra_list_id']) $species_ctrl_opts['lookupListId']=$args['extra_list_id'];
    //We only do the work to setup the filter if the user has specified a filter in the box
    if (!empty($args['taxon_filter_field']) && (!empty($args['taxon_filter']))) {
      $species_ctrl_opts['taxonFilterField']=$args['taxon_filter_field'];
      $filterLines = helper_base::explode_lines($args['taxon_filter']);
      $species_ctrl_opts['taxonFilter']=$filterLines;
    }
    if (isset($args['col_widths']) && $args['col_widths']) $species_ctrl_opts['colWidths']=explode(',', $args['col_widths']);
    call_user_func(array(self::$called_class, 'build_grid_taxon_label_function'), $args, $options);
    if (self::$mode == self::MODE_CLONE)
      $species_ctrl_opts['useLoadedExistingRecords'] = true;   
    return self::species_checklist($species_ctrl_opts);
  }
 
 
  /**
  * Helper function to generate a species checklist from a given taxon list.
  *
  * Please not that although this is based on the data_entry_helper function, it has only been tested with the following
  * options for seasearch - @id,@useThirdLevelSamples,@lookupListId,@gridIdAttributeId,@speciesControlToUseSubSamples,@subSamplePerRow,@resizeWidth,@resizeHeight
  * If you intend to use any other options, they will require further testing or development.
  *
  */
  private static function species_checklist($options)
  {
    global $indicia_templates;
    data_entry_helper::add_resource('addrowtogrid');
    $options = data_entry_helper::get_species_checklist_options($options);
    $classlist = array('ui-widget', 'ui-widget-content', 'species-grid');
    if (!empty($options['class']))
      $classlist[] = $options['class'];
    if ($options['subSamplePerRow'])
      // we'll track 1 sample per grid row.
      $smpIdx=0;
    if ($options['columns'] > 1 && count($options['mediaTypes'])>1)
      throw new Exception('The species_checklist control does not support having more than one occurrence per row (columns option > 0) '.
          'at the same time has having the mediaTypes option in use.');
    data_entry_helper::add_resource('json');
    data_entry_helper::add_resource('autocomplete');
    $filterArray = data_entry_helper::get_species_names_filter($options);
    $filterNameTypes = array('all','currentLanguage', 'preferred', 'excludeSynonyms');
    //make a copy of the options so that we can maipulate it
    $overrideOptions = $options;
    //We are going to cycle through each of the name filter types
    //and save the parameters required for each type in an array so
    //that the Javascript can quickly access the required parameters
    foreach ($filterNameTypes as $filterType) {
      $overrideOptions['speciesNameFilterMode'] = $filterType;
      $nameFilter[$filterType] = data_entry_helper::get_species_names_filter($overrideOptions);
      $nameFilter[$filterType] = json_encode($nameFilter[$filterType]);
    }
    if (count($filterArray)) {
      $filterParam = json_encode($filterArray);
      self::$javascript .= "indiciaData['taxonExtraParams-".$options['id']."'] = $filterParam;\n";
      // Apply a filter to extraParams that can be used when loading the initial species list, to get just the correct names.
      if (isset($options['speciesNameFilterMode']) && !empty($options['listId'])) {
        $filterFields = array();
        $filterWheres = array();
        self::parse_species_name_filter_mode($options, $filterFields, $filterWheres);
        if (count($filterWheres))
          $options['extraParams'] += array('query' => json_encode(array('where' => $filterWheres)));
        $options['extraParams'] += $filterFields;
      }
    }
    data_entry_helper::$js_read_tokens = $options['readAuth'];
    data_entry_helper::$javascript .= "indiciaData['rowInclusionCheck-".$options['id']."'] = '".$options['rowInclusionCheck']."';\n";
    data_entry_helper::$javascript .= "indiciaData['copyDataFromPreviousRow-".$options['id']."'] = '".$options['copyDataFromPreviousRow']."';\n";
    data_entry_helper::$javascript .= "indiciaData['includeSpeciesGridLinkPage-".$options['id']."'] = '".$options['includeSpeciesGridLinkPage']."';\n";
    data_entry_helper::$javascript .= "indiciaData.speciesGridPageLinkUrl = '".$options['speciesGridPageLinkUrl']."';\n";
    data_entry_helper::$javascript .= "indiciaData.speciesGridPageLinkParameter = '".$options['speciesGridPageLinkParameter']."';\n";
    data_entry_helper::$javascript .= "indiciaData.speciesGridPageLinkTooltip = '".$options['speciesGridPageLinkTooltip']."';\n";
    data_entry_helper::$javascript .= "indiciaData['editTaxaNames-".$options['id']."'] = '".$options['editTaxaNames']."';\n";
    data_entry_helper::$javascript .= "indiciaData['subSpeciesColumn-".$options['id']."'] = '".$options['subSpeciesColumn']."';\n";
    data_entry_helper::$javascript .= "indiciaData['subSamplePerRow-".$options['id']."'] = ".($options['subSamplePerRow'] ? 'true' : 'false').";\n";
    if ($options['copyDataFromPreviousRow']) {
      data_entry_helper::$javascript .= "indiciaData['previousRowColumnsToInclude-".$options['id']."'] = '".$options['previousRowColumnsToInclude']."';\n";
      data_entry_helper::$javascript .= "indiciaData.langAddAnother='" . lang::get('Add another') . "';\n";
    }
    if (count($options['mediaTypes'])) {
      data_entry_helper::add_resource('plupload');
      // store some globals that we need later when creating uploaders
      $relpath = data_entry_helper::getRootFolder() . data_entry_helper::client_helper_path();
      $interim_image_folder = isset(parent::$interim_image_folder) ? parent::$interim_image_folder : 'upload/';
      data_entry_helper::$javascript .= "indiciaData.uploadSettings = {\n";
      data_entry_helper::$javascript .= "  uploadScript: '" . $relpath . "upload.php',\n";
      data_entry_helper::$javascript .= "  destinationFolder: '" . $relpath . $interim_image_folder."',\n";
      data_entry_helper::$javascript .= "  jsPath: '".data_entry_helper::$js_path."'";
      if (isset($options['resizeWidth'])) {
        data_entry_helper::$javascript .= ",\n  resizeWidth: ".$options['resizeWidth'];
      }
      if (isset($options['resizeHeight'])) {
        data_entry_helper::$javascript .= ",\n  resizeHeight: ".$options['resizeHeight'];
      }
      if (isset($options['resizeQuality'])) {
        data_entry_helper::$javascript .= ",\n  resizeQuality: ".$options['resizeQuality'];
      }
      data_entry_helper::$javascript .= "\n}\n";
      if ($indicia_templates['file_box']!='')
        data_entry_helper::$javascript .= "file_boxTemplate = '".str_replace('"','\"', $indicia_templates['file_box'])."';\n";
      if ($indicia_templates['file_box_initial_file_info']!='')
        data_entry_helper::$javascript .= "file_box_initial_file_infoTemplate = '".str_replace('"','\"', $indicia_templates['file_box_initial_file_info'])."';\n";
      if ($indicia_templates['file_box_uploaded_image']!='')
        data_entry_helper::$javascript .= "file_box_uploaded_imageTemplate = '".str_replace('"','\"', $indicia_templates['file_box_uploaded_image'])."';\n";
    }
    $occAttrControls = array();
    $occAttrs = array();
    $occAttrControlsExisting = array();
    $taxonRows = array();
    $subSampleRows = array();
    if (!empty($options['useThirdLevelSamples']) && $options['useThirdLevelSamples']==true)
      $useThirdLevelSamples=true;
    else
      $useThirdLevelSamples=false;
    // Load any existing sample's occurrence data into $entity_to_load
    if (isset(data_entry_helper::$entity_to_load['sample:id']) && $options['useLoadedExistingRecords']===false)
      self::preload_species_checklist_occurrences(data_entry_helper::$entity_to_load['sample:id'], $options['readAuth'],
          $options['mediaTypes'], $options['reloadExtraParams'], $subSampleRows, $options['speciesControlToUseSubSamples'],
          (isset($options['subSampleSampleMethodID']) ? $options['subSampleSampleMethodID'] : ''),$options['id'],$useThirdLevelSamples);
    // load the full list of species for the grid, including the main checklist plus any additional species in the reloaded occurrences.  
    $taxalist = data_entry_helper::get_species_checklist_taxa_list($options, $taxonRows);
    // If we managed to read the species list data we can proceed
    if (! array_key_exists('error', $taxalist)) {
      $attrOptions = array(
          'id' => null
           ,'valuetable'=>'occurrence_attribute_value'
           ,'attrtable'=>'occurrence_attribute'
           ,'key'=>'occurrence_id'
           ,'fieldprefix'=>"sc:-idx-::occAttr"
           ,'extraParams'=>$options['readAuth']
           ,'survey_id'=>array_key_exists('survey_id', $options) ? $options['survey_id'] : null
      );
      if (isset($options['attributeIds'])) {
        // make sure we load the grid ID attribute
        if (!empty($options['gridIdAttributeId']) && !in_array($options['gridIdAttributeId'], $options['attributeIds']))
          $options['attributeIds'][] = $options['gridIdAttributeId'];
        $attrOptions['extraParams'] += array('query'=>json_encode(array('in'=>array('id'=>$options['attributeIds']))));
      }
      $attributes = data_entry_helper::getAttributes($attrOptions);
      // Merge in the attribute options passed into the control which can override the warehouse config
      if (isset($options['occAttrOptions'])) {
        foreach ($options['occAttrOptions'] as $attrId => $attr) {
          if (isset($attributes[$attrId]))
            $attributes[$attrId] = array_merge($attributes[$attrId], $attr);
        }
      }
      // Get the attribute and control information required to build the custom occurrence attribute columns
      data_entry_helper::species_checklist_prepare_attributes($options, $attributes, $occAttrControls, $occAttrControlsExisting, $occAttrs);
      $beforegrid = '<span style="display: none;">Step 1</span>'."\n";
      if (isset($options['lookupListId'])) {
        $subSampleImagesToLoad=array();
        //Cycle through sub-samples of the main parent sample
        foreach ($subSampleRows as $subSampleIdx=>$subSampleRow) {          
          foreach (data_entry_helper::$entity_to_load as $key=>$value) {
            $keyParts=explode(':',$key);
            //Get an array of sample media to load onto the grid
            if (strpos($key,'third-level-smp-occ-grid') !== false && strpos($key,':sample_medium:id') !== false) {
              if (!in_array($keyParts[3],$subSampleImagesToLoad))
                $subSampleImagesToLoad[]=$keyParts[3];
            }  
          }
        }
        //For each sub-sample, add a row to the occurrences grid with the image loaded, this is then ready for the user.
        //To create occurrences with
        if (isset($subSampleImagesToLoad)) {
          $mediaIdArray = array();     
          foreach ($subSampleImagesToLoad as $subSampleImageIdx=>$subSampleImageToLoad) {
            $mediaIdArray[] = $subSampleImageToLoad;
            $beforegrid .= self::get_species_checklist_empty_row_with_image($options, $occAttrControls, $attributes, $subSampleImageIdx, $subSampleImageToLoad);
          }

          $encodedMediaArray = json_encode($mediaIdArray);
          data_entry_helper::$javascript .= "indiciaData.encodedMediaArray=".json_encode($encodedMediaArray).";\n";
        }
        $beforegrid .= self::get_species_checklist_clonable_row($options, $occAttrControls, $attributes);
      }
      $onlyImages = true;
      if ($options['mediaTypes']) {
        foreach($options['mediaTypes'] as $mediaType) {
          if (substr($mediaType, 0, 6)!=='Image:')
            $onlyImages=false;
        }
      }
      $grid = data_entry_helper::get_species_checklist_header($options, $occAttrs, $onlyImages);
      $rows = array();
      $imageRowIdxs = array();
      $taxonCounter = array();
      $rowIdx = 0;
      // tell the addTowToGrid javascript how many rows are already used, so it has a unique index for new rows
      data_entry_helper::$javascript .= "indiciaData['gridCounter-".$options['id']."'] = ".count($taxonRows).";\n";
      data_entry_helper::$javascript .= "indiciaData['gridSampleCounter-".$options['id']."'] = ".count($subSampleRows).";\n";
      // Loop through all the rows needed in the grid
      // Get the checkboxes (hidden or otherwise) that indicate a species is present
      if (is_array(data_entry_helper::$entity_to_load)) {
        $presenceValues = preg_grep("/^sc:[0-9]*:[0-9]*:present$/", array_keys(data_entry_helper::$entity_to_load));
      }
      // if subspecies are stored, then need to load up the parent species info into the $taxonRows data
      if ($options['subSpeciesColumn']) {
        self::load_parent_species($taxalist, $options);
        if ($options['subSpeciesRemoveSspRank'])
          // remove subspecific rank information from the displayed subspecies names by passing a regex
          data_entry_helper::$javascript .= "indiciaData.subspeciesRanksToStrip='".lang::get('(form[a\.]?|var\.?|ssp\.)')."';\n";
      }
      // track if there is a row we are editing in this grid
      $hasEditedRecord = false;
      if ($options['mediaTypes']) {
        $mediaBtnLabel = lang::get($onlyImages ? 'Add images' : 'Add media');
        $mediaBtnClass = 'sc' . $onlyImages ? 'Image' : 'Media' . 'Link';
      }
      foreach ($taxonRows as $txIdx => $rowIds) {
        $ttlId = $rowIds['ttlId'];
        $loadedTxIdx = isset($rowIds['loadedTxIdx']) ? $rowIds['loadedTxIdx'] : -1;
        $existing_record_id = isset($rowIds['occId']) ? $rowIds['occId'] : false;
        // Multi-column input does not work when image upload allowed
        $colIdx = count($options['mediaTypes']) ? 0 : (int)floor($rowIdx / (count($taxonRows)/$options['columns']));
        // Find the taxon in our preloaded list data that we want to output for this row
        $taxonIdx = 0;
        while ($taxonIdx < count($taxalist) && $taxalist[$taxonIdx]['id'] != $ttlId) {
          $taxonIdx += 1;
        }
        if ($taxonIdx >= count($taxalist))
          continue; // next taxon, as this one was not found in the list
        $taxon = $taxalist[$taxonIdx];
        // If we are using the sub-species column then when the taxon has a parent (=species) this goes in the
        // first column and we put the subsp in the second column in a moment.
        if (isset($options['subSpeciesColumn']) && $options['subSpeciesColumn'] && !empty($taxon['parent']))
          $firstColumnTaxon=$taxon['parent'];
        else
          $firstColumnTaxon=$taxon;
        // map field names if using a cached lookup       
        if ($options['cacheLookup'])
          $firstColumnTaxon = $firstColumnTaxon + array(
            'preferred_name' => $firstColumnTaxon['preferred_taxon'],
            'common' => $firstColumnTaxon['default_common_name']
          );
        // Get the cell content from the taxon_label template
        $firstCell = helper_base::mergeParamsIntoTemplate($firstColumnTaxon, 'taxon_label');
        // If the taxon label template is PHP, evaluate it.
        if ($options['PHPtaxonLabel']) $firstCell = eval($firstCell);
        // Now create the table cell to contain this.
        $colspan = isset($options['lookupListId']) && $options['rowInclusionCheck']!='alwaysRemovable' ? ' colspan="2"' : '';
        $row = '';
        // Add a delete button if the user can remove rows, add an edit button if the user has the edit option set, add a page link if user has that option set.
        if ($options['rowInclusionCheck']=='alwaysRemovable') {
          $imgPath = empty(helper_base::$images_path) ? helper_base::relative_client_helper_path()."../media/images/" : helper_base::$images_path;
          $speciesGridLinkPageIconSource = $imgPath."nuvola/find-22px.png";
          if ($options['editTaxaNames']) {
            $row .= '<td class="row-buttons">
                     <img class="action-button remove-row" src='.$imgPath.'nuvola/cancel-16px.png>
                     <img class="action-button edit-taxon-name" src='.$imgPath.'nuvola/package_editors-16px.png>';
            if ($options['includeSpeciesGridLinkPage']) {
              $row .= '<img class="species-grid-link-page-icon" title="'.$options['speciesGridPageLinkTooltip'].'" alt="Notes icon" src='.$speciesGridLinkPageIconSource.'>';
            }          
            $row .= '</td>';
          } else {
            $row .= '<td class="row-buttons"><img class="action-button remove-row" src='.$imgPath.'nuvola/cancel-16px.png>';
            if ($options['includeSpeciesGridLinkPage']) {
              $row .= '<img class="species-grid-link-page-icon" title="'.$options['speciesGridPageLinkTooltip'].'" alt="Notes icon" src='.$speciesGridLinkPageIconSource.'>';
            }
            $row .= '</td>';
          }
        }
        // if editing a specific occurrence, mark it up
        $editedRecord = isset($_GET['occurrence_id']) && $_GET['occurrence_id']==$existing_record_id;
        $editClass = $editedRecord ? ' edited-record ui-state-highlight' : '';
        $hasEditedRecord = $hasEditedRecord || $editedRecord;
        // Verified records can be flagged with an icon
        //Do an isset check as the npms_paths form for example uses the species checklist, but doesn't use an entity_to_load
        if (isset(data_entry_helper::$entity_to_load["sc:$loadedTxIdx:$existing_record_id:record_status"])) {
          $status = data_entry_helper::$entity_to_load["sc:$loadedTxIdx:$existing_record_id:record_status"];
          if (preg_match('/[VDR]/', $status)) {
            $img = false;
            switch ($status) {
              case 'V' : $img = 'ok'; $statusLabel = 'verified'; break;
              case 'D' : $img = 'dubious'; $statusLabel = 'queried'; break;
              case 'R' : $img = 'cancel'; $statusLabel = 'rejected'; break;
            }
            if ($img) {
              $label = lang::get($statusLabel);
              $title = lang::get('This record has been {1}. Changing it will mean that it will need to be rechecked by an expert.', $label);
              $firstCell .= "<img alt=\"$label\" title=\"$title\" src=\"{$imgPath}nuvola/$img-16px.png\">";
            }
          }
        }
        $row .= str_replace(array('{content}','{colspan}','{editClass}','{tableId}','{idx}'),
            array($firstCell,$colspan,$editClass,$options['id'],$colIdx), $indicia_templates['taxon_label_cell']);
        $hidden = ($options['rowInclusionCheck']=='checkbox' ? '' : ' style="display:none"');
        // AlwaysFixed mode means all rows in the default checklist are included as occurrences. Same for
        // AlwayeRemovable except that the rows can be removed.
        // If we are reloading a record there will be an entity_to_load which will indicate whether present should be checked.
        // This has to be evaluated true or false if reloading a submission with errors.
        if ($options['rowInclusionCheck']=='alwaysFixed' || $options['rowInclusionCheck']=='alwaysRemovable' ||
            (data_entry_helper::$entity_to_load!=null && array_key_exists("sc:$loadedTxIdx:$existing_record_id:present", data_entry_helper::$entity_to_load) &&
                data_entry_helper::$entity_to_load["sc:$loadedTxIdx:$existing_record_id:present"] == true)) {
          $checked = ' checked="checked"';
        } else {
          $checked='';
        }
        $row .= "\n<td class=\"scPresenceCell\" headers=\"$options[id]-present-$colIdx\"$hidden>";
        $fieldname = "sc:$options[id]-$txIdx:$existing_record_id:present";
        if ($options['rowInclusionCheck']==='hasData')
          $row .= "<input type=\"hidden\" name=\"$fieldname\" id=\"$fieldname\" value=\"$taxon[id]\"/>";
        else
          // this includes a control to force out a 0 value when the checkbox is unchecked.
          $row .= "<input type=\"hidden\" class=\"scPresence\" name=\"$fieldname\" value=\"0\"/>".
            "<input type=\"checkbox\" class=\"scPresence\" name=\"$fieldname\" id=\"$fieldname\" value=\"$taxon[id]\" $checked />";
        // If we have a grid ID attribute, output a hidden
        if (!empty($options['gridIdAttributeId'])) {
          $gridAttributeId = $options['gridIdAttributeId'];
          if (empty($existing_record_id)) {
            //If in add mode we don't need to include the occurrence attribute id
            $fieldname  = "sc:$options[id]-$txIdx::occAttr:$gridAttributeId";
            $row .= "<input type=\"hidden\" name=\"$fieldname\" id=\"$fieldname\" value=\"$options[id]\"/>";
          } else {
            $search = preg_grep("/^sc:[0-9]*:$existing_record_id:occAttr:$gridAttributeId:".'[0-9]*$/', array_keys(data_entry_helper::$entity_to_load));
            if (!empty($search)) {
              $match = array_pop($search);
              $parts = explode(':',$match);
              //The id of the existing occurrence attribute value is at the end of the data
              $idxOfOccValId = count($parts) - 1;
              //$txIdx is row number in the grid. We cannot simply take the data from entity_to_load as it doesn't contain the row number.
              $fieldname = "sc:$options[id]-$txIdx:$existing_record_id:occAttr:$gridAttributeId:$parts[$idxOfOccValId]";
              $row .= "<input type=\"hidden\" name=\"$fieldname\" id=\"$fieldname\" value=\"$options[id]\"/>";
            }
          }
        }
        $row .= "</td>";
        if ($options['speciesControlToUseSubSamples']) {
          $row .= "\n<td class=\"scSampleCell\" style=\"display:none\">";
          $fieldname = "sc:$options[id]-$txIdx:$existing_record_id:occurrence:sampleIDX";
          $value = $options['subSamplePerRow'] ? $smpIdx : $rowIds['smpIdx'];
          $row .= "<input type=\"hidden\" class=\"scSample\" name=\"$fieldname\" id=\"$fieldname\" value=\"$value\" />";
          $row .= "</td>";
          // always increment the sample index if 1 per row.
          if ($options['subSamplePerRow'])
            $smpIdx++;
        }
        $idx = 0;
       
        if ($options['mediaTypes']) {
          $existingImages = is_array(data_entry_helper::$entity_to_load) ? preg_grep("/^sc:$loadedTxIdx:$existing_record_id:occurrence_medium:id:[a-z0-9]*$/", array_keys(data_entry_helper::$entity_to_load)) : array();
          $row .= "\n<td class=\"ui-widget-content scAddMediaCell\">";
          $style = (count($existingImages)>0) ? ' style="display: none"' : '';
          $fieldname = "add-media:$options[id]-$txIdx:$existing_record_id";
          $row .= "<a href=\"\"$style class=\"add-media-link button $mediaBtnClass\" id=\"$fieldname\">" .
              "$mediaBtnLabel</a>";
          $row .= "</td>";
        }
        // Are we in the first column of a multicolumn grid, or doing single column grid? If so start new row.
        if ($colIdx === 0) {
          $rows[$rowIdx] = $row;
        } else {
          $rows[$rowIdx % (ceil(count($taxonRows)/$options['columns']))] .= $row;
        }
        $rowIdx++;
        if ($options['mediaTypes'] && count($existingImages) > 0) {
          $totalCols = ($options['lookupListId'] ? 2 : 1) + 1 /*checkboxCol*/ + count($occAttrControls)
              + ($options['occurrenceComment'] ? 1 : 0) + ($options['occurrenceSensitivity'] ? 1 : 0) + (count($options['mediaTypes']) ? 1 : 0);
          $rows[$rowIdx]='<td colspan="'.$totalCols.'">'.data_entry_helper::file_box(array(
            'table'=>"sc:$options[id]-$txIdx:$existing_record_id:occurrence_medium",
            'loadExistingRecordKey'=>"sc:$loadedTxIdx:$existing_record_id:occurrence_medium",
            'mediaTypes' => $options['mediaTypes'],
            'readAuth' => $options['readAuth']
          )).'</td>';
          $imageRowIdxs[]=$rowIdx;
          $rowIdx++;
        }
      }
      $grid .= "\n<tbody>\n";
      if (count($rows)>0)
        $grid .= data_entry_helper::species_checklist_implode_rows($rows, $imageRowIdxs);
      $grid .= "</tbody>\n";
      $grid = str_replace(
          array('{class}', '{id}', '{content}'),
          array(' class="'.implode(' ', $classlist).'"', " id=\"$options[id]\"", $grid),
          $indicia_templates['data-input-table']
      );
      // in hasData mode, the wrap_species_checklist method must be notified of the different default
      // way of checking if a row is to be made into an occurrence. This may differ between grids when
      // there are multiple grids on a page.
      if ($options['rowInclusionCheck']=='hasData') {
        $grid .= '<input name="rowInclusionCheck-' . $options['id'] . '" value="hasData" type="hidden" />';
        if (!empty($options['hasDataIgnoreAttrs']))
          $grid .= '<input name="hasDataIgnoreAttrs-' . $options['id'] . '" value="'
                . implode(',', $options['hasDataIgnoreAttrs']) . '" type="hidden" />';
      }
      // If the lookupListId parameter is specified then the user is able to add extra rows to the grid,
      // selecting the species from this list. Add the required controls for this.
      if (isset($options['lookupListId'])) {
        // Javascript to add further rows to the grid
        if (isset($indicia_templates['format_species_autocomplete_fn'])) {
          data_entry_helper::$javascript .= 'formatter = '.$indicia_templates['format_species_autocomplete_fn'];
        } else {
          data_entry_helper::$javascript .= "formatter = '".$indicia_templates['taxon_label']."';\n";
        }
        if (!empty(parent::$warehouse_proxy))
          $url = parent::$warehouse_proxy."index.php/services/data";
        else
          $url = helper_base::$base_url."index.php/services/data";
        data_entry_helper::$javascript .= "if (typeof indiciaData.speciesGrid==='undefined') {indiciaData.speciesGrid={};}\n";
        data_entry_helper::$javascript .= "indiciaData.speciesGrid['$options[id]']={};\n";
        data_entry_helper::$javascript .= "indiciaData.speciesGrid['$options[id]'].cacheLookup=".($options['cacheLookup'] ? 'true' : 'false').";\n";
        data_entry_helper::$javascript .= "indiciaData.speciesGrid['$options[id]'].numValues=".(!empty($options['numValues']) ? $options['numValues'] : 20).";\n";
        data_entry_helper::$javascript .= "indiciaData.speciesGrid['$options[id]'].selectMode=".(!empty($options['selectMode']) && $options['selectMode'] ? 'true' : 'false').";\n";
        //encoded media array is just and array of media items that has been json_encoded.
        //Add a row to the occurrence grid for each media item.
        data_entry_helper::$javascript .= "
        if (indiciaData.encodedMediaArray) {
          var encodedMediaArray = eval(indiciaData.encodedMediaArray);
          for (var i=0; i<encodedMediaArray.length; i++) {
            makeImageRowOrSpareRow('".
            $options['id']."', {'auth_token' : '".
            $options['readAuth']['auth_token']."', 'nonce' : '".$options['readAuth']['nonce']."'},'".$options['lookupListId']."','$url', null, false, null, null, encodedMediaArray[i]);
          }
        }
        \r\n";      
        data_entry_helper::$javascript .= "makeImageRowOrSpareRow('".
            $options['id']."', {'auth_token' : '".
            $options['readAuth']['auth_token']."', 'nonce' : '".$options['readAuth']['nonce']."'},'".$options['lookupListId']."','$url', null, false, null, null);\r\n";
      }
      // If options contain a help text, output it at the end if that is the preferred position
      $options['helpTextClass'] = (isset($options['helpTextClass'])) ? $options['helpTextClass'] : 'helpTextLeft';
      $r = $beforegrid . $grid;
      data_entry_helper::$javascript .= "$('#".$options['id']."').find('input,select').keydown(keyHandler);\n";
      //nameFilter is an array containing all the parameters required to return data for each of the
      //"Choose species names available for selection" filter types
      data_entry_helper::species_checklist_filter_popup($options, $nameFilter);
      if ($options['subSamplePerRow']) {
        // output a hidden block to contain sub-sample hidden input values.
        $r .= '<div id="'.$options['id'].'-blocks">'.
            data_entry_helper::get_subsample_per_row_hidden_inputs().
            '</div>';
      }     
      if ($hasEditedRecord) {
        data_entry_helper::$javascript .= "$('#$options[id] tbody tr').hide();\n";
        data_entry_helper::$javascript .= "$('#$options[id] tbody tr td.edited-record').parent().show();\n";
        data_entry_helper::$javascript .= "$('#$options[id] tbody tr td.edited-record').parent().next('tr.supplementary-row').show();\n";
        $r .= '<p>'.lang::get('You are editing a single record that is part of a larger sample, so any changes to the sample\'s information such as edits to the date or map reference '.
            'will affect the whole sample.')." <a id=\"species-grid-view-all-$options[id]\">".lang::get('View all the records in this sample or add more records.').'</a></p>';
        data_entry_helper::$javascript .= "$('#species-grid-view-all-$options[id]').click(function(e) {
  $('#$options[id] tbody tr').show();
  $(e.currentTarget).hide();
});\n";
        self::$onload_javascript .= "
if ($('#$options[id]').parents('.ui-tabs-panel').length) {
  indiciaFns.activeTab($('#controls'), $('#$options[id]').parents('.ui-tabs-panel')[0].id);
}\n";
      }
      return $r;
    } else {
      return $taxalist['error'];
    }
  }
 
 
  /**
   * Normally, the species checklist will handle loading the list of occurrences from the database automatically.
   * However, when a form needs access to occurrence data before loading the species checklist, this method
   * can be called to preload the data. The data is loaded into data_entry_helper::$entity_to_load and an array
   * of occurrences loaded is returned.
   * @param int $sampleId ID of the sample to load
   * @param array $readAuth Read authorisation array
   * @param boolean $loadMedia Array of media type terms to load.
   * @param boolean $extraParams Extra params to pass to the web service call for filtering.
   * @return array Array with key of occurrence_id and value of $taxonInstance.
   * Again, this is an altered copy of the one found in data_entry_helper, includes support for third level samples
   * May contain coded that is not needed and can be removed if possible
   */
  private static function preload_species_checklist_occurrences($sampleId, $readAuth, $loadMedia, $extraParams, &$subSamples, $useSubSamples, $subSampleMethodID='',$gridId, $useThirdLevelSamples=false) {
    //Obviously this would need to not be hardcoded
    $occurrenceIds = array();
    $taxonCounter = array();
    // don't load from the db if there are validation errors, since the $_POST will already contain all the
    // data we need.
    if (is_null(data_entry_helper::$validation_errors)) {
      // strip out any occurrences we've already loaded into the entity_to_load, in case there are other
      // checklist grids on the same page. Otherwise we'd double up the record data.
      foreach(data_entry_helper::$entity_to_load as $key => $value) {
        $parts = explode(':', $key);
        if (count($parts) > 2 && $parts[0] == 'sc' && $parts[1]!='-idx-') {
          unset(data_entry_helper::$entity_to_load[$key]);
        }
      }
      $extraParamsCopy=$extraParams;
      if($useSubSamples){
        $extraParams += $readAuth + array('view'=>'detail','parent_id'=>$sampleId,'deleted'=>'f', 'orderby'=>'id', 'sortdir'=>'ASC' );          
        if($subSampleMethodID != '')
          $extraParams['sample_method_id'] = $subSampleMethodID;
        $subSamples = data_entry_helper::get_population_data(array(
            'table' => 'sample',
            'extraParams' => $extraParams,
            'nocache' => true
        ));   
        if (!empty($useThirdLevelSamples) && $useThirdLevelSamples==true) {
          $allThirdLevelSamples=array();
          foreach ($subSamples as $subSample) {
            $extraParams = $extraParamsCopy + $readAuth + array('view'=>'detail','parent_id'=>$subSample['id'],'deleted'=>'f', 'orderby'=>'id', 'sortdir'=>'ASC' );          
            //if($subSampleMethodID != '')
            //  $extraParams['sample_method_id'] = $subSampleMethodID;
            $thirdLevelSamplesForSingleSubSample = data_entry_helper::get_population_data(array(
                'table' => 'sample',
                'extraParams' => $extraParams,
                'nocache' => true
            ));
            $allThirdLevelSamples=array_merge($allThirdLevelSamples,$thirdLevelSamplesForSingleSubSample);
          }
          //If there are third level samples then the occurrences grid needs to be loaded from these,
          //otherwise keep the load from the second level samples as it means we haven't yet saved the occurrences yet (and
          //as such the third-level samples won't have been created yet).
          if (!empty($allThirdLevelSamples))
            $subSamples=$allThirdLevelSamples;
        }
        $subSampleList = array();
        foreach($subSamples as $idx => $subsample)  {
          $subSampleList[] = $subsample['id'];
          //If we are using third level samples and there are no third level samples yet, then we don't want to load
          //the ids of the second level samples otherwise the system will use these instead of creating the new sub-sample level
          if (!(empty($allThirdLevelSamples)&&!empty($useThirdLevelSamples) && $useThirdLevelSamples==true)) {
            data_entry_helper::$entity_to_load['sc:'.$idx.':'.$subsample['id'].':sample:id'] = $subsample['id'];
          }
          data_entry_helper::$entity_to_load['sc:'.$idx.':'.$subsample['id'].':sample:geom'] = $subsample['wkt'];
          data_entry_helper::$entity_to_load['sc:'.$idx.':'.$subsample['id'].':sample:wkt'] = $subsample['wkt'];
          data_entry_helper::$entity_to_load['sc:'.$idx.':'.$subsample['id'].':sample:location_id'] = $subsample['location_id'];
          data_entry_helper::$entity_to_load['sc:'.$idx.':'.$subsample['id'].':sample:entered_sref'] = $subsample['entered_sref'];
          data_entry_helper::$entity_to_load['sc:'.$idx.':'.$subsample['id'].':sample:entered_sref_system'] = $subsample['entered_sref_system'];
          data_entry_helper::$entity_to_load['sc:'.$idx.':'.$subsample['id'].':sample:date_start'] = $subsample['date_start'];
          data_entry_helper::$entity_to_load['sc:'.$idx.':'.$subsample['id'].':sample:date_end'] = $subsample['date_end'];
          data_entry_helper::$entity_to_load['sc:'.$idx.':'.$subsample['id'].':sample:date_type'] = $subsample['date_type'];
          data_entry_helper::$entity_to_load['sc:'.$idx.':'.$subsample['id'].':sample:sample_method_id'] = $subsample['sample_method_id'];
        }
        unset($extraParams['parent_id']);
        unset($extraParams['sample_method_id']);
        $extraParams['sample_id']=$subSampleList;
        $sampleCount = count($subSampleList);
      } else {
        $extraParams += $readAuth + array('view'=>'detail','sample_id'=>$sampleId,'deleted'=>'f', 'orderby'=>'id', 'sortdir'=>'ASC' );
          $sampleCount = 1;
      }
      if($sampleCount>0) {
        $occurrences = data_entry_helper::get_population_data(array(
          'table' => 'occurrence',
          'extraParams' => $extraParams,
          'nocache' => true
        ));
        foreach($occurrences as $idx => $occurrence){
          if($useSubSamples){
            foreach($subSamples as $sidx => $subsample){
              if($subsample['id'] == $occurrence['sample_id'])
                data_entry_helper::$entity_to_load['sc:'.$idx.':'.$occurrence['id'].':occurrence:sampleIDX'] = $sidx;
            }
          }
          data_entry_helper::$entity_to_load['sc:'.$idx.':'.$occurrence['id'].':present'] = $occurrence['taxa_taxon_list_id'];
          data_entry_helper::$entity_to_load['sc:'.$idx.':'.$occurrence['id'].':record_status'] = $occurrence['record_status'];
          data_entry_helper::$entity_to_load['sc:'.$idx.':'.$occurrence['id'].':occurrence:comment'] = $occurrence['comment'];
          data_entry_helper::$entity_to_load['sc:'.$idx.':'.$occurrence['id'].':occurrence:sensitivity_precision'] = $occurrence['sensitivity_precision'];
          // Warning. I observe that, in cases where more than one occurrence is loaded, the following entries in
          // $entity_to_load will just take the value of the last loaded occurrence.
          data_entry_helper::$entity_to_load['occurrence:record_status']=$occurrence['record_status'];
          data_entry_helper::$entity_to_load['occurrence:taxa_taxon_list_id']=$occurrence['taxa_taxon_list_id'];
          data_entry_helper::$entity_to_load['occurrence:taxa_taxon_list_id:taxon']=$occurrence['taxon'];
          // Keep a list of all Ids
          $occurrenceIds[$occurrence['id']] = $idx;
        }
        //Load items onto the occurrences grid if there are occurrences, or images from sub-samples to display
        if(count($occurrenceIds)>0||count($subSamples)>0) {
          // load the attribute values into the entity to load as well
          $attrValues = data_entry_helper::get_population_data(array(
            'table' => 'occurrence_attribute_value',
            'extraParams' => $readAuth + array('occurrence_id' => array_keys($occurrenceIds)),
            'nocache' => true
          ));
          foreach($attrValues as $attrValue) {
            data_entry_helper::$entity_to_load['sc:'.$occurrenceIds[$attrValue['occurrence_id']].':'.$attrValue['occurrence_id'].':occAttr:'.$attrValue['occurrence_attribute_id'].(isset($attrValue['id'])?':'.$attrValue['id']:'')]
                = $attrValue['raw_value'];
          }
          if (empty($allThirdLevelSamples)) {
            if (count($loadMedia)>0) {   
              $media=array();
              //TODO: Probably would be better to do this bit with a report but haven't got round to writing it.
              foreach ($subSamples as $subSample) {
                //TODO: This will currently overwrite previous result, but for now this doesn't matter much, won't happen if I use a report
                $data = data_entry_helper::get_population_data(array(
                  'table' => 'sample_medium',
                  'extraParams' => $readAuth + array('sample_id' => $subSample['id']),
                  'nocache' => true
                ));
                foreach ($data as $mediaItem) {
                  $media[]=$mediaItem;
                }
              }
              //Seasearch specific as only then do we load sample media onto a grrid
              foreach($media as $rowIdx=>$medium) {
                data_entry_helper::$entity_to_load['sc:'.$gridId.':'.$rowIdx.':'.$medium['id'].':sample_medium:id']
                    = $medium['id'];
                data_entry_helper::$entity_to_load['sc:'.$gridId.':'.$rowIdx.':'.$medium['id'].':sample_medium:path']
                    = $medium['path'];
                data_entry_helper::$entity_to_load['sc:'.$gridId.':'.$rowIdx.':'.$medium['id'].':sample_medium:caption']
                    = $medium['caption'];
                data_entry_helper::$entity_to_load['sc:'.$gridId.':'.$rowIdx.':'.$medium['id'].':sample_medium:media_type_id']
                    = $medium['media_type_id'];
                data_entry_helper::$entity_to_load['sc:'.$gridId.':'.$rowIdx.':'.$medium['id'].':sample_medium:media_type']
                    = $medium['media_type'];
              }
            }
          } else {
            if (count($loadMedia)>0) {
              $media = data_entry_helper::get_population_data(array(
                'table' => 'occurrence_medium',
                'extraParams' => $readAuth + array('occurrence_id' => array_keys($occurrenceIds)),
                'nocache' => true
              ));
              foreach($media as $medium) {
                data_entry_helper::$entity_to_load['sc:'.$occurrenceIds[$medium['occurrence_id']].':'.$medium['occurrence_id'].':occurrence_medium:id:'.$medium['id']]
                    = $medium['id'];
                data_entry_helper::$entity_to_load['sc:'.$occurrenceIds[$medium['occurrence_id']].':'.$medium['occurrence_id'].':occurrence_medium:path:'.$medium['id']]
                    = $medium['path'];
                data_entry_helper::$entity_to_load['sc:'.$occurrenceIds[$medium['occurrence_id']].':'.$medium['occurrence_id'].':occurrence_medium:caption:'.$medium['id']]
                    = $medium['caption'];
                data_entry_helper::$entity_to_load['sc:'.$occurrenceIds[$medium['occurrence_id']].':'.$medium['occurrence_id'].':occurrence_medium:media_type_id:'.$medium['id']]
                    = $medium['media_type_id'];
                data_entry_helper::$entity_to_load['sc:'.$occurrenceIds[$medium['occurrence_id']].':'.$medium['occurrence_id'].':occurrence_medium:media_type:'.$medium['id']]
                    = $medium['media_type'];
              }
            }
          }
        }
      }
    }
    return $occurrenceIds;
  }
 
  /* Similar to data entry helper cloneable row, apart from the following functions support cloning a row onto the grid with images placed into the rows */
  private static function get_species_checklist_empty_row($options, $occAttrControls, $attributes) {
    global $indicia_templates;
    $colspan = isset($options['lookupListId']) || $options['rowInclusionCheck']=='alwaysRemovable' ? ' colspan="2"' : '';
    $r = str_replace(array('{colspan}','{tableId}','{idx}','{editClass}'), array($colspan, $options['id'], 0, ''), $indicia_templates['taxon_label_cell']);
    $fieldname = "sc:$options[id]--idx-:";
    if ($options['subSpeciesColumn']) {
      $r .= '<td class="ui-widget-content scSubSpeciesCell"><select class="scSubSpecies" style="display: none" ' .
        "id=\"$fieldname:occurrence:subspecies\" name=\"$fieldname:occurrence:subspecies\" onchange=\"SetHtmlIdsOnSubspeciesChange(this.id);\">";
      $r .= '</select><span class="species-checklist-select-species">'.lang::get('Select a species first').'</span></td>';
    }
    $hidden = ($options['rowInclusionCheck']=='checkbox' ? '' : ' style="display:none"');
    $r .= '<td class="scPresenceCell" headers="'.$options['id'].'-present-0"'.$hidden.'>';
    $r .= "<input type=\"checkbox\" class=\"scPresence\" name=\"$fieldname:present\" id=\"$fieldname:present\" value=\"\" />";
    // If we have a grid ID attribute, output a hidden
    if (!empty($options['gridIdAttributeId']))
      $r .= "<input type=\"hidden\" name=\"$fieldname:occAttr:$options[gridIdAttributeId]\" id=\"$fieldname:occAttr:$options[gridIdAttributeId]\" value=\"$options[id]\"/>";
    $r .= '</td>';
    if ($options['speciesControlToUseSubSamples'])
      $r .= '<td class="scSampleCell" style="display:none"><input type="hidden" class="scSample" name="'.
          $fieldname.':occurrence:sampleIDX" id="'.$fieldname.':occurrence:sampleIDX" value="" /></td>';
    $idx = 0;

    return $r;
  }

  //The row is empty but includes an image.
  private static function get_species_checklist_empty_row_with_image($options, $occAttrControls, $attributes, $rowIdx, $mediaId) {
    $rowClass='scOccImageRow';
    $rowId=$options['id'].'-scOccImageRow-'.$mediaId;
    $r='<table><tbody><tr class="'.$rowClass.'" id="'.$rowId.'">';
    $r.=self::get_species_checklist_empty_row($options, $occAttrControls, $attributes);
    if ($options['mediaTypes']) {
      $totalCols = ($options['lookupListId'] ? 2 : 1) + 1 /*checkboxCol*/ + (count($options['mediaTypes']) ? 1 : 0) + count($occAttrControls);
      $gridId=$options['id'];
      $r.='<td colspan="'.$totalCols.'">'.data_entry_helper::file_box(array(
        'table'=>"sc:$gridId-$rowIdx:$mediaId:sample_medium",
        'loadExistingRecordKey'=>"sc:$gridId:$rowIdx:$mediaId:sample_medium",
        'mediaTypes' => $options['mediaTypes'],
        'readAuth' => $options['readAuth']
      )).'</td>';
    }
    $r .= "</tr></tbody></table>\n";
    return $r;
  }

  /**
   * When the species checklist grid has a lookup list associated with it, this is a
   * secondary checklist which you can pick species from to add to the grid. As this happens,
   * a hidden table is used to store a clonable row which provides the template for new rows
   * to be added to the grid.
   * @param array $options Options array passed to the species grid.
   * @param array $occAttrControls List of the occurrence attribute controls, keyed by attribute ID.
   * @param array $attributes List of attribute definitions loaded from the database.
   * Again similar to data_entry_helper, but with support for preloading a cloneable row onto the grid with an image placed in it.
   */
  private static function get_species_checklist_clonable_row($options, $occAttrControls, $attributes) {
    $rowClass='scClonableRow';
    $rowId=$options['id'].'-scClonableRow';
    $r='<table style="display: none"><tbody><tr class="'.$rowClass.'" id="'.$rowId.'">';
    $r.=self::get_species_checklist_empty_row($options, $occAttrControls, $attributes);
    if ($options['mediaTypes']) {
      $onlyLocal = true;
      $onlyImages = true;
      foreach ($options['mediaTypes'] as $mediaType) {
        if (!preg_match('/:Local$/', $mediaType))
          $onlyLocal=false;
        if (!preg_match('/^Image:/', $mediaType))
          $onlyImages=false;
      }
      $label = $onlyImages ? 'Add images' : 'Add media';
      $class = 'sc' . $onlyImages ? 'Image' : 'Media' . 'Link';
      $r .= '<td class="ui-widget-content scAddMediaCell"><a href="" class="add-media-link button '.$class.'" style="display: none" id="add-media:'.$options['id'].'--idx-:">'.
          lang::get($label).'</a><span class="species-checklist-select-species">'.lang::get('Select a species first').'</span></td>';
    }
    $r .= "</tr></tbody></table>\n";
    return $r;
  }
 
  /**
   * Based on wrap_species_checklist_with_third_level_samples in data_entry_helper.
   * Altered for Seasearch as it needs to understand there is a third level of samples. There is one third level sample for each occurrence to hold its spatial reference.
   */
    private static function wrap_species_checklist_with_third_level_samples($arr, $include_if_any_data=false,
          $zero_attrs = true, $zero_values=array('0','None','Absent'), $gridsToExclude=array()){
    if (array_key_exists('website_id', $arr)){
      $website_id = $arr['website_id'];
    } else {
      throw new Exception('Cannot find website id in POST array!');
    }
    // determiner and record status can be defined globally for the whole list.
    if (array_key_exists('occurrence:determiner_id', $arr))
      $determiner_id = $arr['occurrence:determiner_id'];
    if (array_key_exists('occurrence:record_status', $arr))
      $record_status = $arr['occurrence:record_status'];
    // Set the default method of looking for rows to include - either using data, or the checkbox (which could be hidden)
    $include_if_any_data = $include_if_any_data || (isset($arr['rowInclusionCheck']) && $arr['rowInclusionCheck']=='hasData');

    $occurrenceRecords = array();
    $sampleRecord = array();
    $sampleRecords = array();
    $subModels = array();
    foreach ($arr as $key=>$value){
      $gridExcluded=false;
      foreach ($gridsToExclude as $gridToExclude) {
        if (substr($key, 0, strlen($gridToExclude)+3)=='sc:'.$gridToExclude) {
          $gridExcluded=true;
        }
      }
      //Only look at rows on occurrences grid excluding the clonable row
      if ($gridExcluded===false && (strpos($key,'sc:') !== false) && (strpos($key,'-idx-') === false)){ //discard the hidden cloneable rows
        // Don't explode the last element for occurrence attributes
        $a = explode(':', $key, 4);
        $b = explode(':', $a[3], 2);
        //At this stage we just collected all the information to create a general sample which
        //can be duplicated for each occurrence. This sample is then altered later in submission to set things like entered_sref.
        if($b[0] == "sample" || $b[0] == "smpAttr"){
          $sampleRecord[$a[1]][$a[3]] = $value;
        } else {
          //Make a list of occurrences
          $occurrenceRecords[$a[1]][$a[3]] = $value;
        }
      }
    } 
    $sampleRecords=array();
    foreach ($occurrenceRecords as $id => $record) {
      $present = data_entry_helper::wrap_species_checklist_record_present($record, $include_if_any_data,
          $zero_attrs, $zero_values, array());
      if (array_key_exists('id', $record) || $present!==null) { // must always handle row if already present in the db
        if ($present===null)
          // checkboxes do not appear if not checked. If uncheck, delete record.
          $record['deleted'] = 't';
        else
          $record['zero_abundance']=$present ? 'f' : 't';
        $record['taxa_taxon_list_id'] = $record['present'];
        $record['website_id'] = $website_id;
        if (isset($determiner_id)) {
          $record['determiner_id'] = $determiner_id;
        }
        if (isset($record_status)) {
          $record['record_status'] = $record_status;
        }
        $occ = data_entry_helper::wrap($record, 'occurrence');
        data_entry_helper::attachOccurrenceMediaToModel($occ, $record);
        //Duplicate the general sample record for each occurrence, these are then altered later in submission specifically for the occurrence
        $sampleRecords[] = $sampleRecord;
        //Added the occurrence to the sample
        $sampleRecords[count($sampleRecords)-1]['occurrences']=array();
        $sampleRecords[count($sampleRecords)-1]['occurrences'][] = array('fkId' => 'sample_id','model' => $occ);
      }
    }
    foreach ($sampleRecords as $id => $sampleRecord) {
      $occs = $sampleRecord['occurrences'];
      unset($sampleRecord['occurrences']);
      $sampleRecord['website_id'] = $website_id;
      // copy essentials down to each subsample
      if (!empty($arr['survey_id']))
        $sampleRecord['survey_id'] = $arr['survey_id'];
      if (!empty($arr['sample:date']))
        $sampleRecord['date'] = $arr['sample:date'];
      if (!empty($arr['sample:entered_sref_system']))
        $sampleRecord['entered_sref_system'] = $arr['sample:entered_sref_system'];
      if (!empty($arr['sample:location_name']) && empty($sampleRecord['location_name']))
        $sampleRecord['location_name'] = $arr['sample:location_name'];
      if (!empty($arr['sample:input_form']))
        $sampleRecord['input_form'] = $arr['sample:input_form'];
      $subSample = data_entry_helper::wrap($sampleRecord, 'sample');
      // Add the subsample/soccurrences in as subModels without overwriting others such as a sample image
      if (array_key_exists('subModels', $subSample)) {
        $subSample['subModels'] = array_merge($sampleMod['subModels'], $occs);
      } else {
        $subSample['subModels'] = $occs;
      }
      $subModel = array('fkId' => 'parent_id', 'model' => $subSample);
      $copyFields = array();
      if(!isset($sampleRecord['date'])) $copyFields = array('date_start'=>'date_start','date_end'=>'date_end','date_type'=>'date_type');
      if(!isset($sampleRecord['survey_id'])) $copyFields['survey_id'] = 'survey_id';
      if(count($copyFields)>0) $subModel['copyFields'] = $copyFields; // from parent->to child
      $subModels[] = $subModel;
    }
    return $subModels;
  }
  
  /**
   * Convert date from GPX file into format suitable for use with PHP strToTime function
   */
  private static function convertGPXDateToStrToTimeCompatibleFormat($gpxDate) {
    //Remove Z off end of GPX date
    $gpxDate=substr($gpxDate, 0, -1);
    //Split the date and time which are separated by letter T
    $gpxDateTimeSplit=explode('T',$gpxDate);
    //Re-assemble with space between date and time.
    return $gpxDateTimeSplit[0].' '.$gpxDateTimeSplit[1];
  }
}