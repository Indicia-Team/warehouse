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
    'attribute_sets_survey' => 'ass',
    'attribute_sets_taxa_taxon_list_attribute' => 'asttla',
    'occurrence_attributes_taxa_taxon_list_attribute' => 'attla',
    'sample_attributes_taxa_taxon_list_attribute' => 'attla',
  ];

  private static $rootEntities = [
    'occurrence_attributes_taxa_taxon_list_attribute' => 'occurrence',
    'sample_attributes_taxa_taxon_list_attribute' => 'samples',
  ];

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
      self::delete($db, $model);
    }
    else {
      self::insertOrUpdate($db, $model);
    }
  }

  /**
   * Changes required after an update or insert.
   *
   * Creates the occurrence_attributes_websites records required to link the
   * attributes in the set to the same website/survey combinations that the set
   * belongs to.
   *
   * @param object $db
   *   Connection object.
   * @param object $model
   *   Instantiated ORM object just inserted or updated.
   */
  private static function insertOrUpdate($db, $model) {
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
insert into {$entity}_attributes_websites (website_id, {$entity}_attribute_id, created_on, created_by_id, restrict_to_survey_id)
select distinct aset.website_id, attla.{$entity}_attribute_id, now(), 1 /*user_id*/, ass.survey_id
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
  private static function delete($db, $model) {
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
  and aset.deleted=false
join attribute_sets_taxa_taxon_list_attributes asttla
  on asttla.attribute_set_id=ass.attribute_set_id
join {$entity}_attributes_taxa_taxon_list_attributes attla
  on attla.taxa_taxon_list_attribute_id=asttla.taxa_taxon_list_attribute_id
  and attla.deleted=false
where aw.website_id=aset.website_id
and coalesce(aw.restrict_to_survey_id, 0)=coalesce(ass.survey_id, 0)
-- Filter one of the options below
and $alias.id=$model->id
-- Reverse the deletion filter for the table we are deleting from
and ass.deleted=case '$alias' when 'ass' then true else false end
and asttla.deleted=case '$alias' when 'asttla' then true else false end
and attla.deleted=case '$alias' when 'attla' then true else false end
and aw.deleted=false
SQL;
      $db->query($qry);
      kohana::log('debug', "delete query: $qry");
    }
  }

}
