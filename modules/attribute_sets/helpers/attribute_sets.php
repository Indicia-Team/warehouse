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
 * @author Indicia Team
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
    if ($allowRanges) {
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
        self::deleteAttributesWebsites($db, $model);
      }
      self::deleteAttributesTaxonRestriction($db, $model);
    }
    else {
      if ($model->object_name !== 'attribute_sets_taxon_restriction') {
        self::insertOrUpdateAttributesWebsites($db, $model);
      }
      self::insertOrUpdateAttributesTaxonRestriction($db, $model);
    }
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
  private static function insertOrUpdateAttributesWebsites($db, $model) {
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
   * required because of an update to an attribute set..
   *
   * @param object $db
   *   Connection object.
   * @param object $model
   *   Instantiated ORM object just inserted or updated.
   */
  private static function deleteAttributesWebsites($db, $model) {
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
where aw.website_id=aset.website_id
and coalesce(aw.restrict_to_survey_id, 0)=coalesce(ass.survey_id, 0)
-- Filter one of the options below
and $alias.id=$model->id
-- Reverse the deletion filter for the table we are deleting from
and aset.deleted=case '$alias' when 'aset' then true else false end
and ass.deleted=case '$alias' when 'ass' then true else false end
and asttla.deleted=case '$alias' when 'asttla' then true else false end
and attla.deleted=case '$alias' when 'attla' then true else false end
and aw.deleted=false
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
  private static function insertOrUpdateAttributesTaxonRestriction($db, $model) {
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
  and attla.deleted=false
where atr.restrict_to_taxon_meaning_id=astr.restrict_to_taxon_meaning_id
and coalesce(atr.restrict_to_stage_term_meaning_id, 0)=coalesce(astr.restrict_to_stage_term_meaning_id, 0)
-- Filter one of the options below
and $alias.id=$model->id
-- Reverse the deletion filter for the table we are deleting from
and aset.deleted=case '$alias' when 'aset' then true else false end
and astr.deleted=case '$alias' when 'atr' then true else false end
and ass.deleted=case '$alias' when 'ass' then true else false end
and asttla.deleted=case '$alias' when 'asttla' then true else false end
and attla.deleted=case '$alias' when 'attla' then true else false end
and atr.deleted=false
SQL;
      $db->query($qry);
    }
  }

  /**
   * Retrieve the user ID to store in the record metadata.
   *
   * @return integer
   *   User's warehouse user ID.
   */
  private static function getUserId() {
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
    return $userId;
  }

}
