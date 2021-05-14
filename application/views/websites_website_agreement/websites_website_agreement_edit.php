<?php

/**
 * @file
 * View template for the website -> website agreement edit form.
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
$id = html::initial_value($values, 'websites_website_agreement:id');
$readAuth = data_entry_helper::get_read_auth(0 - $_SESSION['auth_user']->id, kohana::config('indicia.private_key'));
if ($this->auth->logged_in('CoreAdmin')) {
  $adminCase = "$('#div-'+field).attr('enabled',true);
                $('#div-'+field).css('opacity',1);";
}
else {
  $adminCase = "$('#div-'+field+' input').attr('checked','');
                $('#div-'+field).attr('enabled',false);
                $('#div-'+field).css('opacity',0.5);";
}
// Add some JavaScript to the page which loads the currently selected agreement's details, then
// sets the various control's to checked, unchecked, enabled or disabled where the agreement
// specifies restrictions on what can be done. Once selected, you can't change the agreement
// during edit of an existing websites_website_agreement.
data_entry_helper::$javascript .= "
  $('#agreement-select').change(function(evt) {
    jQuery.ajax({
      type: 'GET',
      url: '" . url::site() . "services/data/website_agreement/'+evt.target.value+'?mode=json&view=detail'+
        '&auth_token=$readAuth[auth_token]&nonce=$readAuth[nonce]&callback=?',
      data: {},
      success: function(data) {
        $.each(data[0], function(field, value) {
          $('#div-'+field+' .helpText .locked-info').remove();
          if (field.substr(0, 7)==='provide' || field.substr(0,7)==='receive') {
            switch (value) {
              case 'D':
                $('#div-'+field+' input').removeAttr('checked');
                $('#div-'+field).hide();
                break;
              case 'O':
                $('#div-'+field+' input').removeAttr('disabled');
                $('#div-'+field).show();
                break;
              case 'A':
                $adminCase
                break;
              case 'R':
                $('#div-'+field+' input').attr('checked', 'checked');
                $('#div-'+field+' input').attr('disabled', 'disabled');
                $('#div-'+field).show();
                $('#div-'+field+' .helpText').append(
                  '<span class=\"locked-info\"><br/>NOTE, by participating in this agreement the website must accept this option.</span>');
                break;
            }
          }
        });
      },
      dataType: 'json'
    });
  });
  $('#agreement-select').change();
";

?>
<form action="<?php echo url::site() . 'websites_website_agreement/save' ?>" method="post" id="entry_form">
  <fieldset>
    <legend>Website Agreement Participation Details<?php echo $metadata; ?></legend>
    <input type="hidden" name="websites_website_agreement:id" value="<?php echo html::initial_value($values, 'websites_website_agreement:id'); ?>" />
    <input type="hidden" name="websites_website_agreement:website_id" value="<?php echo html::initial_value($values, 'websites_website_agreement:website_id'); ?>" />

    <?php
    // @todo: filter the select box to public agreements, or those created by the current user, unless core admin.
    if ($this->uri->method(FALSE) === 'edit') {
      echo '<div style="display: none">';
    }
    echo data_entry_helper::select([
      'id' => 'agreement-select',
      'label' => 'Agreement to participate in',
      'helpText' => 'Select the agreement that this website should belong to',
      'fieldname' => 'websites_website_agreement:website_agreement_id',
      'default' => html::initial_value($values, 'websites_website_agreement:website_agreement_id'),
      'labelClass' => 'control-width-4',
      'table' => 'website_agreement',
      'captionField' => 'title',
      'valueField' => 'id',
      'extraParams' => $readAuth,
      'blankText' => '<please select>',
      'class' => 'required',
      'caching' => FALSE,
    ]);
    if ($this->uri->method(FALSE) === 'edit') {
      echo '</div>';
    }
    ?>
    <p>This sharing agreement has the following options:</p>
    <div id="div-provide_for_reporting"><?php
    echo data_entry_helper::checkbox(array(
      'label' => 'Provides data for reporting',
      'helpText' => 'Check this box if the website provides its data to other agreement participants for reporting.',
      'fieldname' => 'websites_website_agreement:provide_for_reporting',
      'default' => html::initial_value($values, 'websites_website_agreement:provide_for_reporting'),
      'labelClass' => 'control-width-4',
    ));
    ?> </div>
    <div id="div-receive_for_reporting"><?php
    echo data_entry_helper::checkbox(array(
      'label' => 'Receives data for reporting',
      'helpText' => 'Check this box if the website receives data from other agreement participants for reporting.',
      'fieldname' => 'websites_website_agreement:receive_for_reporting',
      'default' => html::initial_value($values, 'websites_website_agreement:receive_for_reporting'),
      'labelClass' => 'control-width-4',
    ));
    ?> </div>
    <div id="div-provide_for_peer_review"><?php
    echo data_entry_helper::checkbox(array(
      'label' => 'Provides data for peer review',
      'helpText' => 'Check this box if the website provides its data to other agreement participants for peer review, e.g. browsing and commenting on records.',
      'fieldname' => 'websites_website_agreement:provide_for_peer_review',
      'default' => html::initial_value($values, 'websites_website_agreement:provide_for_peer_review'),
      'labelClass' => 'control-width-4',
    ));
    ?> </div>
    <div id="div-receive_for_peer_review"><?php
    echo data_entry_helper::checkbox(array(
      'label' => 'Receives data for peer review',
      'helpText' => 'Check this box if the website receives data from other agreement participants for reporting, e.g. browsing and commenting on records.',
      'fieldname' => 'websites_website_agreement:receive_for_peer_review',
      'default' => html::initial_value($values, 'websites_website_agreement:receive_for_peer_review'),
      'labelClass' => 'control-width-4',
    ));
    ?> </div>
    <div id="div-provide_for_verification"><?php
    echo data_entry_helper::checkbox(array(
      'label' => 'Provides data for verification',
      'helpText' => 'Check this box if the website provides its data to other agreement participants for verification.',
      'fieldname' => 'websites_website_agreement:provide_for_verification',
      'default' => html::initial_value($values, 'websites_website_agreement:provide_for_verification'),
      'labelClass' => 'control-width-4',
    ));
    ?> </div>
    <div id="div-receive_for_verification"><?php
    echo data_entry_helper::checkbox(array(
      'label' => 'Receives data for verification',
      'helpText' => 'Check this box if the website receives data from other agreement participants for verification.',
      'fieldname' => 'websites_website_agreement:receive_for_verification',
      'default' => html::initial_value($values, 'websites_website_agreement:receive_for_verification'),
      'labelClass' => 'control-width-4',
    ));
    ?> </div>
    <div id="div-provide_for_data_flow"><?php
    echo data_entry_helper::checkbox(array(
      'label' => 'Provides data for data flow',
      'helpText' => 'Check this box if the website provides its data to other agreement participants for data flow, e.g. for passing data onto national information portals.',
      'fieldname' => 'websites_website_agreement:provide_for_data_flow',
      'default' => html::initial_value($values, 'websites_website_agreement:provide_for_data_flow'),
      'labelClass' => 'control-width-4',
    ));
    ?> </div>
    <div id="div-receive_for_data_flow"><?php
    echo data_entry_helper::checkbox(array(
      'label' => 'Receives data for data flow',
      'helpText' => 'Check this box if the website receives data from other agreement participants for data flow, e.g. for passing data onto national information portals.',
      'fieldname' => 'websites_website_agreement:receive_for_data_flow',
      'default' => html::initial_value($values, 'websites_website_agreement:receive_for_data_flow'),
      'labelClass' => 'control-width-4',
    ));
    ?> </div>
    <div id="div-provide_for_moderation"><?php
    echo data_entry_helper::checkbox(array(
      'label' => 'Provides data for moderation',
      'helpText' => 'Check this box if the website provides its data to other agreement participants for moderation, e.g. to check images before publishing.',
      'fieldname' => 'websites_website_agreement:provide_for_moderation',
      'default' => html::initial_value($values, 'websites_website_agreement:provide_for_moderation'),
      'labelClass' => 'control-width-4',
    ));
    ?> </div>
    <div id="div-receive_for_moderation"><?php
    echo data_entry_helper::checkbox(array(
      'label' => 'Receives data for moderation',
      'helpText' => 'Check this box if the website receives data from other agreement participants for moderation, e.g. to check images before publishing.',
      'fieldname' => 'websites_website_agreement:receive_for_moderation',
      'default' => html::initial_value($values, 'websites_website_agreement:receive_for_moderation'),
      'labelClass' => 'control-width-4',
    ));
    ?>
    </div>
    <div id="div-provide_for_editing"><?php
    echo data_entry_helper::checkbox(array(
      'label' => 'Provides data for editing',
      'helpText' => 'Check this box if the website provides its data to other agreement participants for editing.',
      'fieldname' => 'websites_website_agreement:provide_for_editing',
      'default' => html::initial_value($values, 'websites_website_agreement:provide_for_editing'),
      'labelClass' => 'control-width-4',
    ));
    ?> </div><div id="div-receive_for_editing"><?php
    echo data_entry_helper::checkbox(array(
      'label' => 'Receives data for editing',
      'helpText' => 'Check this box if the website receives data from other agreement participants for editing.',
      'fieldname' => 'websites_website_agreement:receive_for_editing',
      'default' => html::initial_value($values, 'websites_website_agreement:receive_for_editing'),
      'labelClass' => 'control-width-4',
    ));
    ?>
    </div>
  </fieldset>
  <?php
  echo html::form_buttons($id !== NULL, FALSE, FALSE);
  data_entry_helper::enable_validation('entry_form');
  echo data_entry_helper::dump_javascript();
  ?>
</form>
