<?php

/**
 * @file
 * View template file for the website agremeent edit form.
 *
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
 * @author Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link https://github.com/indicia-team/warehouse
 */

warehouse::loadHelpers(['data_entry_helper']);
$id = html::initial_value($values, 'website_agreement:id');
?>
<form action="<?php echo url::site() . 'website_agreement/save' ?>" method="post" id="entry_form">
  <fieldset>
    <legend>Website Agreement Details<?php echo $metadata; ?></legend>
    <input type="hidden" name="website_agreement:id" value="<?php echo html::initial_value($values, 'website_agreement:id'); ?>" />
    <?php
    echo data_entry_helper::text_input([
      'label' => 'Agreement title',
      'helpText' => 'Enter the title of the agreement',
      'fieldname' => 'website_agreement:title',
      'default' => html::initial_value($values, 'website_agreement:title'),
    ]);
    echo data_entry_helper::textarea([
      'label' => 'Description',
      'helpText' => 'Enter an optional description of the agreement',
      'fieldname' => 'website_agreement:description',
      'default' => html::initial_value($values, 'website_agreement:description'),
    ]);
    echo data_entry_helper::select([
      'label' => 'Providing data for reporting',
      'helpText' => 'Select the requirements of participants with respect to providing data for reporting. When data ' .
        'are provided by a website, other websites participating in the same agreement will be able to report on ' .
        'these data if they select to receive data for reporting.',
      'fieldname' => 'website_agreement:provide_for_reporting',
      'default' => html::initial_value($values, 'website_agreement:provide_for_reporting'),
      'lookupValues' => [
        'D' => 'Not allowed',
        'O' => 'Optional',
        'A' => 'Optional, but must be setup by an administrator',
        'R' => 'Required',
      ],
    ]);
    echo data_entry_helper::select([
      'label' => 'Receive data for reporting',
      'helpText' => 'Select the requirements of participants with respect to receiving data for reporting. When a ' .
        'website selects to receive data for reporting, reports run on the website can include data from other ' .
        'websites participating in the same agreement if they elect to provide data for reporting.',
      'fieldname' => 'website_agreement:receive_for_reporting',
      'default' => html::initial_value($values, 'website_agreement:receive_for_reporting'),
      'lookupValues' => [
        'D' => 'Not allowed',
        'O' => 'Optional',
        'A' => 'Optional, but must be setup by an administrator',
        'R' => 'Required',
      ],
    ]);
    echo data_entry_helper::select([
      'label' => 'Providing data for peer review',
      'helpText' => 'Select the requirements of participants with respect to providing data for peer review. When ' .
        'data are provided  by a website, other websites participating in the same agreement will be able to review ' .
        'these data if they select to receive data for peer review.',
      'fieldname' => 'website_agreement:provide_for_peer_review',
      'default' => html::initial_value($values, 'website_agreement:provide_for_peer_review'),
      'lookupValues' => [
        'D' => 'Not allowed',
        'O' => 'Optional',
        'A' => 'Optional, but must be setup by an administrator',
        'R' => 'Required',
      ],
    ]);
    echo data_entry_helper::select([
      'label' => 'Receive data for peer review',
      'helpText' => 'Select the requirements of participants with respect to receiving data for peer review. When a ' .
        'website selects  to receive data for peer review, review processes such as record reviewing and commenting ' .
        'run on the website can include data from other websites participating in the same agreement if they elect ' .
        'to provide data for peer review.',
      'fieldname' => 'website_agreement:receive_for_peer_review',
      'default' => html::initial_value($values, 'website_agreement:receive_for_peer_review'),
      'lookupValues' => [
        'D' => 'Not allowed',
        'O' => 'Optional',
        'A' => 'Optional, but must be setup by an administrator',
        'R' => 'Required',
      ],
    ]);
    echo data_entry_helper::select([
      'label' => 'Providing data for verification',
      'helpText' => 'Select the requirements of participants with respect to providing data for verification. When ' .
        'data are provided by a website, other websites participating in the same agreement will be able to verify ' .
        'these data if they select to receive data for verification.',
      'fieldname' => 'website_agreement:provide_for_verification',
      'default' => html::initial_value($values, 'website_agreement:provide_for_verification'),
      'lookupValues' => [
        'D' => 'Not allowed',
        'O' => 'Optional',
        'A' => 'Optional, but must be setup by an administrator',
        'R' => 'Required',
      ],
    ]);
    echo data_entry_helper::select([
      'label' => 'Receive data for verification',
      'helpText' => 'Select the requirements of participants with respect to receiving data for verification. When a ' .
        'website selects to receive data for verification, verification systems run on the website can include data ' .
        'from other websites participating in the same agreement if they elect to provide data for verification.',
      'fieldname' => 'website_agreement:receive_for_verification',
      'default' => html::initial_value($values, 'website_agreement:receive_for_verification'),
      'lookupValues' => [
        'D' => 'Not allowed',
        'O' => 'Optional',
        'A' => 'Optional, but must be setup by an administrator',
        'R' => 'Required',
      ],
    ]);
    echo data_entry_helper::select([
      'label' => 'Providing data for data flow',
      'helpText' => 'Select the requirements of participants with respect to providing data for data flow. When ' .
        'data are provided by a website, other websites participating in the same agreement will be able to pass this ' .
        'data on (for example to a national informaiton portal) if they select to receive data for data flow.',
      'fieldname' => 'website_agreement:provide_for_data_flow',
      'default' => html::initial_value($values, 'website_agreement:provide_for_data_flow'),
      'lookupValues' => [
        'D' => 'Not allowed',
        'O' => 'Optional',
        'A' => 'Optional, but must be setup by an administrator',
        'R' => 'Required',
      ],
    ]);
    echo data_entry_helper::select([
      'label' => 'Receive data for data flow',
      'helpText' => 'Select the requirements of participants with respect to receiving data for data flow. When a ' .
        'website selects to receive data for data flow, they can pass on data from other websites for data flow ' .
        'purposes (e.g. to a national information portal) from other websites participating in the same agreement if ' .
        'they elect to provide data for data flow.',
      'fieldname' => 'website_agreement:receive_for_data_flow',
      'default' => html::initial_value($values, 'website_agreement:receive_for_data_flow'),
      'lookupValues' => [
        'D' => 'Not allowed',
        'O' => 'Optional',
        'A' => 'Optional, but must be setup by an administrator',
        'R' => 'Required',
      ],
    ]);
    echo data_entry_helper::select([
      'label' => 'Providing data for moderation',
      'helpText' => 'Select the requirements of participants with respect to providing data for moderation. When ' .
        'data are provided by a website, other websites participating in the same agreement will be able to moderate ' .
        'these data, e.g. to check images before publication, if they select to receive data for moderation.',
      'fieldname' => 'website_agreement:provide_for_moderation',
      'default' => html::initial_value($values, 'website_agreement:provide_for_moderation'),
      'lookupValues' => [
        'D' => 'Not allowed',
        'O' => 'Optional',
        'A' => 'Optional, but must be setup by an administrator',
        'R' => 'Required',
      ],
    ]);
    echo data_entry_helper::select([
      'label' => 'Receive data for moderation',
      'helpText' => 'Select the requirements of participants with respect to receiving data for moderation. When a ' .
        'website selects to receive data for modeartion, they can perform moderation tasks such as checking images ' .
        'before publication from other websites participating in the same agreement if they elect to provide data ' .
        'for moderation.',
      'fieldname' => 'website_agreement:receive_for_moderation',
      'default' => html::initial_value($values, 'website_agreement:receive_for_moderation'),
      'lookupValues' => [
        'D' => 'Not allowed',
        'O' => 'Optional',
        'A' => 'Optional, but must be setup by an administrator',
        'R' => 'Required',
      ],
    ]);
    echo data_entry_helper::select([
      'label' => 'Providing data for editing',
      'helpText' => 'Select the requirements of participants with respect to providing data for editing. When data ' .
        'are provided by a website, other websites participating in the same agreement will be able to edit this ' .
        'data, e.g. to check images before publication, if they select to receive data for editing.',
      'fieldname' => 'website_agreement:provide_for_editing',
      'default' => html::initial_value($values, 'website_agreement:provide_for_editing'),
      'lookupValues' => [
        'D' => 'Not allowed',
        'O' => 'Optional',
        'A' => 'Optional, but must be setup by an administrator',
        'R' => 'Required',
      ],
    ]);
    echo data_entry_helper::select([
      'label' => 'Receive data for editing',
      'helpText' => 'Select the requirements of participants with respect to receiving data for editing. When a ' .
        'website selects to receive data for modeartion, they can perform editing tasks such as checking images ' .
        'before publication from other websites participating in the same agreement if they elect to provide data ' .
        'for editing.',
      'fieldname' => 'website_agreement:receive_for_editing',
      'default' => html::initial_value($values, 'website_agreement:receive_for_editing'),
      'lookupValues' => [
        'D' => 'Not allowed',
        'O' => 'Optional',
        'A' => 'Optional, but must be setup by an administrator',
        'R' => 'Required',
      ],
    ]);

    ?>
  </fieldset>
  <?php
  echo html::form_buttons($id != NULL, FALSE, FALSE);
  data_entry_helper::enable_validation('entry_form');
  echo data_entry_helper::dump_javascript();
  ?>
</form>
