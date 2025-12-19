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

defined('SYSPATH') or die('No direct script access.');

/**
 * Model class for the classification_results table.
 *
 * Each row represents a single set of results (suggestions) from an image
 * classifier having been sent a set of images to classify.
 */
class Classification_result_Model extends ORM {

  /**
   * Media join info.
   *
   * List of many to many joins to occurrence media that needs to be created
   * after submission done, in postProcess.
   */
  public static $mediaJoins = [];

  protected $belongs_to = [
    'classification_event',
    'classifier' => 'termlists_term',
    'created_by' => 'user',
  ];

  protected $has_and_belongs_to_many = [
    'occurrence_media',
  ];

  public function validate(Validation $array, $save = FALSE) {
    $array->pre_filter('trim');
    $array->add_rules('classification_event_id', 'required', 'integer');
    $array->add_rules('classifier_id', 'integer');
    $this->unvalidatedFields = [
      'additional_info_submitted',
      'classifier_version',
      'results_raw',
      'deleted',
    ];
    return parent::validate($array, $save);
  }

  /**
   * Submission structure includes metaField for joining to media files.
   *
   * See postSubmit for further info.
   */
  public function get_submission_structure() {
    return [
      'model' => $this->object_name,
      'metaFields' => [
        'mediaPaths',
      ],
    ];
  }

  /**
   * Track any linked media in the same submission.
   *
   * If a complex submission contains an occurrence, media file and
   * classification result linked to the media file, then there are circular
   * references in the resulting data. So, it is impossible to know the pk of
   * the media file at this stage as it hasn't been inserted yet. Therefore
   * the classification_result's submission can contain a metaField value
   * called mediaPaths with the path of each file to link. This method just
   * tracks these so that they can be linked once the submission is complete.
   *
   * @param bool $isInsert
   *   True if insert, false if update.
   */
  public function postSubmit($isInsert) {
    if (array_key_exists('metaFields', $this->submission) && array_key_exists('mediaPaths', $this->submission['metaFields'])) {
      // Just remember for processing after complete submission done. Otherwise
      // the other required table data may not exist yet.
      self::$mediaJoins[$this->id] = [
        'pathsJson' => $this->submission['metaFields']['mediaPaths'],
        'classification_event_id' => $this->classification_event_id,
      ];
    }
    return TRUE;
  }

  /**
   * Creates links from posted media to the classification result.
   *
   * Called by MY_ORM's postProcess method after submission complete.
   *
   * @param object $db
   *   Database connection.
   */
  public static function createMediaJoins($db) {
    $occurrencesToUpdate = [];
    foreach (self::$mediaJoins as $crId => $joinInfo) {
      // Find the list of missing occurrence_media IDs that correspond to the
      // paths.
      $pathsArray = is_string($joinInfo['pathsJson']) ? json_decode($joinInfo['pathsJson']) : $joinInfo['pathsJson'];
      $paths = warehouse::stringArrayToSqlInList($db, $pathsArray);
      $occurrenceMediaQuery = <<<SQL
SELECT om.id as occurrence_media_id, om.occurrence_id
FROM occurrence_media om
JOIN occurrences o ON o.id=om.occurrence_id AND o.deleted=false
AND o.classification_event_id=$joinInfo[classification_event_id]
LEFT JOIN classification_results_occurrence_media crom ON crom.classification_result_id=$crId AND crom.occurrence_media_id=om.id
WHERE om.deleted=false AND om.path in ($paths)
AND crom.id IS NULL;
SQL;
      $mediaLinkInfo = $db->query($occurrenceMediaQuery)->result();
      // Add the join records.
      foreach ($mediaLinkInfo as $mediaLink) {
        $m = ORM::factory('classification_results_occurrence_medium');
        $m->occurrence_media_id = $mediaLink->occurrence_media_id;
        $m->classification_result_id = $crId;
        $m->set_metadata();
        $m->save();
        // Keep a distinct list of the affected occurrences.
        $occurrencesToUpdate[$mediaLink->occurrence_id] = $mediaLink->occurrence_id;
      }
    }
    if (count($occurrencesToUpdate) > 0) {
      // Now that the joins are in place, we can set
      // cache_occurrences_functional.classifier_agreement.
      $occurrenceIdsCsv = implode(',', $occurrencesToUpdate);
      $db->query(<<<SQL
        UPDATE cache_occurrences_functional u
        SET classifier_agreement=false
        FROM occurrences o
        JOIN occurrence_media m ON m.occurrence_id=o.id AND m.deleted=false
        JOIN classification_results_occurrence_media crom ON crom.occurrence_media_id=m.id
        WHERE u.id=o.id
        AND o.id IN ($occurrenceIdsCsv);

        UPDATE cache_occurrences_functional u
        SET classifier_agreement=COALESCE(cs.classifier_chosen, false)
        FROM occurrences o
        JOIN occurrence_media m ON m.occurrence_id=o.id AND m.deleted=false
        JOIN classification_results_occurrence_media crom ON crom.occurrence_media_id=m.id
        LEFT JOIN (classification_suggestions cs
          JOIN cache_taxa_taxon_lists cttl on cttl.id=cs.taxa_taxon_list_id
        ) ON cs.classification_result_id=crom.classification_result_id AND cs.deleted=false
        WHERE u.id=o.id
        AND (cttl.external_key=u.taxa_taxon_list_external_key OR cs.id IS NULL)
        AND o.id IN ($occurrenceIdsCsv);
        SQL
      );
    }
  }

}
