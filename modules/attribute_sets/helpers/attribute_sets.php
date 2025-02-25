<?php

/**
 * @file
 * Helper class to assist in syncing attribute sets data.
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
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link https://github.com/indicia-team/warehouse
 */

 defined('SYSPATH') or die('No direct script access.');

/**
 * Helper class to provide generally useful Indicia warehouse functions.
 */
class attribute_sets {

  private static $aliases = [
    'attribute_set' => 'aset',
    'attribute_sets_survey' => 'ass',
    'attribute_sets_taxa_taxon_list_attribute' => 'asttla',
    'occurrence_attributes_taxa_taxon_list_attribute' => 'attla',
    'sample_attributes_taxa_taxon_list_attribute' => 'attla',
    'attribute_sets_taxon_restriction' => 'astr',
  ];

  private static $rootEntities = [
    'occurrence_attributes_taxa_taxon_list_attribute' => 'occurrence',
    'sample_attributes_taxa_taxon_list_attribute' => 'samples',
  ];

  /**
   * Determines if linked sample or occurrence attribute needed.
   *
   * Checks a taxa_taxon_list_attribute which is being linked to an occurrence
   * or sample attribute to see if this is really required. There can be
   * several attributes to define the confidence ranges for a taxon (e.g. 80%
   * or 95%) and if so, then we only want to create an occurrence or sample
   * attribute for one of them as we are capturing an exact value with the
   * field record data.
   */
  public static function isLinkedAttributeRequired($ttlAttrModel) {
    kohana::log('debug', "checking $ttlAttrModel->caption");
    if ($ttlAttrModel->allow_ranges === 't'
        && preg_match('/ \(\d+%\)$/', $ttlAttrModel->caption)
        && !preg_match('/ \(95%\)$/', $ttlAttrModel->caption)) {
      kohana::log('debug', "Not required: $ttlAttrModel->caption");
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Clean up attribute captions with percentiles.
   *
   * When linking a taxa taxon list range attribute with a confidence
   * percentile, we want to exclude the percentile from the generated
   * occurrence or sample attribute.
   *
   * @param bool $allowRanges
   *   TRUE if ranges allowed, enabling the removal of percentile data.
   * @param string $text
   *   The caption or caption_i18n data to manipulate.
   *
   * @return string
   *   Cleaned up caption.
   */
  public static function removePercentiles($allowRanges, $text) {
    if ($allowRanges && !empty($text)) {
      return preg_replace('/ \(\d+%\)/', '', $text);
    }
    return $text;
  }

  /**
   * Changes required after a change to attribute set data.
   *
   * When one of the entities that defines the configuration of an attribute
   * set is updated, inserted or deleted, ensure that the changes are reflected
   * automatically in the occurrence/sample attribute links to website/surveys.
   *
   * @param object $db
   *   Connection object.
   * @param object $model
   *   Instantiated ORM object just inserted or updated.
   */
  public static function updateSetLinks($db, $model) {
    if ($model->deleted === 't') {
      if ($model->object_name !== 'attribute_sets_taxon_restriction') {
        self::deleteFieldAttributesWebsites($db, $model);
      }
      self::deleteAttributesTaxonRestriction($db, $model);
    }
    else {
      if ($model->object_name !== 'attribute_sets_taxon_restriction') {
        self::insertOrUpdateFieldAttributesWebsites($db, $model);
      }
      self::insertOrUpdateFieldAttributesTaxonRestriction($db, $model);
      if ($model->object_name === 'attribute_set'
          || $model->object_name === 'attribute_sets_taxa_taxon_list_attribute') {
        self::insertMissingTaxonListsAttributes($db, $model);
      }
      if (!preg_match('/^(sample|occurrence)/', $model->object_name)) {
        self::insertOrUpdateTaxonAttributesTaxonRestriction($db, $model);
      }
    }
  }

  /**
   * Create missing links between taxon lists and taxon attributes.
   *
   * Creates the taxon_lists_taxa_taxon_list_attributes records required to
   * link the attributes in the set to the same taxon list that the set
   * belongs to.
   *
   * @param object $db
   *   Connection object.
   * @param object $model
   *   Instantiated ORM object just inserted or updated.
   */
  private static function insertMissingTaxonListsAttributes($db, $model) {
    $alias = self::$aliases[$model->object_name];
    $userId = self::getUserId();
    $qry = <<<SQL
-- Dummy query to prevent ORM trying to get the insert result when there might be none.
INSERT INTO taxon_lists_taxa_taxon_list_attributes
    (taxon_list_id, taxa_taxon_list_attribute_id, created_on, created_by_id)
  SELECT aset.taxon_list_id, asttla.taxa_taxon_list_attribute_id, now(), $userId
  FROM attribute_sets aset
  JOIN attribute_sets_taxa_taxon_list_attributes asttla
    ON asttla.attribute_set_id=aset.id
    AND asttla.deleted=false
  LEFT JOIN taxon_lists_taxa_taxon_list_attributes tlttla
    ON tlttla.taxon_list_id=aset.taxon_list_id
    AND tlttla.taxa_taxon_list_attribute_id=asttla.taxa_taxon_list_attribute_id
    AND tlttla.deleted=false
  WHERE aset.deleted=false
  AND aset.taxon_list_id IS NOT NULL
  AND tlttla.id IS NULL
  AND $alias.id=$model->id;
SQL;
    $db->query($qry);
  }

  /**
   * Create missing taxon attribute restrictions.
   *
   * Creates the taxa_taxon_list_attribute_taxon_restrictions records required
   * to restrict the attributes in the set to the taxonomic branches that the
   * set is linked to.
   *
   * @param object $db
   *   Connection object.
   * @param object $model
   *   Instantiated ORM object just inserted or updated.
   */
  private static function insertOrUpdateTaxonAttributesTaxonRestriction($db, $model) {
    $alias = self::$aliases[$model->object_name];
    $userId = self::getUserId();
    $qry = <<<SQL
-- Dummy comment to prevent Kohana faiing on a zero row insert.
INSERT INTO taxa_taxon_list_attribute_taxon_restrictions
    (taxon_lists_taxa_taxon_list_attribute_id, restrict_to_taxon_meaning_id, restrict_to_stage_term_meaning_id,
     created_on, created_by_id, updated_on, updated_by_id)
  SELECT DISTINCT tlttla.id as taxon_lists_taxa_taxon_list_attribute_id, astr.restrict_to_taxon_meaning_id, astr.restrict_to_stage_term_meaning_id,
    now(), $userId, now(), $userId
FROM taxon_lists_taxa_taxon_list_attributes tlttla
JOIN attribute_sets_taxa_taxon_list_attributes asttla
  ON asttla.taxa_taxon_list_attribute_id=tlttla.taxa_taxon_list_attribute_id
  AND asttla.deleted=false
JOIN attribute_sets aset
  ON aset.id=asttla.attribute_set_id
  AND aset.deleted=false
JOIN attribute_sets_surveys ass
  ON ass.attribute_set_id=asttla.attribute_set_id
  AND ass.deleted=false
JOIN attribute_sets_taxon_restrictions astr
  ON astr.attribute_sets_survey_id=ass.id
  AND astr.deleted=false
LEFT JOIN taxa_taxon_list_attribute_taxon_restrictions ttlatr
  ON ttlatr.taxon_lists_taxa_taxon_list_attribute_id = tlttla.id
  AND ttlatr.restrict_to_taxon_meaning_id=astr.restrict_to_taxon_meaning_id
  AND COALESCE(ttlatr.restrict_to_stage_term_meaning_id, 0)=COALESCE(astr.restrict_to_stage_term_meaning_id, 0)
  AND ttlatr.deleted=false
WHERE ttlatr.id IS NULL
AND $alias.id=$model->id;
SQL;
    $db->query($qry);
  }

  /**
   * Changes required after an update or insert to the attributes_websites.
   *
   * Creates the occurrence_attributes_websites and sample_attributes_websites
   * records required to link the attributes in the set to the same
   * website/survey combinations that the set belongs to.
   *
   * @param object $db
   *   Connection object.
   * @param object $model
   *   Instantiated ORM object just inserted or updated.
   */
  private static function insertOrUpdateFieldAttributesWebsites($db, $model) {
    // Find the alias used im the query for the table we are going to filter
    // against.
    $alias = self::$aliases[$model->object_name];
    // Do we need to process both occurrence and sample attributes, or just
    // one? Depends on what we are filtering against.
    $rootEntities = empty(self::$rootEntities[$model->object_name]) ?
      ['sample', 'occurrence'] : [self::$rootEntities[$model->object_name]];
    $userId = self::getUserId();
    // For each root entity, build a query that inserts the attribute website
    // join records required.
    foreach ($rootEntities as $entity) {
      $qry = <<<SQL
-- dummy comment to prevent Kohana reading insert_id (which breaks if nothing inserted)
insert into {$entity}_attributes_websites (website_id, {$entity}_attribute_id, created_on, created_by_id, restrict_to_survey_id)
select distinct aset.website_id, attla.{$entity}_attribute_id, now(), $userId, ass.survey_id
from attribute_sets_surveys ass
join attribute_sets aset
  on aset.id=ass.attribute_set_id
  and aset.deleted=false
join attribute_sets_taxa_taxon_list_attributes asttla
  on asttla.attribute_set_id=ass.attribute_set_id
  and asttla.deleted=false
join {$entity}_attributes_taxa_taxon_list_attributes attla
  on attla.taxa_taxon_list_attribute_id=asttla.taxa_taxon_list_attribute_id
  and attla.deleted=false
left join {$entity}_attributes_websites aw
  on aw.website_id=aset.website_id
  and aw.restrict_to_survey_id=ass.survey_id
  and aw.{$entity}_attribute_id=attla.{$entity}_attribute_id
  and aw.deleted=false
where ass.deleted=false
and $alias.id=$model->id
and aw.id is null;
SQL;
      $db->query($qry);
    }
  }

  /**
   * Changes required after a delete.
   *
   * Deletes the occurrence_attributes_websites records which are no longer
   * required because of an update to an attribute set.
   *
   * @param object $db
   *   Connection object.
   * @param object $model
   *   Instantiated ORM object just inserted or updated.
   */
  private static function deleteFieldAttributesWebsites($db, $model) {
    // Find the alias used im the query for the table we are going to filter
    // against.
    $alias = self::$aliases[$model->object_name];
    // Do we need to process both occurrence and sample attributes, or just
    // one? Depends on what we are filtering against.
    $rootEntities = empty(self::$rootEntities[$model->object_name]) ?
      ['sample', 'occurrence'] : [self::$rootEntities[$model->object_name]];
    // For each root entity, build a query that inserts the attribute website
    // join records required.
    foreach ($rootEntities as $entity) {
      $qry = <<<SQL
update {$entity}_attributes_websites aw
set deleted=true
from attribute_sets_surveys ass
join attribute_sets aset
  on aset.id=ass.attribute_set_id
join attribute_sets_taxa_taxon_list_attributes asttla
  on asttla.attribute_set_id=ass.attribute_set_id
join {$entity}_attributes_taxa_taxon_list_attributes attla
  on attla.taxa_taxon_list_attribute_id=asttla.taxa_taxon_list_attribute_id
where (ass.deleted or aset.deleted or asttla.deleted or attla.deleted)
and aw.website_id=aset.website_id
and aw.{$entity}_attribute_id=attla.{$entity}_attribute_id
and aw.restrict_to_survey_id=ass.survey_id
and $alias.id=$model->id
and aw.deleted=false
and aw.id not in (
  -- This is the list of stuff which should definitely be kept because it is
  -- still linked via another set.
  select aw.id
  from {$entity}_attributes_websites aw,
    attribute_sets_surveys ass
    join attribute_sets aset
      on aset.id=ass.attribute_set_id
      and aset.deleted=false
    join attribute_sets_taxa_taxon_list_attributes asttla
      on asttla.attribute_set_id=ass.attribute_set_id
      and asttla.deleted=false
    join {$entity}_attributes_taxa_taxon_list_attributes attla
      on attla.taxa_taxon_list_attribute_id=asttla.taxa_taxon_list_attribute_id
      and attla.deleted=false
  where ass.deleted=false
  and aw.website_id=aset.website_id
  and aw.{$entity}_attribute_id=attla.{$entity}_attribute_id
  and aw.restrict_to_survey_id=ass.survey_id
);
SQL;
      $db->query($qry);
    }
  }

  /**
   * Changes required after an update or insert to the taxon_restrictions.
   *
   * Creates the occurrence and sample attributes_taxon_restrictions records
   * required to link the attributes in the set to the same taxon restriction
   * combinations that the set belongs to.
   *
   * @param object $db
   *   Connection object.
   * @param object $model
   *   Instantiated ORM object just inserted or updated.
   */
  private static function insertOrUpdateFieldAttributesTaxonRestriction($db, $model) {
    // Find the alias used im the query for the table we are going to filter
    // against.
    $alias = self::$aliases[$model->object_name];
    // Do we need to process both occurrence and sample attributes, or just
    // one? Depends on what we are filtering against.
    $rootEntities = empty(self::$rootEntities[$model->object_name]) ?
      ['sample', 'occurrence'] : [self::$rootEntities[$model->object_name]];
    $userId = self::getUserId();
    // For each root entity, build a query that inserts the attribute website
    // join records required. These are the records that are implied by the
    // attribute set taxon restrictions which don't already exists.
    foreach ($rootEntities as $entity) {
      $qry = <<<SQL
-- dummy comment to prevent Kohana reading insert_id (which breaks if nothing inserted)
insert into {$entity}_attribute_taxon_restrictions({$entity}_attributes_website_id, restrict_to_taxon_meaning_id, restrict_to_stage_term_meaning_id,
  created_on, created_by_id, updated_on, updated_by_id)
select distinct aw.id, astr.restrict_to_taxon_meaning_id, astr.restrict_to_stage_term_meaning_id,
  now(), $userId, now(), $userId
from attribute_sets_taxon_restrictions astr
join attribute_sets_surveys ass
  on ass.id=astr.attribute_sets_survey_id
  and ass.deleted=false
join attribute_sets aset
  on aset.id=ass.attribute_set_id
  and aset.deleted=false
join attribute_sets_taxa_taxon_list_attributes asttla
  on asttla.attribute_set_id=ass.attribute_set_id
  and asttla.deleted=false
join {$entity}_attributes_taxa_taxon_list_attributes attla
  on attla.taxa_taxon_list_attribute_id=asttla.taxa_taxon_list_attribute_id
  and attla.deleted=false
join {$entity}_attributes_websites aw
  on aw.website_id=aset.website_id
  and aw.restrict_to_survey_id=ass.survey_id
  and aw.{$entity}_attribute_id=attla.{$entity}_attribute_id
  and aw.deleted=false
left join {$entity}_attribute_taxon_restrictions atr
  on atr.{$entity}_attributes_website_id=aw.id
  and atr.deleted=false
  and atr.restrict_to_taxon_meaning_id=astr.restrict_to_taxon_meaning_id
  and coalesce(atr.restrict_to_stage_term_meaning_id, 0)=coalesce(astr.restrict_to_stage_term_meaning_id, 0)
where astr.deleted=false
and $alias.id=$model->id
and atr.id is null;
SQL;
      $db->query($qry);
    }
  }

  private static function deleteAttributesTaxonRestriction($db, $model) {
    // Find the alias used im the query for the table we are going to filter
    // against.
    $alias = self::$aliases[$model->object_name];
    // Do we need to process both occurrence and sample attributes, or just
    // one? Depends on what we are filtering against.
    $rootEntities = empty(self::$rootEntities[$model->object_name]) ?
      ['sample', 'occurrence'] : [self::$rootEntities[$model->object_name]];
    $userId = self::getUserId();
    // For each root entity, build a query that deletes the attribute website
    // join records required.
    foreach ($rootEntities as $entity) {
      $qry = <<<SQL
update {$entity}_attribute_taxon_restrictions atr
set deleted=true, updated_on=now(), updated_by_id=$userId
from attribute_sets_taxon_restrictions astr
join attribute_sets_surveys ass
  on ass.id=astr.attribute_sets_survey_id
join attribute_sets aset
  on aset.id=ass.attribute_set_id
join attribute_sets_taxa_taxon_list_attributes asttla
  on asttla.attribute_set_id=ass.attribute_set_id
join {$entity}_attributes_taxa_taxon_list_attributes attla
  on attla.taxa_taxon_list_attribute_id=asttla.taxa_taxon_list_attribute_id
join {$entity}_attributes_websites aw
  on aw.website_id=aset.website_id
  and aw.restrict_to_survey_id=ass.survey_id
  and aw.{$entity}_attribute_id=attla.{$entity}_attribute_id
where (astr.deleted or ass.deleted or aset.deleted or asttla.deleted or attla.deleted or aw.deleted)
and $alias.id=$model->id
and atr.{$entity}_attributes_website_id=aw.id
and atr.restrict_to_taxon_meaning_id=astr.restrict_to_taxon_meaning_id
and coalesce(atr.restrict_to_stage_term_meaning_id, 0)=coalesce(astr.restrict_to_stage_term_meaning_id, 0)
and atr.deleted=false
and atr.id not in (
  -- Exclude deletions for any restriction that is still valid because of
  -- another attribute set.
  select atr.id
  from {$entity}_attribute_taxon_restrictions, attribute_sets_taxon_restrictions astr
  join attribute_sets_surveys ass
    on ass.id=astr.attribute_sets_survey_id
    and ass.deleted=false
  join attribute_sets aset
    on aset.id=ass.attribute_set_id
    and aset.deleted=false
  join attribute_sets_taxa_taxon_list_attributes asttla
    on asttla.attribute_set_id=ass.attribute_set_id
    and asttla.deleted=false
  join {$entity}_attributes_taxa_taxon_list_attributes attla
    on attla.taxa_taxon_list_attribute_id=asttla.taxa_taxon_list_attribute_id
    and attla.deleted=false
  join {$entity}_attributes_websites aw
    on aw.website_id=aset.website_id
    and aw.restrict_to_survey_id=ass.survey_id
    and aw.{$entity}_attribute_id=attla.{$entity}_attribute_id
    and aw.deleted=false
  where astr.deleted=false
  and atr.{$entity}_attributes_website_id=aw.id
  and atr.restrict_to_taxon_meaning_id=astr.restrict_to_taxon_meaning_id
  and coalesce(atr.restrict_to_stage_term_meaning_id, 0)=coalesce(astr.restrict_to_stage_term_meaning_id, 0)
);
SQL;
      $db->query($qry);
    }

    // taxa_taxon_list_attributes do not use a website table so the above SQL does not work
    // This code is a version of the above code for taxa_tax_list_attributes
    $ttlQry = <<<SQL
    update taxa_taxon_list_attribute_taxon_restrictions atr
    set deleted=true, updated_on=now(), updated_by_id=$userId
    from attribute_sets_taxon_restrictions astr
    join attribute_sets_surveys ass
      on ass.id=astr.attribute_sets_survey_id
    join attribute_sets aset
      on aset.id=ass.attribute_set_id
    join attribute_sets_taxa_taxon_list_attributes asttla
      on asttla.attribute_set_id=ass.attribute_set_id
    join taxon_lists_taxa_taxon_list_attributes tlttla
      on tlttla.taxon_list_id=aset.taxon_list_id
      and tlttla.taxa_taxon_list_attribute_id=asttla.taxa_taxon_list_attribute_id
    where (astr.deleted or ass.deleted or aset.deleted or asttla.deleted or tlttla.deleted)
    and $alias.id=$model->id
    and atr.taxon_lists_taxa_taxon_list_attribute_id=tlttla.id
    and atr.restrict_to_taxon_meaning_id=astr.restrict_to_taxon_meaning_id
    and coalesce(atr.restrict_to_stage_term_meaning_id, 0)=coalesce(astr.restrict_to_stage_term_meaning_id, 0)
    and atr.deleted=false
    and atr.id not in (
      -- Exclude deletions for any restriction that is still valid because of
      -- another attribute set.
      select atr.id
      from taxa_taxon_list_attribute_taxon_restrictions, attribute_sets_taxon_restrictions astr
      join attribute_sets_surveys ass
        on ass.id=astr.attribute_sets_survey_id
        and ass.deleted=false
      join attribute_sets aset
        on aset.id=ass.attribute_set_id
        and aset.deleted=false
      join attribute_sets_taxa_taxon_list_attributes asttla
        on asttla.attribute_set_id=ass.attribute_set_id
        and asttla.deleted=false
      join taxon_lists_taxa_taxon_list_attributes tlttla
        on tlttla.taxon_list_id=aset.taxon_list_id
        and tlttla.taxa_taxon_list_attribute_id=asttla.taxa_taxon_list_attribute_id
        and tlttla.deleted=false
      where astr.deleted=false
      --AVB changed this in original code
      and atr.taxon_lists_taxa_taxon_list_attribute_id=tlttla.id
      and atr.restrict_to_taxon_meaning_id=astr.restrict_to_taxon_meaning_id
      and coalesce(atr.restrict_to_stage_term_meaning_id, 0)=coalesce(astr.restrict_to_stage_term_meaning_id, 0)
    );
SQL;
    $db->query($ttlQry);
  }

  /**
   * Retrieve the user ID to store in the record metadata.
   *
   * @return integer
   *   User's warehouse user ID.
   */
  private static function getUserId(): int {
    if (isset($_SESSION['auth_user'])) {
      $userId = $_SESSION['auth_user']->id;
    }
    else {
      global $remoteUserId;
      if (isset($remoteUserId)) {
        $userId = $remoteUserId;
      }
      else {
        $defaultUserId = Kohana::config('indicia.defaultPersonId');
        $userId = ($defaultUserId ? $defaultUserId : 1);
      }
    }
    return (int) $userId;
  }

}
