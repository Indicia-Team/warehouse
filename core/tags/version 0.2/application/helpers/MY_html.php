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
        'fk_created_by', 'fk_updated_by', 'fk_meaning', 'fk_taxon_meaning', 'deleted');
     if ($default) {
       $r .= '<option>'.html::specialchars($default).'</option>';
     }
     foreach ($model->getSubmittableFields(true) as $name => $displayName) {
       if (!in_array($name, $skipped)) {
         $r .= '<option value="'.$name.'">';
         $fieldName = substr($name,3);
         if ($displayName=='') {
           $displayName = self::leadingCaps($fieldName);
         }
         if (substr($name, 0, 3)=='fk_') {
           // if the foreign key name does not match its table, also output the table name
           if (array_key_exists($fieldName, $model->belongs_to)) {
             $fkModel = ORM::factory($model->belongs_to[$fieldName]);
             $fieldDesc = $displayName.' from '.self::leadingCaps($model->belongs_to[$fieldName]);
           } elseif ($model instanceof ORM_Tree && $fieldName == 'parent') {
             $fkModel = ORM::factory(inflector::singular($model->getChildren()));
             $fieldDesc = 'Parent '.self::leadingCaps($model->getChildren());
           } else {
              $fkModel = ORM::factory($fieldName);
              $fieldDesc = $displayName;
           }
           // If the search field name does not match the fk, include this
           if ($fkModel->getSearchField()!= $fieldName) {
              $searchField = self::leadingCaps($fkModel->getSearchField());
           } else {
              $searchField=$displayName;
           }
           $option = $fieldDesc.($fieldDesc!=$searchField ? ' '.$searchField : '');
         } else {
           $option = self::leadingCaps($name);
         }
         $r .= $option.'</option>';
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

}
?>
