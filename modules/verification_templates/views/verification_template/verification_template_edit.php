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
 * @package    Modules
 * @subpackage Verification_templates
 * @author     Indicia Team
 * @license    http://www.gnu.org/licenses/gpl.html GPL
 * @link       https://github.com/Indicia-Team/
 */

require_once(DOCROOT.'client_helpers/data_entry_helper.php');
if (isset($_POST))
  data_entry_helper::dump_errors(array('errors'=>$this->model->getAllErrors()));
?>
<form class="iform" action="<?php echo url::site(); ?>verification_template/save" method="post" id="entry-form">
  <fieldset>
    <legend>Verification Template definition details</legend>
<?php
data_entry_helper::link_default_stylesheet();
data_entry_helper::enable_validation('entry-form');
echo data_entry_helper::hidden_text(array(
    'fieldname' => 'verification_template:id',
    'default' => html::initial_value($values, 'verification_template:id'),
));
echo data_entry_helper::select(array(
    'label' => 'Website',
    'fieldname' => 'verification_template:website_id',
    'default' => html::initial_value($values, 'verification_template:website_id'),
    'lookupValues' => $other_data['websites'],
    'helpText' => 'The verification template must belong to a website',
    'validation' => array('required'),
));
echo data_entry_helper::checkbox(array(
    'label' => 'Restrict to creating website',
    'fieldname' => 'verification_template:restrict_to_website_id',
    'default' => html::initial_value($values, 'verification_template:restrict_to_website_id'),
));
echo data_entry_helper::textarea(array(
    'label' => 'Restrict to specified external keys',
    'fieldname' => 'verification_template:restrict_to_external_keys_list',
    'default' => $other_data['restrict_to_external_keys_list'],
));
echo data_entry_helper::textarea(array(
    'label' => 'Restrict to specified family external keys',
    'fieldname' => 'verification_template:restrict_to_family_external_keys_list',
    'default' => $other_data['restrict_to_family_external_keys_list'],
));
echo data_entry_helper::textarea(array(
    'label' => 'Template',
    'fieldname' => 'verification_template:template',
    'default' => html::initial_value($values, 'verification_template:template'),
    'validation' => array('required'),
));

echo $metadata;
echo html::form_buttons(html::initial_value($values, 'verification_template:id') !== null, false, false);

data_entry_helper::$dumped_resources[] = 'jquery';
data_entry_helper::$dumped_resources[] = 'jquery_ui';
data_entry_helper::$dumped_resources[] = 'fancybox';
echo data_entry_helper::dump_javascript();
?>
  </fieldset>
</form>