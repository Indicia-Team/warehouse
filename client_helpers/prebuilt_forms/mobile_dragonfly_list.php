<?php
require_once('mobile_species_list.php');

global $list_templates;

class iform_mobile_dragonfly_list extends iform_mobile_species_list {


  /**
   * Return the form metadata. Note the title of this method includes the name of the form file. This ensures
   * that if inheritance is used in the forms, subclassed forms don't return their parent's form definition.
   * @return array The definition of the form.
   * @todo rename this method.
   */
  public static function get_mobile_dragonfly_list_definition() {
    return array(
      'title'=>'Dragonfly List',
      'category' => 'Mobile',
      'helpLink'=>'<optional help URL>',
      'description'=>'Generates a dragonfly species list.'
    );
  }

  public static function getFixedBlankPage($id = NULL, $caption = NULL){
    $options = array();
    $options['href'] = '#';
    $options['caption'] = 'Back';
    $options['icon'] = 'arrow-l';
    $options['iconpos'] = 'notext';
    $back_button = "<div class='ui-btn-left' data-role='controlgroup'
        data-type='horizontal'>";
    $back_button .= mobile_entry_helper::apply_template('jqmBackButton', $options);
    $back_button .= "</div>";

    return [
      JQM_ATTR => array('id' => $id),
      JQM_CONTENT => [
        JQM_HEADER => [
          JQM_ATTR => array("data-position" => "fixed"),
          JQM_CONTENT => array($back_button, $caption)
        ],
        JQM_CONTENT => [
          JQM_ATTR => array(),
          JQM_CONTENT => array()
        ],
        JQM_FOOTER => [
          JQM_ATTR => array("data-position" => "fixed"),
          JQM_CONTENT => array()
        ]
      ]
    ];
  }
}