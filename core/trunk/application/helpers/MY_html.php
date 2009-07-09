<?php defined('SYSPATH') or die('No direct script access.');

class html extends html_Core {

   /* Outputs an error message in a span, but only if there is something to output */
  public static function error_message($message)
  {
    if ($message) echo '<span class="form_error">'.$message.'</span>';
  }

  /**
   * Returns a list of columns as an list of <options> for inclusion in an HTML drop down,
   * loading the columns from a model that are available to import data into
   * (excluding the id and metadata).
   */
   public static function model_field_options($model, $default)
   {
     $r = '';
     $skipped = array('id', 'created_by_id', 'created_on', 'updated_by_id', 'updated_on', 'fk_meaning_id',
         'deleted');
     if ($default) {
       $r .= '<option>'.html::specialchars($default).'</option>';
     }
     foreach ($model->getSubmittableFields(true) as $name => $dbtype) {
       if (!in_array($name, $skipped)) {
         $r .= '<option value="'.$name.'">';
         $fieldName = substr($name,3);
         if (substr($name, 0, 3)=='fk_') {
           // if the foreign key name does not match its table, also output the table name
           if (array_key_exists($fieldName, $model->belongs_to)) {
             $fkModel = ORM::factory($model->belongs_to[$fieldName]);
             $tableSuffix = ' (from '.$model->belongs_to[$fieldName].' table)';
           } elseif ($model instanceof ORM_Tree && $fieldName == 'parent') {
             $fkModel = ORM::factory(inflector::singular($model->getChildren()));
             $tableSuffix = ' (from '.$model->getChildren().' table)';
           } else {
              $fkModel = ORM::factory($fieldName);
              $tableSuffix = '';
           }
           // If the search field name does not match the fk, include this
           if ($fkModel->getSearchField()!= $fieldName) {
              $fieldSuffix = ' '.$fkModel->getSearchField();
           } else {
              $fieldSuffix='';
           }
           $option = $fieldName.$fieldSuffix.$tableSuffix;
         } else {
           $option = $name;
         }
         $r .= inflector::humanize(ucwords(preg_replace('/[\s_]+/', ' ', $option)));
         $r .= '</option>';
       }
     }
     return $r;
   }

}
?>
