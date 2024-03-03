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
 * @package Survey Structure Export
 * @subpackage Controllers
 * @author Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link https://github.com/indicia-team/warehouse/
 */

/**
 * Controller class for the survey structure export plugin module.
 */
class Survey_structure_export_Controller extends Indicia_Controller {

  /**
   * Holds a list of log messages describing the results of an import.
   *
   * @var array
   */
  private $log = [];

  /**
   * The user's ID.
   *
   * @var int
   */
  private $userId;

  /**
   * The ID of the website we are importing into.
   *
   * @var int
   */
  private $website_id;

  /**
   * The controller view.
   *
   * @var object
   */
  public $view;

  /**
   * @const SQL_FETCH_ALL_SURVEY_ATTRS Query definition which retrieves all
   * the survey attribute details for a survey ID in preparation for export.
   */
  const SQL_FETCH_ALL_SURVEY_ATTRS = "SELECT
    a.caption,
    (with table_of_caption_i18n as
      (
        select
        value || '|' || key as cap_pipe_lang
        from json_each_text(a.caption_i18n)
	    )
      select array_to_string(array_agg(cap_pipe_lang), '**')
      from table_of_caption_i18n
    ) as a_caption_i18n,
    a.unit,
    a.term_name,
    a.term_identifier,
    a.description,
    (with table_of_description_i18n as
      (
        select
        value || '|' || key as desc_pipe_lang
        from json_each_text(a.description_i18n)
	    )
      select array_to_string(array_agg(desc_pipe_lang), '**')
      from table_of_description_i18n
    ) as a_description_i18n,
    a.system_function,
    a.data_type,
    a.multi_value,
    a.allow_ranges,
    a.public,
    a.validation_rules,
    aw.validation_rules AS aw_validation_rules,
    aw.weight AS aw_weight,
    aw.control_type_id AS aw_control_type_id,
    aw.website_id AS aw_website_id,
    aw.default_text_value AS aw_default_text_value,
    aw.default_float_value AS aw_default_float_value,
    aw.default_int_value AS aw_default_int_value,
    aw.default_date_start_value AS aw_default_date_start_value,
    aw.default_date_end_value AS aw_default_date_end_value,
    aw.default_date_type_value AS aw_default_date_type_value,
    aw.default_upper_value AS aw_default_upper_value,
    t.termlist_title,
    (
      SELECT array_to_string(array_agg(
        CASE a.data_type
        WHEN 'T' THEN
          coalesce(av.text_value, '')
        WHEN 'F' THEN
          coalesce(av.float_value::varchar, '') || '|' ||
          coalesce(av.upper_value::varchar, '')
        WHEN 'L' THEN
          coalesce(t.term, '')
        WHEN 'I' THEN
          coalesce(av.int_value::varchar, '') || '|' ||
          coalesce(av.upper_value::varchar, '')
        WHEN 'V' THEN
          coalesce(av.date_start_value::varchar, '') || '|' ||
          coalesce(av.date_end_value::varchar, '') || '|' ||
          coalesce(av.date_type_value::varchar, '')
        END
      ), '**')
      FROM survey_attribute_values av
      LEFT JOIN cache_termlists_terms t
        ON t.termlist_id = a.termlist_id
        AND t.id = av.int_value
      WHERE av.survey_attribute_id = a.id
      AND av.survey_id = {survey_id}
    ) AS av_values,
    array_to_string(array_agg(
      (
        t.term || '|' ||
        t.language_iso || '|' ||
        coalesce(t.sort_order::varchar, '') || '|' ||
        coalesce(tp.term::varchar, '')
      )::varchar ORDER BY t.sort_order, t.term
    ), '**') AS terms
  FROM survey_attributes a
  JOIN survey_attributes_websites aw
    ON aw.survey_attribute_id = a.id
    AND aw.deleted = false
  LEFT JOIN cache_termlists_terms t ON t.termlist_id = a.termlist_id
  LEFT JOIN cache_termlists_terms tp ON tp.id = t.parent_id
  WHERE a.deleted = false
  AND aw.website_id = {website_id}
  GROUP BY a.caption, a_caption_i18n, a.unit, a.term_name,
    a.term_identifier, a.description, a_description_i18n, a.system_function,
    a.data_type, a.multi_value, a.allow_ranges, a.public, a.validation_rules,
    aw.validation_rules, aw.weight, aw.control_type_id, aw.website_id,
    aw.default_text_value, aw.default_float_value, aw.default_int_value,
    aw.default_date_start_value, aw.default_date_end_value,
    aw.default_date_type_value, aw.default_upper_value, t.termlist_title,
    av_values
  ORDER BY aw.weight";

 /**
   * @const SQL_FETCH_ALL_SAMPLE_ATTRS Query definition which retrieves all
   * the sample attribute details for a survey ID in preparation for export.
   */
  const SQL_FETCH_ALL_SAMPLE_ATTRS = "SELECT
    a.caption,
    (with table_of_caption_i18n as
      (
        select
        value || '|' || key as cap_pipe_lang
        from json_each_text(a.caption_i18n)
	    )
      select array_to_string(array_agg(cap_pipe_lang), '**')
      from table_of_caption_i18n
    ) as a_caption_i18n,
    a.unit,
    a.term_name,
    a.term_identifier,
    a.description,
    (with table_of_description_i18n as
      (
        select
        value || '|' || key as desc_pipe_lang
        from json_each_text(a.description_i18n)
	    )
      select array_to_string(array_agg(desc_pipe_lang), '**')
      from table_of_description_i18n
    ) as a_description_i18n,
    a.system_function,
    a.data_type,
    a.multi_value,
    a.allow_ranges,
    a.public,
    a.validation_rules,
    a.applies_to_location,
    a.applies_to_recorder,
    sm.term AS aw_restrict_to_sample_method_id_term,
    aw.validation_rules AS aw_validation_rules,
    aw.weight AS aw_weight,
    aw.control_type_id AS aw_control_type_id,
    aw.website_id AS aw_website_id,
    aw.default_text_value AS aw_default_text_value,
    aw.default_float_value AS aw_default_float_value,
    aw.default_int_value AS aw_default_int_value,
    aw.default_date_start_value AS aw_default_date_start_value,
    aw.default_date_end_value AS aw_default_date_end_value,
    aw.default_date_type_value AS aw_default_date_type_value,
    fsb1.name AS fsb1_name,
    fsb1.weight AS fsb1_weight,
    fsb2.name AS fsb2_name,
    fsb2.weight AS fsb2_weight,
    t.termlist_title AS termlist_title,
    array_to_string(array_agg(
      (
        t.term || '|' ||
        t.language_iso || '|' ||
        coalesce(t.sort_order::varchar, '') || '|' ||
        coalesce(tp.term::varchar, '')
      )::varchar ORDER BY t.sort_order, t.term
    ), '**') AS terms
  FROM sample_attributes a
  JOIN sample_attributes_websites aw ON aw.sample_attribute_id = a.id AND aw.deleted = false
  LEFT JOIN cache_termlists_terms t ON t.termlist_id = a.termlist_id
  LEFT JOIN cache_termlists_terms tp ON tp.id = t.parent_id
  LEFT JOIN cache_termlists_terms sm ON sm.id = aw.restrict_to_sample_method_id
  LEFT JOIN form_structure_blocks fsb1
    ON fsb1.id = aw.form_structure_block_id
    AND fsb1.survey_id = aw.restrict_to_survey_id
  LEFT JOIN form_structure_blocks fsb2
    ON fsb2.id = fsb1.parent_id
    AND fsb2.survey_id = aw.restrict_to_survey_id
  WHERE a.deleted = false
    AND aw.restrict_to_survey_id = {survey_id}
  GROUP BY a.caption, a_caption_i18n, a.unit, a.term_name,
    a.term_identifier, a.description, a_description_i18n, a.system_function,
    a.data_type, a.multi_value, a.allow_ranges, a.public,
    a.validation_rules, a.applies_to_location, a.applies_to_recorder,
    sm.term, aw.validation_rules, aw.weight, aw.control_type_id,
    aw.website_id, aw.default_text_value, aw.default_float_value,
    aw.default_int_value, aw.default_date_start_value,
    aw.default_date_end_value, aw.default_date_type_value,
    fsb1.name, fsb1.weight, fsb2.name, fsb2.weight, t.termlist_title
  ORDER BY fsb1.weight, fsb2.weight, aw.weight";

  /**
   * @const SQL_FETCH_ALL_OCCURRENCE_ATTRS Query definition which retrieves
   * all the occurrence attribute details for a survey ID in preparation for
   * export.
   */
  const SQL_FETCH_ALL_OCCURRENCE_ATTRS = "SELECT
    a.caption,
    (with table_of_caption_i18n as
      (
        select
        value || '|' || key as cap_pipe_lang
        from json_each_text(a.caption_i18n)
	    )
      select array_to_string(array_agg(cap_pipe_lang), '**')
      from table_of_caption_i18n
    ) as a_caption_i18n,
    a.unit,
    a.term_name,
    a.term_identifier,
    a.description,
    (with table_of_description_i18n as
      (
        select
        value || '|' || key as desc_pipe_lang
        from json_each_text(a.description_i18n)
	    )
      select array_to_string(array_agg(desc_pipe_lang), '**')
      from table_of_description_i18n
    ) as a_description_i18n,
    a.system_function,
    a.data_type,
    a.multi_value,
    a.allow_ranges,
    a.public,
    a.validation_rules,
    aw.validation_rules AS aw_validation_rules,
    aw.weight AS aw_weight,
    aw.control_type_id AS aw_control_type_id,
    aw.website_id AS aw_website_id,
    aw.default_text_value AS aw_default_text_value,
    aw.default_float_value AS aw_default_float_value,
    aw.default_int_value AS aw_default_int_value,
    aw.default_date_start_value AS aw_default_date_start_value,
    aw.default_date_end_value AS aw_default_date_end_value,
    aw.default_date_type_value AS aw_default_date_type_value,
    fsb1.name AS fsb1_name,
    fsb1.weight AS fsb1_weight,
    fsb2.name AS fsb2_name,
    fsb2.weight AS fsb2_weight,
    t.termlist_title AS termlist_title,
    array_to_string(array_agg(
      (
        t.term || '|' ||
        t.language_iso || '|' ||
        coalesce(t.sort_order::varchar, '') || '|' ||
        coalesce(tp.term::varchar, '')
      )::varchar ORDER BY t.sort_order, t.term
    ), '**') AS terms
  FROM occurrence_attributes a
  JOIN occurrence_attributes_websites aw
    ON aw.occurrence_attribute_id = a.id
    AND aw.deleted = false
  LEFT JOIN cache_termlists_terms t ON t.termlist_id = a.termlist_id
  LEFT JOIN cache_termlists_terms tp ON tp.id = t.parent_id
  LEFT JOIN form_structure_blocks fsb1
    ON fsb1.id = aw.form_structure_block_id
    AND fsb1.survey_id = aw.restrict_to_survey_id
  LEFT JOIN form_structure_blocks fsb2
    ON fsb2.id = fsb1.parent_id
    AND fsb2.survey_id = aw.restrict_to_survey_id
  WHERE a.deleted = false
    AND aw.restrict_to_survey_id = {survey_id}
  GROUP BY  a.caption, a_caption_i18n, a.unit, a.term_name,
    a.term_identifier, a.description, a_description_i18n, a.system_function,
    a.data_type, a.multi_value, a.allow_ranges, a.public,
    a.validation_rules, aw.validation_rules, aw.weight, aw.control_type_id,
    aw.website_id, aw.default_text_value, aw.default_float_value,
    aw.default_int_value, aw.default_date_start_value,
    aw.default_date_end_value, aw.default_date_type_value,
    fsb1.name, fsb1.weight, fsb2.name, fsb2.weight, t.termlist_title
  ORDER BY fsb1.weight, fsb2.weight, aw.weight";

  /**
   * @const SQL_FIND_ATTRS Query definition which searches for an existing
   * attribute which matches the definition of one being imported. Uses an
   * array aggregation to get details of all terms which must be manually
   * tested after running the query, since PostgreSQL does not support
   * aggregates in the where clause. The order by clause puts any attributes
   * already used by this website at the top.
   */
  const SQL_FIND_ATTRS = "SELECT
    a.id,
    a.caption,
    (with table_of_caption_i18n as
      (
        select
        value || '|' || key as cap_pipe_lang
        from json_each_text(a.caption_i18n)
	    )
      select array_to_string(array_agg(cap_pipe_lang), '**')
      from table_of_caption_i18n
    ) as a_caption_i18n,
    a.unit,
    a.term_name,
    a.term_identifier,
    a.description,
    (with table_of_description_i18n as
      (
        select
        value || '|' || key as desc_pipe_lang
        from json_each_text(a.description_i18n)
	    )
      select array_to_string(array_agg(desc_pipe_lang), '**')
      from table_of_description_i18n
    ) as a_description_i18n,
    a.system_function,
    a.data_type,
    a.multi_value,
    a.allow_ranges,
    a.public,
    a.validation_rules{extraFields},
	  t.termlist_title AS termlist_title,
    aw.website_id,
	  array_to_string(array_agg(
      (
        t.term || '|' ||
        t.language_iso || '|' ||
        coalesce(t.sort_order::varchar, '') || '|' ||
        coalesce(tp.term::varchar, '')
      )::varchar order by t.sort_order, t.term
    ), '**') AS terms
  FROM {type}_attributes a
  LEFT JOIN cache_termlists_terms t ON t.termlist_id = a.termlist_id
  LEFT JOIN cache_termlists_terms tp ON tp.id = t.parent_id
  LEFT JOIN {type}_attributes_websites aw
    ON aw.{type}_attribute_id = a.id
    AND aw.deleted = false
    AND aw.website_id = {websiteId}
  WHERE a.deleted = false
  AND (a.public = true OR aw.id IS NOT NULL)
  {where}
  GROUP BY a.id, a.caption, a_caption_i18n, a.unit, a.term_name,
    a.term_identifier, a.description, a_description_i18n, a.system_function,
    a.data_type, a.multi_value, a.allow_ranges, a.public,
    a.validation_rules{extraFields}, t.termlist_title, aw.website_id
  ORDER BY aw.website_id IS NULL, aw.website_id = {websiteId}";

  /**
   * @const SQL_FIND_TERMLIST Query definition which searches for an existing
   * termlist which matches the definition of one being imported. Uses an array
   * aggregation to get details of all terms which must be manually tested
   * after running the query, since PostgreSQL does not support aggregates in
   * the where clause.
   */
  const SQL_FIND_TERMLIST = "SELECT
    t.termlist_id,
    t.termlist_title,
    array_to_string(array_agg(
      (
        t.term || '|' ||
        t.language_iso || '|' ||
        coalesce(t.sort_order::varchar, '') || '|' ||
        coalesce(tp.term::varchar, '')
      )::varchar ORDER BY t.sort_order, t.term
    ), '**') AS terms
  FROM cache_termlists_terms t
  LEFT JOIN cache_termlists_terms tp ON tp.id = t.parent_id
  WHERE {where}
  GROUP BY t.termlist_id, t.termlist_title";

  /**
   * Constructor.
   */
  public function __construct() {
    parent::__construct();
  }

  /**
   * Controller action for the export tab content. Displays the view containing
   * a block of exportable content as well as a textarea into which exports
   * from elsewhere can be pasted.
   */
  public function index() {
    $this->view = new View('survey_structure_export/index');
    $surveyId = $this->uri->last_segment();
    // Get website ID for this survey.
    $survey = $this->db
      ->select('website_id')
      ->from('surveys')
      ->where(['id' => $surveyId])
      ->get()->result_array(FALSE);
    $websiteId = $survey[0]['website_id'];
    $this->website_id = $websiteId;
    $this->view->surveyId = $surveyId;
    // Get the attribute data (including termlists) associated with the survey
    // ready to export.
    $export = $this->getSurveyAttributes($websiteId, $surveyId);
    $this->view->export = json_encode($export);
    $this->template->content = $this->view;
  }

  /**
   * Controller action called when Save clicked. Perform the import when text
   * has been pasted into the import text area.
   */
  public function save() {
    $surveyId = $_POST['survey_id'];
    // Get website ID for this survey.
    $survey = $this->db
      ->select('website_id, title')
      ->from('surveys')
      ->where(['id' => $surveyId])
      ->get()->result_array(FALSE);
    $this->website_id = $survey[0]['website_id'];

    if (empty($_POST['import_survey_structure'])) {
      // Return error if import text was not provided.
      $this->template->title = 'Error during survey structure import';
      $this->view = new View('templates/error_message');
      $this->view->message = 'Please ensure you copy the details of a ' .
      'survey\'s attributes into the "Import survey structure" box before importing.';
      $this->template->content = $this->view;
    }
    else {
      // Start a transaction for import.
      $this->db->query('BEGIN;');
      try {
        $importData = json_decode($_POST['import_survey_structure'], TRUE);
        $this->doImport($importData);
        $this->template->title = 'Import Complete';
        $this->view = new View('survey_structure_export/import_complete');
        $this->view->log = $this->log;
        $this->template->content = $this->view;
        $this->db->query('COMMIT;');
      }
      catch (Exception $e) {
        // Roll back transaction on error.
        $this->db->query('ROLLBACK;');
        error_logger::log_error('Exception during survey structure import', $e);
        $this->template->title = 'Error during survey structure import';
        $this->view = new View('templates/error_message');
        $this->view->message = 'An error occurred during the survey structure ' .
        'import and no changes have been made to the database. Please make ' .
        'sure the import data is valid. More information can be found in the warehouse logs.';
        $this->template->content = $this->view;
      }
    }

    $this->page_breadcrumbs[] = html::anchor('survey', 'Surveys');
    $this->page_breadcrumbs[] = html::anchor('survey/edit/' . $surveyId, $survey[0]['title']);
    $this->page_breadcrumbs[] = $this->template->title;
  }

  /**
   * Import a pasted definition of a set of custom attributes.
   *
   * @param array $importData The array definition of the attributes to import.
   */
  public function doImport($importData) {
    // Determine userId to use for creating records.
    if (isset($_SESSION['auth_user'])) {
      $this->userId = $_SESSION['auth_user']->id;
    }
    else {
      global $remoteUserId;
      if (isset($remoteUserId)) {
        $this->userId = $remoteUserId;
      }
      else {
        $defaultUserId = Kohana::config('indicia.defaultPersonId');
        $this->userId = ($defaultUserId ? $defaultUserId : 1);
      }
    }

    // Process attributes.
    foreach ($importData['srvAttrs'] as $importAttrDef) {
      $this->processAttribute('survey', $importAttrDef, []);
    }
    foreach ($importData['smpAttrs'] as $importAttrDef) {
      $this->processAttribute(
        'sample',
        $importAttrDef,
        ['applies_to_location', 'applies_to_recorder']
      );
    }
    foreach ($importData['occAttrs'] as $importAttrDef) {
      $this->processAttribute('occurrence', $importAttrDef, []);
    }
  }

  /**
   * Handles the import of a single custom attribute.
   *
   * @param string $type occurrence, sample, or survey.
   * @param array $importAttrDef Definition of the attribute in an array, as
   * retrieved from the imported data.
   * @param array $extraFields List of non-standard fields in this attributes
   * database table.
   */
  private function processAttribute($type, $importAttrDef, $extraFields) {
    $this->log[] = "Processing $type attribute: $importAttrDef[caption]";
    // Fetch possible matches based on the following SQL field matches.
    $fieldsToMatch = [
      'caption' => 'a.caption',
      'data_type' => 'a.data_type',
      'validation_rules' => 'a.validation_rules',
      'multi_value' => 'a.multi_value',
      'public' => 'a.public',
      'system_function' => 'a.system_function',
    ];
    // Depending on the type of attribute, there might
    // be some additional fields to match. We also need these in a string
    // suitable for adding to the SQL select and group by clauses.
    $extras = '';
    foreach ($extraFields as $field) {
      $fieldsToMatch[$field] = "a.$field";
      $extras .= ", a.$field";
    }
    // Build the where clause required to do the match to see if an existing
    // attribute meets our needs.
    $where = '';
    foreach ($fieldsToMatch as $field => $fieldsql) {
      $value = pg_escape_string($this->db->getLink(), $importAttrDef[$field] ?? '');
      if ($importAttrDef[$field] === '' || $importAttrDef[$field] === NULL) {
        $where .= "and coalesce($fieldsql, '') = '$value' ";
      }
      else {
        $where .= "and $fieldsql = '$value' ";
      }
    }

    $query = str_replace(
      ['{type}', '{where}', '{extraFields}', '{websiteId}'],
      [$type, $where, $extras, $this->website_id],
      self::SQL_FIND_ATTRS
    );
    $possibleMatches = $this->db->query($query)->result_array(FALSE);
    // We now have one or more possible matching attributes. Strip out any that
    // don't match the aggregated termlist data.
    $existingAttrs = [];
    foreach ($possibleMatches as $possibleMatch) {
      // Additional checks that can't be done in SQL because aggregates don't
      // work in SQL where clauses.
      if (
        $possibleMatch['termlist_title'] === $importAttrDef['termlist_title']
        && $possibleMatch['terms'] === $importAttrDef['terms']
      ) {
        $existingAttrs[] = $possibleMatch;
      }
    }
    if (count($existingAttrs) === 0) {
      // Create a new attribute if no existing match was found.
      $attrId = $this->createAttr($type, $importAttrDef, $extraFields);
    }
    else {
      // Because the find query puts the attributes already used by this
      // website at the top, we can use $existingAttrs[0] to link to safely.
      $this->linkAttr($type, $importAttrDef, $existingAttrs[0]);
      $attrId = $existingAttrs[0]['id'];
    }
    if ($type === 'survey') {
      $this->createAttrValue($importAttrDef, $attrId);
    }
  }

  /**
   * Create a custom attribute.
   *
   * @param string $type Type of custom attribute: [survey|sample|occurrence].
   * @param array $attrDef Definition of the attribute in an array, as
   * retrieved from the imported data.
   * @param array $extraFields List of non-standard fields in this attributes
   * database table.
   * @return int Returns the database ID of the created attribute.
   * @throws \exception
   */
  private function createAttr($type, $attrDef, $extraFields) {
    // List standard fields and values to set.
    $array = [
      'caption' => $attrDef['caption'],
      'caption_i18n' => str_replace('**', "\n", $attrDef['a_caption_i18n'] ?? ''),
      'unit' => $attrDef['unit'],
      'term_name' => $attrDef['term_name'],
      'term_identifier' => $attrDef['term_identifier'],
      'description' => $attrDef['description'],
      'description_i18n' => str_replace('**', "\n", $attrDef['a_description_i18n'] ?? ''),
      'system_function' => $attrDef['system_function'],
      'data_type' => $attrDef['data_type'],
      'multi_value' => $attrDef['multi_value'],
      'allow_ranges' => $attrDef['allow_ranges'],
      'public' => $attrDef['public'],
      'validation_rules' => $attrDef['validation_rules'],
    ];
    // Append any extra fields and their values.
    foreach ($extraFields as $field) {
      $array[$field] = $attrDef[$field];
    }

    // Lookups need to link to or create a termlist.
    if ($attrDef['data_type'] === 'L') {
      // Find termlists with the same name that are available to this website?
      $possibleMatches = $this->db->query(
          str_replace(
            '{where}',
            "t.termlist_title = '$attrDef[termlist_title]' and " .
            "(t.website_id = {$this->website_id} or t.website_id is null)",
            self::SQL_FIND_TERMLIST
          )
      )->result_array(FALSE);
      // Now double check that the found termlist(s) have the same set of terms
      // we are expecting.
      $termlists = [];
      foreach ($possibleMatches as $possibleMatch) {
        if ($possibleMatch['terms'] === $attrDef['terms']) {
          $termlists[] = $possibleMatch;
        }
      }
      // Do we have any matching termlists?
      if (count($termlists) >= 1) {
        // Use the existing termlist to provide terms for the new custom attribute.
        $array['termlist_id'] = $termlists[0]['termlist_id'];
      }
      else {
        // Create a new termlist for the new custom attribute.
        $array['termlist_id'] = $this->createTermlist($attrDef);
      }
    }

    // Save array of values to new attribute.
    $a = ORM::factory("{$type}_attribute");
    $a->set_submission_data($array);
    if (!$a->submit()) {
      throw new exception("Error creating $type attribute for $attrDef[caption]");
    }
    else {
      $this->log[] = "Created $type attribute $attrDef[caption]";
      $this->linkAttr($type, $attrDef, $a->as_array());
    }
    return $a->id;
  }

  /**
   * Create a new termlist and populate it with the terms required for a new lookup
   * custom attribute.
   *
   * @param array $attrDef Definition of the attribute as defined by the imported data.
   * @return int Returns the database ID of the created termist.
   * @throws \exception
   */
  private function createTermlist($attrDef) {
    $tl = ORM::factory('termlist');
    $tl->set_submission_data([
      'title' => $attrDef['termlist_title'],
      'description' => "Terms for the $attrDef[caption] attribute",
      'website_id' => $this->website_id,
      // Following is required because termlists have a deleted callback.
      'deleted' => 'f',
    ]);
    if (!$tl->submit()) {
      throw new exception("Error creating termlist $attrDef[termlist_title] ' .
      'for $attrDef[caption]");
    }
    else {
      // Now we need to create the terms required by the termlist. Split the
      // terms string into individual terms.
      $terms = explode('**', $attrDef['terms']);
      foreach ($terms as $term) {
        // The tokens defining the term are separated by pipes.
        $term = explode('|', $term);
        // SQL escaping.
        $escapedTerm = pg_escape_string($this->db->getLink(), $term[0]);
        // Sanitise the sort order.
        $term[2] = empty($term[2]) ? 'null' : $term[2];
        $this->db->query(
          "SELECT insert_term('$escapedTerm', '$term[1]', $term[2], {$tl->id}, null);"
        );
      }
      // Now re-iterate through the terms and set the term parents.
      foreach ($terms as $term) {
        // The tokens defining the term are separated by pipes.
        $term = explode('|', $term);
        if (!empty($term[3])) {
          // SQL escaping.
          $escapedTerm = pg_escape_string($this->db->getLink(), $term[0]);
          $escapedParent = pg_escape_string($this->db->getLink(), $term[3]);
          $this->db->query("UPDATE termlists_terms tlt set parent_id = tltp.id
            FROM terms t, termlists_terms tltp
            JOIN terms tp
              ON tp.id = tltp.term_id
              AND tp.deleted = false
              AND tp.term = '$escapedParent'
            WHERE tlt.termlist_id = {$tl->id}
              AND t.id = tlt.term_id
              AND t.deleted = false
              AND t.term = '$escapedTerm'
              AND tltp.termlist_id = tlt.termlist_id
              AND tltp.deleted = false");
        }
      }
      $this->log[] = "Created termlist $attrDef[termlist_title]";
    }
    return $tl->id;
  }

  /**
   * Link an attribute to the survey by checking a {type}_attributes_websites
   * record exists and if not then creates it.
   *
   * @param string $type Type of attribute we are working on, [survey|sample|occurrence].
   * @param array $importAttrDef The definition of the attribute we are importing.
   * @param array $existingAttr The array definition of the attribute to link, which must
   * already exist.
   * @throws \exception
   * @internal param array $attrDef Definition of the attribute as defined by the imported data.
   */
  private function linkAttr($type, $importAttrDef, $existingAttr) {
    if ($type === 'survey') {
      // Survey attributes are not restricted to survey like samples and
      // occurrences.
      $where = [
        "{$type}_attribute_id" => $existingAttr['id'],
        'website_id' => $this->website_id,
      ];
    }
    else {
      $where = [
        "{$type}_attribute_id" => $existingAttr['id'],
        'restrict_to_survey_id' => $_POST['survey_id'],
      ];
    }
    $aw = ORM::factory("{$type}_attributes_website")->where($where)->find();

    if ($aw->loaded) {
      $this->log[] = 'An attribute similar to this is already linked to the ' .
      'survey - no action taken.';
    }
    else {
      // Need to create a link in sample_attributes_websites to link the
      // existing attribute to the survey.
      $fkName = "{$type}_attribute_id";
      $aw->$fkName = $existingAttr['id'];
      $aw->website_id = $this->website_id;
      if ($type !== 'survey') {
        $aw->restrict_to_survey_id = $_POST['survey_id'];
      }
      $aw->validation_rules = $importAttrDef['aw_validation_rules'];
      $aw->weight = $importAttrDef['aw_weight'];
      $aw->control_type_id = $importAttrDef['aw_control_type_id'];
      $aw->default_text_value = $importAttrDef['aw_default_text_value'];
      $aw->default_float_value = $importAttrDef['aw_default_float_value'];
      $aw->default_int_value = $importAttrDef['aw_default_int_value'];
      $aw->default_date_start_value = $importAttrDef['aw_default_date_start_value'];
      $aw->default_date_end_value = $importAttrDef['aw_default_date_end_value'];
      $aw->default_date_type_value = $importAttrDef['aw_default_date_type_value'];
      $aw->form_structure_block_id = $this->getFormStructureBlockId($type, $importAttrDef);
      $aw->created_on = date("Ymd H:i:s");
      $aw->created_by_id = $this->userId;
      if ($type === 'sample' && !empty($importAttrDef['aw_restrict_to_sample_method_id_term'])) {
        $sm = $this->db->query(
          "SELECT id FROM list_termlists_terms
          WHERE termlist='Sample methods'
          AND term='$importAttrDef[aw_restrict_to_sample_method_id_term]'"
        )->result_array(FALSE);

        if (count($sm) === 0) {
          $this->db->query(
            "SELECT insert_term(
              '$importAttrDef[aw_restrict_to_sample_method_id_term]',
              'eng',
              null,
              null,
              'indicia:sample_methods'
            );"
          );
        }
        else {
          $aw->restrict_to_sample_method_id = $sm[0]['id'];
        }
      }
      if (!$aw->save()) {
        throw new exception("Error creating $type attributes website record " .
        "to associate $importAttrDef[caption].");
      }
    }
  }

  /**
   * Given an attribute import definition, work out if the correct form
   * structure blocks are already available and return the appropriate ID. If
   * not already available then the form structure blocks are created.
   *
   * @todo Should probably use the database agnostic query builder here.
   * @param string $type Type of attribute we are working on: [survey|sample|occurrence].
   * @param array $attrDef Definition of the attribute as defined by the imported data.
   * @return integer The form structure block ID to link this attribute to.
   */
  private function getFormStructureBlockId($type, $attrDef) {
    if (empty($attrDef['fsb1_name'])) {
      return NULL;
    }
    // Survey attributes never have form structure blocks so will
    // have already been turned back.
    $type = ($type === 'sample') ? 'S' : 'O';
    $query = "SELECT fsb1.id
        FROM form_structure_blocks fsb1
        LEFT JOIN form_structure_blocks fsb2 ON fsb2.id = fsb1.parent_id
        WHERE fsb1.name = '$attrDef[fsb1_name]'
        AND fsb1.survey_id = $_POST[survey_id]
        AND fsb1.type = '$type'\n";
    if (empty($attrDef['fsb2_name'])) {
      $query .= 'AND fsb2.id is null';
    }
    else {
      $query .= "AND fsb2.name = '$attrDef[fsb2_name]'
          AND fsb2.survey_id = $_POST[survey_id]
          AND fsb2.type = '$type'";
    }
    $matches = $this->db->query($query)->result_array(FALSE);
    if (count($matches)) {
      // Matching form structure block exists.
      return $matches[0]['id'];
    }
    else {
      // Need to create the form structure block.
      $parent = FALSE;
      if (!empty($attrDef['fsb2_name'])) {
        // If we have a parent block, find an existing one or create a new one as appropriate.
        $parent = ORM::factory('form_structure_block')->where([
          'name' => $attrDef['fsb2_name'],
          'survey_id' => $_POST['survey_id'],
          'parent_id' => NULL,
        ]);
        if (!$parent->loaded) {
          $parent->name = $attrDef['fsb2_name'];
          $parent->survey_id = $_POST['survey_id'];
          $parent->type = $type;
          $parent->weight = $attrDef['fsb2_weight'];
          $parent->save();
        }
      }
      // Now create the child.
      $child = ORM::factory('form_structure_block');
      $child->name = $attrDef['fsb1_name'];
      $child->survey_id = $_POST['survey_id'];
      $child->type = $type;
      $child->weight = $attrDef['fsb1_weight'];
      if ($parent) {
        $child->parent_id = $parent->id;
      }
      $child->save();
      return $child->id;
    }
  }

  /**
   * Create survey attribute values.
   *
   * @param array $attrDef Definition of the attribute in an array, as
   * retrieved from the imported data.
   * @param int $attrId The database ID of the attribute to create values for.
   */
  private function createAttrValue($attrDef, $attrId) {
    if ($attrDef['av_values'] === '') {
      // No values to create.
      return;
    }

    $fixedCols = [
      'survey_id' => $_POST['survey_id'],
      'survey_attribute_id' => $attrId,
    ];

    // Multiple attribute values will be separated by '**'.
    $inValues = explode('**', $attrDef['av_values']);
    foreach ($inValues as $inValue) {

      $values = [];
      switch ($attrDef['data_type']) {
        case 'T':
          $values['text_value'] = $inValue;
          break;

        case 'F':
          list(
            $values['float_value'],
            $values['upper_value']
          ) = explode('|', $inValue);
          break;

        case 'L':
          // From the attrId we can get the termlist_id, thence we can find the
          // matching term. The cache_termlists_terms cannot be relied upon to
          // have any new records.
          $query = "SELECT tlt.id
          FROM termlists_terms tlt
          JOIN termlists tl ON tl.id = tlt.termlist_id AND tl.deleted = false
          JOIN terms t ON t.id = tlt.term_id AND t.deleted = false
          JOIN survey_attributes sa ON sa.termlist_id = tl.id AND sa.deleted = false
          WHERE tlt.deleted = false
            AND sa.id = $attrId
            AND t.term = '$inValue'";

          $matches = $this->db->query($query)->result_array(FALSE);
          if (count($matches) === 0) {
            throw new exception("Error creating survey attribute value " .
            "record for $attrDef[caption]. No matching term.");
          }
          else {
            $values['int_value'] = $matches[0]['id'];
          }
          break;

        case 'I':
          list(
            $values['int_value'],
            $values['upper_value']
          ) = explode('|', $inValue);
          break;

        case 'V':
          list(
            $values['date_start_value'],
            $values['date_end_value'],
            $values['date_type_value']
          ) = explode('|', $inValue);
          break;
      }

      $array = array_merge($fixedCols, $values);
      $a = ORM::factory("survey_attribute_value");
      $a->set_submission_data($array);
      if (!$a->submit()) {
        throw new exception("Error creating survey attribute value for $attrDef[caption]");
      }
      else {
        $this->log[] = "Created survey attribute value $attrDef[caption]";
      }
    } // End foreach.
  }

  /**
   * Retrieves the data for a list of attributes associated with a given survey.
   *
   * @param integer $websiteId Website ID
   * @param integer $surveyId Survey ID
   * @return array A version of the data which has been changed into structured
   * arrays of the data from the tables.
   */
  public function getSurveyAttributes($websiteId, $surveyId) {
    $query_templates = [
      'srvAttrs' => self::SQL_FETCH_ALL_SURVEY_ATTRS,
      'smpAttrs' => self::SQL_FETCH_ALL_SAMPLE_ATTRS,
      'occAttrs' => self::SQL_FETCH_ALL_OCCURRENCE_ATTRS,
    ];

    $result = [];
    foreach ($query_templates as $attrs => $template) {
      $query = str_replace(
        ['{survey_id}', '{website_id}'],
        [$surveyId, $websiteId],
        $template
      );
      $result[$attrs] = $this->db->query($query)->result_array(FALSE);
    }

    return $result;
  }

}
