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
 * @package	Groups and individuals module
 * @subpackage Views
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL
 * @link 	http://code.google.com/p/indicia/
 */

require_once(DOCROOT.'client_helpers/data_entry_helper.php');
if (isset($_POST))
  data_entry_helper::dump_errors(array('errors'=>$this->model->getAllErrors()));
?>
<?php $tabs = false;
if (!empty($values['identifier:id']) 
  && is_numeric($values['identifier:id'])
  && $values['identifier:id'] > 0) : // edit so show tabs 
  $tabs = true;
?>
<div id="tabs">
<?php
data_entry_helper::enable_tabs(array('divId'=>'tabs')); 
echo data_entry_helper::tab_header(array('tabs'=>array(
  '#details'=>'Identifier',
  '#observations'=>'Observations',
)));
?>
<div id="details">
<?php  endif; ?>
<form class="iform" action="<?php echo url::site(); ?>identifier/save" method="post">
<?php ///echo '$values: '.print_r($values, true).'<br />'; ?>
<?php //echo '$other_data: '.print_r($other_data, true); ?>
<?php //echo 'initial taxa: '.print_r(html::initial_value($values, 'joinsTo:taxa_taxon_list:id'), true); ?>
<?php  
echo $metadata; 
if (isset($values['identifier:id'])) : ?>
  <input type="hidden" name="identifier:id" value="<?php echo html::initial_value($values, 'identifier:id'); ?>" />
<?php endif; ?>
<input type="hidden" name="website_id" value="<?php echo html::initial_value($values, 'website_id'); ?>" />
<fieldset>
<legend>Known subject details</legend>
<?php 
$readAuth = data_entry_helper::get_read_auth(0-$_SESSION['auth_user']->id, kohana::config('indicia.private_key'));
echo data_entry_helper::select(array(
  'label' => 'Issue Authority',
  'fieldname' => 'identifier:issue_authority_id',
  'default'=>html::initial_value($values, 'identifier:issue_authority_id'),
  'lookupValues' => $other_data['issue_authority_terms'],
  'blankText' => '<Please select>',
  'extraParams' => $readAuth,
));
echo data_entry_helper::select(array(
  'label' => 'Issue Scheme',
  'fieldname' => 'identifier:issue_scheme_id',
  'default'=>html::initial_value($values, 'identifier:issue_scheme_id'),
  'lookupValues' => $other_data['issue_scheme_terms'],
  'blankText' => '<Please select>',
  'extraParams' => $readAuth,
));
echo data_entry_helper::date_picker(array(
  'label' => 'Issue Date',
  'fieldname' => 'identifier:issue_date',
  'default' => html::initial_value($values, 'identifier:issue_date'),
));
echo data_entry_helper::date_picker(array(
  'label' => 'First Use Date',
  'fieldname' => 'identifier:first_use_date',
  'default' => html::initial_value($values, 'identifier:first_use_date'),
));
echo data_entry_helper::date_picker(array(
  'label' => 'Last Observed Date',
  'fieldname' => 'identifier:last_observed_date',
  'default' => html::initial_value($values, 'identifier:last_observed_date'),
));
echo data_entry_helper::date_picker(array(
  'label' => 'Final Date',
  'fieldname' => 'identifier:final_date',
  'default' => html::initial_value($values, 'identifier:final_date'),
));
echo data_entry_helper::select(array(
  'label' => 'Identifier Type',
  'fieldname' => 'identifier:identifier_type_id',
  'default'=>html::initial_value($values, 'identifier:identifier_type_id'),
  'lookupValues' => $other_data['identifier_type_terms'],
  'blankText' => '<Please select>',
  'extraParams' => $readAuth,
));
echo data_entry_helper::select(array(
  'label' => 'Website',
  'fieldname' => 'identifier:website_id',
  'table' => 'website',
  'captionField' => 'title',
  'valueField' => 'id',
  'default'=>html::initial_value($values, 'identifier:website_id'),
  'extraParams' => $readAuth,
));
echo data_entry_helper::text_input(array(
  'label'=>'Coded Value',
  'fieldname'=>'identifier:coded_value',
  'default'=>html::initial_value($values, 'identifier:coded_value')
));
echo data_entry_helper::textarea(array(
  'label'=>'Summary',
  'fieldname'=>'identifier:summary',
  'class'=>'control-width-6',
  'default'=>html::initial_value($values, 'identifier:summary')
));
?>
</fieldset>
<?php if (array_key_exists('attributes', $values) && count($values['attributes'])>0) : ?>
<fieldset>
<legend>Custom Attributes</legend>
<ol>
<?php
 foreach ($values['attributes'] as $attr) :
	$name = 'idnAttr:'.$attr['identifier_attribute_id'];
  // if this is an existing attribute, tag it with the attribute value record id so we can re-save it
  if ($attr['id']) $name .= ':'.$attr['id'];
  switch ($attr['data_type']) :
    case 'D':
    case 'V':
      echo data_entry_helper::date_picker(array(
        'label' => $attr['caption'],
        'fieldname' => $name,
        'default' => $attr['value']
      ));
      break;
    case 'L':
      echo data_entry_helper::select(array(
        'label' => $attr['caption'],
        'fieldname' => $name,
        'default' => $attr['raw_value'],
        'lookupValues' => $values['terms_'.$attr['termlist_id']],
        'blankText' => '<Please select>'
      ));
      break;
    case 'B':
      echo data_entry_helper::checkbox(array(
        'label' => $attr['caption'],
        'fieldname' => $name,
        'default' => $attr['value']
      ));
      break;
    default:
      echo data_entry_helper::text_input(array(
        'label' => $attr['caption'],
        'fieldname' => $name,
        'default' => $attr['value']
      ));
  endswitch;
	
endforeach;
 ?>
</ol>
</fieldset>
<?php 
endif;
echo html::form_buttons(html::initial_value($values, 'identifier:id')!=null, false, false);
data_entry_helper::$dumped_resources[] = 'jquery';
data_entry_helper::$dumped_resources[] = 'json';
data_entry_helper::$dumped_resources[] = 'jquery_ui';
data_entry_helper::$dumped_resources[] = 'fancybox';
data_entry_helper::link_default_stylesheet();
echo data_entry_helper::dump_javascript();
?>
</form>
<?php if ($tabs) : ?>
</div>
<div id="observations">
TODO - will link to grid view of identifier observations filtered on this identifier
</div>
</div>
<?php endif; ?>
