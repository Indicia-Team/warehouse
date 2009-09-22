<?php defined('SYSPATH') or die('No direct script access.');

class html extends html_Core {

   /* Outputs an error message in a span, but only if there is something to output */
  public static function error_message($message)
  {
    if ($message) echo '<br/><span class="form-notice ui-state-error ui-corner-all">'.$message.'</span>';
  }

  /**
   * Returns a list of columns as an list of <options> for inclusion in an HTML drop down,
   * loading the columns from a model that are available to import data into
   * (excluding the id and metadata).
   */
   public static function model_field_options($model, $default)
   {
     $r = '';
     $skipped = array('id', 'created_by_id', 'created_on', 'updated_by_id', 'updated_on',
        'fk_created_by', 'fk_updated_by', 'fk_meaning', 'fk_taxon_meaning', 'deleted', 'image_path');
     if ($default) {
       $r .= '<option>'.html::specialchars($default).'</option>';
     }     
     foreach ($model->getSubmittableFields(true) as $field) {              
       list($prefix,$fieldname)=explode(':',$field);
       // Skip the metadata fields
       if (!in_array($fieldname, $skipped)) {
         // make a clean looking caption         
         if (substr($fieldname,0,3)=='fk_') {
           $captionSuffix=' (lookup)';
         } else {
           $captionSuffix='';
         }
         $fieldname=str_replace(array('fk_','_id'), array('',''), $fieldname);
         if ($prefix==$model->object_name || $prefix=="metaFields" || $prefix==substr($fieldname,0,strlen($prefix))) {
           $caption = self::leadingCaps($fieldname.$captionSuffix);
         } else {       
           $caption = self::leadingCaps("$prefix $fieldname$captionSuffix");
         }      
         $r .= '<option value="'.self::specialchars($field).'">'.self::specialchars($caption).'</option>';
       }       
     }
     return $r;
   }

  /**
   * Humanize a piece of text by inserting spaces instead of underscores, and making first letter
   * of each word capital.
   *
   * @param string $text The text to alter.
   * @return The altered string.
   */
  private static function leadingCaps($text) {
    return inflector::humanize(ucwords(preg_replace('/[\s_]+/', ' ', $text)));
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
    */
   public static function form_buttons($allowDelete) {      
     $r = '<fieldset class="button-set">'."\n";
     $r .= '<input type="submit" name="submit" value="Save" class="ui-corner-all ui-state-default button ui-priority-primary" />'."\n"; 
     $r .= '<input type="submit" name="submit" value="Cancel" class="ui-corner-all ui-state-default button" />'."\n";
     if ($allowDelete) {
       $r .= '<input type="submit" name="submit" value="Delete" onclick="if (!confirm(\'Are you sure you want to delete this record?\')) {return false;}" class="ui-corner-all ui-state-default button" />'."\n";
     }
     $r .= '</fieldset>';
     return $r;
   }

}
?>
