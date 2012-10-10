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
 * @subpackage Helpers
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL
 * @link 	http://code.google.com/p/indicia/
 */

defined('SYSPATH') or die('No direct script access.');

class html extends html_Core {

 /**
  * Outputs an error message in a span, but only if there is something to output 
  */
  public static function error_message($message)
  {
    if ($message) echo '<br/><span class="form-notice ui-state-error ui-corner-all">'.$message.'</span>';
  }

  public static function page_error($title, $description, $link_title=null, $link=null) {
    $r = '<div class="page-notice ui-state-error ui-corner-all">'.
        '<div class="ui-widget-header ui-corner-all"><span class="ui-icon ui-icon-alert"></span>'.
        "$title</div>$description";
    if ($link_title!=null) {
      $r .= "<a href=\"$link\" class=\"button ui-state-default ui-corner-all\">$link_title</a>";
    }
    $r .= "</div>\n";
    return $r;
  }

  public static function page_notice($title, $description, $icon='info') {
    $r = '<div class="page-notice ui-state-highlight ui-corner-all">'.
           '<div class="ui-widget-header ui-corner-all"><span class="ui-icon ui-icon-'.$icon.'"></span>'.
        "$title</div>$description</div>";
    return $r;
  }
   
   /**
    * Returns the initial value for an edit control on a page. This is either loaded from the $_POST
    * array (if reloading after a failed attempt to save) or from the model or initial default value
    * otherwise. 
    * 
    * @param ORM $values List of values to load in an array
    * @param string fieldname The fieldname should be of form model:fieldname. If the model 
    * part indicates a different model then the field value will be loaded from the other 
    * model (assuming that model is linked to the main one. E.g.'taxon:description' would load the
    * $model->taxon->description field.
    */
   public static function initial_value($values, $fieldname) {     
     if (array_key_exists($fieldname, $values)) {
       return self::specialchars($values[$fieldname]);
     } else {
       return null;
     }          
   }
   
   /** 
    * Return HTML to output the default OK and Cancel buttons to display at the bottom of an edit form. Also
    * outputs a delete button if the $allowDelete parameter is true.
    * 
    * @param boolean $allowDelete If true, then a delete button is included in the output.
    * @param boolean $readOnly If true, then the only button is a form cancel button.
    * @param boolean $allowUserSelectNextPage If true then then a select control is output which lets the user define
    * whether to continue adding records on the add new page or to return the index page for the current model.
    */
   public static function form_buttons($allowDelete, $readOnly=false, $allowUserSelectNextPage=true) {      
     $r = '<fieldset class="button-set">'."\n";
     if ($readOnly) {
       $r .= '<input type="submit" name="submit" value="'.kohana::lang('misc.cancel').'" class="ui-corner-all ui-state-default button" />'."\n";
       
     } else {
       $r .= '<input type="submit" name="submit" value="'.kohana::lang('misc.save').'" class="ui-corner-all ui-state-default button ui-priority-primary" />'."\n"; 
       $r .= '<input type="submit" name="submit" value="'.kohana::lang('misc.cancel').'" class="ui-corner-all ui-state-default button" />'."\n";
       if ($allowDelete) {
         $r .= '<input type="submit" name="submit" value="'.kohana::lang('misc.delete').'" onclick="if (!confirm(\''.kohana::lang('misc.confirm_delete').'\')) {return false;}" class="ui-corner-all ui-state-default button" />'."\n";
       }
       // add a drop down to select action after submit clicked. Needs to remember its previous setting from the session, 
       // since we normally arrive here after a redirect.
       if (isset($_SESSION['what-next']) && $_SESSION['what-next']=='add') {
         $selAdd = ' selected="selected"';
         $selReturn='';
       } else {
         $selAdd = '';
         $selReturn=' selected="selected"';
       }
       if ($allowUserSelectNextPage) {
         $r .= '<label for="next-action">'.kohana::lang('misc.then').'</label>';
         $r .= '<select id="what-next" name="what-next"><option value="return"'.$selReturn.'>go back to the list</option><option value="add"'.$selAdd.'>add new</option></select>';
       }
     }
     $r .= '</fieldset>';
     return $r;
   }
   
   /** 
    * Output a thumbnail or other size of an image, with a link to the full sized image suitable 
    * for the fancybox jQuery plugin.
    * @param string $filename Name of a file within the upload folder.
    * @param string $size Name of the file size, normally thumb or med depending on the image handling config.
    * @return string HTML to insert into the page, with the anchored image element.
    */
   public static function sized_image($filename, $size='thumb') {
     $img_config = kohana::config('indicia.image_handling');
     // Dynamically build the HTML sizing attrs for the thumbnail from the config. We may not know
     // both dimensions.
     $sizing = '';
     if ($img_config && array_key_exists($size, $img_config)) {
       if (array_key_exists('width', $img_config['thumb']))
         $sizing = ' width="'.$img_config[$size]['width'].'"';
       if (array_key_exists('height', $img_config[$size]))
         $sizing .= ' height="'.$img_config[$size]['height'].'"';
       
     }
     return '<a href="'.url::base()."upload/$filename\" class=\"fancybox\">".
         '<img src="'.url::base()."upload/$size-$filename\"$sizing /></a>";
     
   }

}
?>
