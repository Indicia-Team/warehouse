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
 * @subpackage Views
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL
 * @link 	http://code.google.com/p/indicia/
 */

$id = html::initial_value($values, 'licence:id');
require_once(DOCROOT.'client_helpers/data_entry_helper.php');
?>
<p>This page allows you to specify the details of a licence that can be applied to records.</p>
<form class="cmxform" id="licence-edit" action="<?php echo url::site().'licence/save'; ?>" method="post">
  <?php echo $metadata; ?>
  <fieldset>
    <input type="hidden" name="licence:id" value="<?php echo $id ?>" />
    <legend>Licence details</legend>
    <?php
    echo data_entry_helper::text_input(array(
      'label' => 'Title',
      'fieldname' => 'licence:title',
      'default' => html::initial_value($values, 'licence:title'),
      'helpText' => 'The main label used for this licence.',
      'validation' => array('required')
    ));
    echo data_entry_helper::text_input(array(
      'label' => 'Code',
      'fieldname' => 'licence:code',
      'default' => html::initial_value($values, 'licence:code'),
      'helpText' => 'The abbreviation or code used for this licence.',
      'validation' => array('required')
    ));
    echo data_entry_helper::textarea(array(
      'label' => 'Description',
      'fieldname' => 'licence:description',
      'default' => html::initial_value($values, 'licence:description'),
      'helpText' => 'A description of this licence.'
    ));
    echo data_entry_helper::text_input(array(
      'label' => 'URL (readable)',
      'fieldname' => 'licence:url_readable',
      'default' => html::initial_value($values, 'licence:url_readable'),
      'helpText' => 'Link to the online licence page in plain rather than legal language if available.',
      'validation' => array('url'),
      'class' => 'control-width-6'
    ));
    echo data_entry_helper::text_input(array(
      'label' => 'URL (legal)',
      'fieldname' => 'licence:url_legal',
      'default' => html::initial_value($values, 'licence:url_legal'),
      'helpText' => 'Link to the online licence page in legal rather than plain language if available.',
      'validation' => array('url'),
      'class' => 'control-width-6'
    ));
    ?>
  </fieldset>
  <?php
  echo html::form_buttons($id!=null, false, false);
  data_entry_helper::$dumped_resources[] = 'jquery';
  data_entry_helper::$dumped_resources[] = 'jquery_ui';
  data_entry_helper::$dumped_resources[] = 'fancybox';
  data_entry_helper::enable_validation('licence-edit');
  data_entry_helper::link_default_stylesheet();
  echo data_entry_helper::dump_javascript();
  ?>
</form>