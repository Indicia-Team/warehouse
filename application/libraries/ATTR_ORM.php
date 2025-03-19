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
 * @author Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link https://github.com/indicia-team/warehouse
 */
abstract class ATTR_ORM extends Valid_ORM {

  public $search_field = 'caption';

  /**
   * Defines if the available attrs for a submission are filtered by survey_id.
   *
   * @var bool
   */
  protected $hasSurveyRestriction = TRUE;

  /**
   * Validate data about to be submitted.
   *
   * @param Validation $array
   *   Form data to validate.
   * @param bool $save
   *   True if save should happen when validation passes.
   */
  public function validate(Validation $array, $save = FALSE) {
    // Uses PHP trim() to remove whitespace from beginning and end of all
    // fields before validation.
    $array->pre_filter('trim');
    // Merge unvalidated fields, in case the subclass has set any.
    if (!isset($this->unvalidatedFields)) {
      $this->unvalidatedFields = array();
    }
    $this->unvalidatedFields = array_merge(
      $this->unvalidatedFields,
      array(
        'validation_rules',
        'public',
        'multi_value',
        'deleted',
        'description',
        'caption_i18n',
        'description_i18n',
        'term_name',
        'term_identifier',
        'allow_ranges',
        'unit',
        'image_path',
      )
    );
    $array->add_rules('caption', 'required', 'length[1,50]');
    $array->add_rules('data_type', 'required');
    $array->add_rules('source_id', 'integer');
    $array->add_rules('reporting_category_id', 'integer');
    if (array_key_exists('data_type', $array->as_array()) && $array['data_type'] == 'L') {
      if (empty($array['termlist_id'])) {
        $array->add_rules('termlist_id', 'required');
      }
      else {
        array_push($this->unvalidatedFields, 'termlist_id');
      }
    }
    $array->add_rules('system_function', 'length[1,30]');
    if (!empty($array->multi_value) && !empty($array->allow_ranges) &&
        $array->multi_value === '1' && $array->allow_ranges === '1') {
      $array->add_error("$this->object_name:allow_ranges", 'notmultiple');
    }
    $parent_valid = parent::validate($array, $save);
    // Clean up cached required fields and attribute lists in case validation
    // rules have changed.
    $cache = Cache::instance();
    $cache->delete_tag('required-fields');
    $cache->delete_tag('attribute-lists');
    if ($save && $parent_valid) {
      // Clear the cache used for attribute datatype and validation rules since
      // the attribute has changed.
      $cache = new Cache();
      // Type is the object name with _attribute stripped from the end.
      $type = substr($this->object_name, 0, strlen($this->object_name) - 10);
      $cache->delete("attrInfo_{$type}_$this->id");
    }

    return $save && $parent_valid;
  }

  /**
   * Retrieve the submission structure.
   *
   * As we share a generic form, the submission structure is generic to all
   * custom attributes.
   */
  public function get_submission_structure() {
    return array(
      'model' => $this->object_name,
      'metaFields' => array(
        'disabled_input',
        'quick_termlist_create',
        'quick_termlist_terms',
      ),
    );
  }

  /**
   * Validate the submission then save it.
   *
   * If saving a re-used attribute, then don't bother posting the main record
   * data as it can't be changed. The postSubmit can still occur though to link
   * it to websites and surveys.
   *
   * @return int
   *   Id of the attribute.
   */
  protected function validateAndSubmit() {
    if (isset($this->submission['metaFields']) && isset($this->submission['metaFields']['disabled_input']) &&
        $this->submission['metaFields']['disabled_input']['value'] === 'YES') {
      $this->find($this->submission['fields']['id']['value']);
      return $this->id;
    }
    else {
      return parent::validateAndSubmit();
    }
  }

  /**
   * Finds the websites posted by the edit form.
   *
   * Uses the Post data to find all the websites that are going to be linked to
   * an attribute being saved.
   *
   * @return array
   *   Array of websites to link to attribute.
   */
  private static function getWebsitesInPost() {
    $matches = preg_grep('/^website_\d+/', array_keys($_POST));
    $websiteIds = array();
    foreach ($matches as $match) {
      preg_match('/^website_(?P<id>\d+)/', $match, $parts);
      $websiteIds[$parts['id']] = '';
    }
    return array_keys($websiteIds);
  }

  /**
   * Code to run pre-submission.
   *
   * A new attribute submission can contain metaField information to declare a
   * list of terms which will be inserted into a new termlist and linked to the
   * attribute.
   *
   * @throws \exception
   */
  protected function preSubmit() {
    $s = $this->submission;
    if (isset($s['metaFields']) &&
        isset($s['metaFields']['quick_termlist_create']) &&
        $s['metaFields']['quick_termlist_create']['value'] === '1' &&
        (empty($s['fields']['termlist_id']) || empty($s['fields']['termlist_id']['value']))) {
      $terms = data_entry_helper::explode_lines($s['metaFields']['quick_termlist_terms']['value']);
      $termlist = ORM::factory('termlist');
      $websiteIds = $this->getWebsitesInPost();
      $termlist->set_submission_data(array(
        'title' => $s['fields']['caption']['value'],
        'description' => 'Termlist created for attribute ' . $s['fields']['caption']['value'],
        'website_id' => count($websiteIds) == 1 ? $websiteIds[0] : NULL,
        'deleted' => 'f',
      ));
      if (!$termlist->submit()) {
        throw new exception('Failed to create attribute termlist');
      }
      foreach ($terms as $idx => $term) {
        if (!empty(trim($term))) {
          $termlists_term = ORM::factory('termlists_term');
          $termlists_term->set_submission_data(array(
            'term:term' => $term,
            'term:fk_language:iso' => kohana::config('indicia.default_lang'),
            'sort_order' => $idx + 1,
            'termlist_id' => $termlist->id,
            'preferred' => 't',
          ));
          if (!$termlists_term->submit()) {
            throw new exception('Failed to create attribute termlist term');
          };
        }
      }
      $this->submission['fields']['termlist_id'] = array('value' => $termlist->id);
    }
  }

  /**
   * Code to run after submission from warehouse UI.
   *
   * After saving, ensures that the join records linking the attribute to a
   * website & survey combination are created or deleted.
   *
   * @param bool $isInsert
   *   True if this is a new inserted record, false for an update.
   *
   * @return bool
   *   Returns true to indicate success.
   */
  protected function postSubmit($isInsert) {
    global $remoteUserId;
    if (!isset($remoteUserId)) {
      // Only save for the websites we have access to.
      if (empty($_POST['restricted-to-websites'])) {
        $websites = ORM::factory('website')->find_all();
      }
      else {
        $websites = ORM::factory('website')->in('id', explode(',', $_POST['restricted-to-websites']))->find_all();
      }
      foreach ($websites as $website) {
        // First check for non survey specific checkbox.
        $this->setAttributeWebsiteRecord($this->id, $website->id, NULL, isset($_POST["website_$website->id"]));
        // Then if attributes on this model are restricted by survey, check the
        // survey checkboxes.
        if ($this->hasSurveyRestriction) {
          $surveys = ORM::factory('survey')->where('website_id', $website->id)->find_all();
          foreach ($surveys as $survey) {
            $this->setAttributeWebsiteRecord($this->id, $website->id, $survey->id, isset($_POST["website_{$website->id}_{$survey->id}"]));
          }
        }
      }
    }
    return TRUE;
  }

  /**
   * Joins an attribute to a website.
   *
   * Internal function to ensure that an attribute is linked to a
   * website/survey combination or alternatively is unlinked from the
   * combination. Checks the existing data and creates or deletes the join
   * record as and when necessary.
   *
   * @param int $attr_id
   *   Id of the attribute.
   * @param int $website_id
   *   ID of the website.
   * @param int $survey_id
   *   ID of the survey.
   * @param bool $checked
   *   True if there should be a link, false if not.
   */
  protected function setAttributeWebsiteRecord($attr_id, $website_id, $survey_id, $checked) {
    $filter = array(
      $this->object_name . '_id' => $attr_id,
      'website_id' => $website_id,
    );
    if ($this->hasSurveyRestriction) {
      $filter['restrict_to_survey_id'] = $survey_id;
    }
    $attributes_website = ORM::factory(inflector::plural($this->object_name) . '_website', $filter);
    if ($attributes_website->loaded) {
      // Existing record.
      if ($checked == TRUE and $attributes_website->deleted === 't') {
        $attributes_website->__set('deleted', 'f');
        $attributes_website->save();
      }
      elseif ($checked == FALSE and $attributes_website->deleted == 'f') {
        $attributes_website->__set('deleted', 't');
        $attributes_website->save();
      }
    }
    elseif ($checked == TRUE) {
      $fields = array(
        $this->object_name . '_id' => array('value' => $attr_id),
        'website_id' => array('value' => $website_id),
        'deleted' => array('value' => 'f'),
      );
      if ($this->hasSurveyRestriction) {
        $fields['restrict_to_survey_id'] = array('value' => $survey_id);
      }
      $save_array = array(
        'id' => $attributes_website->object_name,
        'fields' => $fields,
        'fkFields' => array(),
        'superModels' => array(),
      );
      $attributes_website->submission = $save_array;
      $attributes_website->submit();
    }
  }

  /**
   * Override set handler to store caption translations as JSON.
   */
  public function __set($key, $value) {
    if (($key === 'caption_i18n' || $key === 'description_i18n') && !empty($value)) {
      $list = explode("\n", $value);
      $obj = [];
      foreach ($list as $item) {
        $parts = explode('|', $item);
        if (count($parts) === 2) {
          $obj[trim($parts[1])] = trim($parts[0]);
        }
        else {
          $this->errors[$key] = 'Please use the format <em>text</em>|<em>lang</em> for each line.';
          throw new exception('Invalid format');
        }
      }
      $value = json_encode($obj);
    }
    parent::__set($key, $value);
  }

  /**
   * Retrieves the value of a column in the model. If caption_i18n, reformats for editing.
   *
   * @param string $column
   * @return string
   */
  public function __get($column) {
    $value = parent::__get($column);
    if (($column === 'caption_i18n' || $column === 'description_i18n') && $value !== NULL) {
      $obj = json_decode($value, TRUE);
      if (!empty($obj)) {
        $list = [];
        foreach ($obj as $lang => $term) {
          $list[] = "$term|$lang";
        }
        $value = implode("\n", $list);
      }
    }
    return $value;
  }

}
