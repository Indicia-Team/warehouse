<fieldset>
  <legend><?php echo $other_data['name']; ?> attribute website/survey allocation</legend>
  <?php
  if (!is_null($this->auth_filter) && $this->auth_filter['field'] === 'website_id') {
    // Output a hidden input to list the websites we are allowed update against.
    echo '<input type="hidden" name="restricted-to-websites" value="' . implode(',', $this->auth_filter['values']) . '"/>';
  }
  $baseEntityName = strtolower($other_data['name']);
  $surveyCheckboxList = [];
  $siteUrl = url::site();
  // Loop through all the website and survey combinations possible.
  foreach ($other_data['websiteSurveyLinks'] as $idx => $linkOption) {
    if (!isset($linkOption->selected)) {
      $linkOption->selected = 'f';
    }
    if (!isset($linkOption->selected_all_surveys)) {
      $linkOption->selected_all_surveys = 'f';
    }
    // If this combination is for a survey (i.e. not a survey-less website),
    // build the survey checkbox for the form.
    if (!empty($linkOption->survey_id)) {
      $fieldname = "website_{$linkOption->website_id}_{$linkOption->survey_id}";
      // If a join to the survey exists for this attribute, create a link to
      // the join edit page.
      if ($linkOption->selected === 't') {
        $surveySettingsEditLink = <<<HTML
<a target="_blank" class="btn btn-primary btn-xs" href="{$siteUrl}attribute_by_survey/edit/$linkOption->website_join_id?type=$baseEntityName">
  edit survey specific settings in new tab
</a>
HTML;
      }
      else {
        $surveySettingsEditLink = '';
      }
      // Check the checkbox either if loading existing data, or a posted form
      // is being reloaded after a validation failure where the box was
      // checked.
      if (empty($_POST)) {
        $checked = $linkOption->selected === 't' ? ' checked="checked"' : '';
      }
      else {
        $checked = !empty($_POST[$fieldname]) ? ' checked="checked"' : '';
      }
      // Create a checkbox for this survey.
      $surveyCheckboxList[] = <<<HTML
<div class="checkbox">
  <label>
    <input type="checkbox" name="$fieldname" value="1"$checked>
    $linkOption->survey_title
  </label>
  $surveySettingsEditLink
</div>

HTML;
    }
    // If at the end of the list, or the next item in the list is for a
    // different website, we need to output the website's panel.
    if ($idx === count($other_data['websiteSurveyLinks']) - 1 ||
        $linkOption->website_id !== $other_data['websiteSurveyLinks'][$idx + 1]->website_id) {
      $surveyCheckboxes = implode("\n", $surveyCheckboxList);
      $fieldname = "website_$linkOption->website_id";
      // Check the checkbox either if loading existing data, or a posted form
      // is being reloaded after a validation failure where the box was
      // checked.
      if (empty($_POST)) {
        $checked = $linkOption->selected_all_surveys === 't' ? ' checked="checked"' : '';
      }
      else {
        $checked = !empty($_POST[$fieldname]) ? ' checked="checked"' : '';
      }
      // Output the panel for the website, including the list of survey
      // checkboxes and one for all surveys.
      echo <<<HTML
<div class="panel panel-info">
  <div class="panel-heading">$linkOption->website_title</div>
  <div class="panel-body">
    <div class="checkbox">
      <label>
        <input type="checkbox" id="website_" name="$fieldname" value="1"$checked>
        $linkOption->website_title: all surveys
      </label>
    </div>
    $surveyCheckboxes
  </div>
</div>

HTML;
      $surveyCheckboxList = [];
    }
  }
  ?>
</fieldset>
