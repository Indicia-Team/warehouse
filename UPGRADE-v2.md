# Upgrading to version 2 of the warehouse.

**Before installing version 2 of Indicia, if upgrading from version 1 you
should install the btree_gin extension for PostgreSQL using the following SQL
statement, which requires admin privileges:

```
CREATE EXTENSION btree_gin;
```

* The warehouse upgrade to version 2 is significant and therefore should be
  tested on a copy of your setup before running on live. It may take up to
  several hours to apply if you have millions of occurrence records due to the
  updates required to the reporting cache tables.
* If you have any custom reports, you will need to review them, especially if
  they join to the index_locations_samples table which has been dropped and
  replaced by cache_occurrences_functional.location_ids and
  cache_samples_functional.location_ids, both of which hold an array of
  location IDs for each indexed location that intersects the record. Also note
  the new cache_taxon_paths table and cache_occurrences_functional.taxon_paths
  field used to optimised hierarchical taxonomic indexing.
* Existing client websites should continue to operate as before without
  amendment.
* Although there are significant upgrades to the client helper library code
  version used in the warehouse user interface, this version of the library
  code is not yet recommended for use on client websites.
* If you have a site with a large number of occurrence records and wish to
  minimise the down-time, then you can pre-build the updates required to the
  cache tables using the steps below.
* **Ensure you have a backup of your warehouse database before proceeding!**

1. Take a note of the time on the warehouse system clock.
2. Grab a copy of the version 2 warehouse code for yourself.
3. Run each of the following scripts in sequence, then remove them from your
   copy of the version 2 code base. You can leave the warehouse online whilst
   running these scripts:

   * modules/cache_builder/db/version_2_0_0/201804231959_taxon_path_schema.sql
   * modules/cache_builder/db/version_2_0_0/201804232000_hierarchy_populate.sql
   * modules/cache_builder/db/version_2_0_0/201806051122_attrs_json.sql
   * modules/cache_builder/db/version_2_0_0/201806141347_attrs_json_taxa.sql
   * modules/cache_builder/db/version_2_0_0/201806220938_term_image_paths.sql
   * modules/cache_builder/db/version_2_0_0/201809042100_location_index_field.sql
   * modules/cache_builder/db/version_2_0_0/201809082100_term_allow_data_entry.sql
   * modules/cache_builder/db/version_2_0_0/201811071506_cache_table_new_fields.sql

4. In the following script, replace #master_list_id# with the ID of the
   taxon_list which contains your master taxonomic hierarchy. Run the
   following script which is safe to run on a live warehouse, though may reduce
   performance:

```sql

-- Create a temp table for prebuilt arrays of indexed location_ids. Empty table
-- build if the soatial index builder not installed.
DROP TABLE IF EXISTS loc_ids;

CREATE TEMPORARY TABLE loc_ids (
  sample_id integer,
  location_ids integer[]
  );

CREATE OR REPLACE FUNCTION temp_spatial_index()
RETURNS boolean AS
$BODY$
BEGIN
  BEGIN
    INSERT INTO loc_ids
    SELECT s.id as sample_id, array_agg(distinct ils.location_id) as location_ids
    FROM samples s
    LEFT JOIN index_locations_samples ils ON ils.sample_id=s.id
    WHERE s.deleted=false
    GROUP BY s.id;
  EXCEPTION
    WHEN undefined_table THEN
      BEGIN
        -- Handle case where beta v2 warehouse has already dropped location_ids.
        INSERT INTO loc_ids
        SELECT DISTINCT id, location_ids
        FROM cache_samples_functional;
      EXCEPTION
        WHEN undefined_column THEN
          -- Update from v1 warehouse without spatial index builder.
      END;
  END;
  RETURN true;
END;
$BODY$
  LANGUAGE plpgsql;

SELECT temp_spatial_index();

DROP FUNCTION temp_spatial_index();

CREATE INDEX ix_loc_ids ON loc_ids(sample_id);

SELECT * INTO cache_occurrences_functional_v2 FROM cache_occurrences_functional;

ALTER TABLE cache_occurrences_functional_v2
  ADD CONSTRAINT pk_cache_occurrences_functional_v2 PRIMARY KEY(id);

SELECT * INTO cache_samples_functional_v2 FROM cache_samples_functional;

ALTER TABLE cache_samples_functional_v2
  ADD CONSTRAINT pk_cache_samples_functional_v2 PRIMARY KEY(id);

UPDATE cache_occurrences_functional_v2 o
SET location_ids=CASE l.location_ids WHEN ARRAY[NULL::integer] THEN NULL ELSE l.location_ids END,
  taxon_path=CASE ctp.path WHEN ARRAY[NULL::integer] THEN NULL ELSE ctp.path END,
  blocked_sharing_tasks=
    CASE WHEN u.allow_share_for_reporting
      AND u.allow_share_for_peer_review AND u.allow_share_for_verification
      AND u.allow_share_for_data_flow AND u.allow_share_for_moderation
      AND u.allow_share_for_editing
    THEN null
    ELSE
      ARRAY_REMOVE(ARRAY[
        CASE WHEN u.allow_share_for_reporting=false THEN 'R' ELSE NULL END,
        CASE WHEN u.allow_share_for_peer_review=false THEN 'P' ELSE NULL END,
        CASE WHEN u.allow_share_for_verification=false THEN 'V' ELSE NULL END,
        CASE WHEN u.allow_share_for_data_flow=false THEN 'D' ELSE NULL END,
        CASE WHEN u.allow_share_for_moderation=false THEN 'M' ELSE NULL END,
        CASE WHEN u.allow_share_for_editing=false THEN 'E' ELSE NULL END
      ], NULL)
    END
FROM loc_ids l, users u, cache_taxa_taxon_lists cttl
LEFT JOIN cache_taxa_taxon_lists cttlm
  ON cttlm.external_key=cttl.external_key
  AND cttlm.taxon_list_id=#master_list_id#
  AND cttlm.preferred=true
LEFT JOIN cache_taxon_paths ctp
  ON ctp.taxon_meaning_id=cttlm.taxon_meaning_id
  AND ctp.taxon_list_id=cttlm.taxon_list_id
WHERE l.sample_id=o.sample_id
AND u.id=o.created_by_id
AND cttl.id=o.taxa_taxon_list_id;

UPDATE cache_samples_functional_v2 s
SET location_ids=l.location_ids,
  blocked_sharing_tasks=
    CASE WHEN u.allow_share_for_reporting
      AND u.allow_share_for_peer_review AND u.allow_share_for_verification
      AND u.allow_share_for_data_flow AND u.allow_share_for_moderation
      AND u.allow_share_for_editing
    THEN null
    ELSE
      ARRAY_REMOVE(ARRAY[
        CASE WHEN u.allow_share_for_reporting=false THEN 'R' ELSE NULL END,
        CASE WHEN u.allow_share_for_peer_review=false THEN 'P' ELSE NULL END,
        CASE WHEN u.allow_share_for_verification=false THEN 'V' ELSE NULL END,
        CASE WHEN u.allow_share_for_data_flow=false THEN 'D' ELSE NULL END,
        CASE WHEN u.allow_share_for_moderation=false THEN 'M' ELSE NULL END,
        CASE WHEN u.allow_share_for_editing=false THEN 'E' ELSE NULL END
      ], NULL)
    END
FROM loc_ids l, users u
WHERE l.sample_id=s.id
AND u.id=s.created_by_id;

DROP TABLE loc_ids;

-- Create the indexes.
CREATE INDEX ix_cache_occurrences_functional_v2_created_by_id
  ON cache_occurrences_functional_v2
  USING btree
  (created_by_id);

CREATE INDEX ix_cache_occurrences_functional_v2_date_end
  ON cache_occurrences_functional_v2
  USING btree
  (date_end);

CREATE INDEX ix_cache_occurrences_functional_v2_date_start
  ON cache_occurrences_functional_v2
  USING btree
  (date_start);

CREATE INDEX ix_cache_occurrences_functional_v2_family_taxa_taxon_list_id
  ON cache_occurrences_functional_v2
  USING btree
  (family_taxa_taxon_list_id);

CREATE INDEX ix_cache_occurrences_functional_v2_group_id
  ON cache_occurrences_functional_v2
  USING btree
  (group_id);

CREATE INDEX ix_cache_occurrences_functional_v2_location_id
  ON cache_occurrences_functional_v2
  USING btree
  (location_id);

CREATE INDEX ix_cache_occurrences_functional_v2_map_sq_10km_id
  ON cache_occurrences_functional_v2
  USING btree
  (map_sq_10km_id);

CREATE INDEX ix_cache_occurrences_functional_v2_map_sq_1km_id
  ON cache_occurrences_functional_v2
  USING btree
  (map_sq_1km_id);

CREATE INDEX ix_cache_occurrences_functional_v2_map_sq_2km_id
  ON cache_occurrences_functional_v2
  USING btree
  (map_sq_2km_id);

CREATE INDEX ix_cache_occurrences_functional_v2_public_geom
  ON cache_occurrences_functional_v2
  USING gist
  (public_geom);

CREATE INDEX ix_cache_occurrences_functional_v2_status
  ON cache_occurrences_functional_v2
  USING btree
  (record_status COLLATE pg_catalog."default", record_substatus);

CREATE INDEX ix_cache_occurrences_functional_v2_website_id
  ON cache_occurrences_functional_v2
  USING btree
  (website_id);

CREATE INDEX ix_cache_occurrences_functional_v2_survey_id
  ON cache_occurrences_functional_v2
  USING btree
  (survey_id);

CREATE INDEX ix_cache_occurrences_functional_v2_sample_id
  ON cache_occurrences_functional_v2
  USING btree
  (sample_id);

CREATE INDEX ix_cache_occurrences_functional_v2_taxa_taxon_list_external_key
  ON cache_occurrences_functional_v2
  USING btree
  (taxa_taxon_list_external_key COLLATE pg_catalog."default");

CREATE INDEX ix_cache_occurrences_functional_v2_taxon_group_id
  ON cache_occurrences_functional_v2
  USING btree
  (taxon_group_id);

CREATE INDEX ix_cache_occurrences_functional_v2_updated_on
  ON cache_occurrences_functional_v2
  USING btree
  (updated_on);

CREATE INDEX ix_cache_occurrences_functional_v2_verified_on
  ON cache_occurrences_functional_v2
  USING btree
  (verified_on);

CREATE INDEX ix_cache_samples_functional_v2_created_by_id
  ON cache_samples_functional_v2
  USING btree
  (created_by_id);

CREATE INDEX ix_cache_samples_functional_v2_location_id
  ON cache_samples_functional_v2
  USING btree
  (location_id);

CREATE INDEX ix_cache_samples_functional_v2_map_sq_10km_id
  ON cache_samples_functional_v2
  USING btree
  (map_sq_10km_id);

CREATE INDEX ix_cache_samples_functional_v2_map_sq_1km_id
  ON cache_samples_functional_v2
  USING btree
  (map_sq_1km_id);

CREATE INDEX ix_cache_samples_functional_v2_map_sq_2km_id
  ON cache_samples_functional_v2
  USING btree
  (map_sq_2km_id);

CREATE INDEX ix_cache_samples_functional_v2_website_id
  ON cache_samples_functional_v2
  USING btree
  (website_id);

CREATE INDEX ix_cache_samples_functional_v2_survey_id
  ON cache_samples_functional_v2
  USING btree
  (survey_id);

-- Index to improve performance on WithoutPolygon data cleaning.
CREATE INDEX ix_cache_occurrences_functional_v2_ttl_ext_key_map_sq_v
  ON cache_occurrences_functional_v2(taxa_taxon_list_external_key, map_sq_10km_id)
  WHERE record_status='V';

-- Improve performance of taxon meaning filtering.
CREATE INDEX ix_cache_occurrences_functional_v2_taxon_meaning_id
  ON cache_occurrences_functional_v2(taxon_meaning_id);

-- indexes for the new location_ids fields.
CREATE INDEX ix_cache_occurrences_functional_v2_location_ids
  ON cache_occurrences_functional_v2
  USING GIN(location_ids);

CREATE INDEX ix_cache_samples_functional_v2_location_ids
  ON cache_samples_functional_v2
  USING GIN(location_ids);

-- Index on the array of ancestors for each taxon.
CREATE INDEX ix_cache_occurrences_functional_v2_taxon_path
  ON cache_occurrences_functional_v2
  USING gin
  (taxon_path);

CREATE INDEX ix_cache_samples_functional_v2_public_geom
    ON cache_samples_functional_v2 USING gist
    (public_geom);

```

5. When the script is done and you are ready to proceed with the upgrade,
   notify your users and take your client websites offline.
6. Copy your version of the warehouse over the live version, replacing existing
   files.
7. Delete the following files:
   * modules/cache_builder/db/version_2_0_0/201811071507_cache_table_updates.sql
   * modules/cache_builder/db/version_2_0_0/201811071512_new_cache_indexes.sql
   * modules/spatial_index_builder/db/version_2_0_0/201811141205_drop_index_table.sql
8. Visit the warehouse home page and run the upgrade.
9. Run the following script, replacing #datetime# with the date and time of the
   server system clock before you started (YYYY-MM-DD hh:mm:ss format). Note
   that when you drop the cache_occurrences_functional and
   cache_samples_functional tables all views that refer to the table will need
   to be dropped and recreated afterwards. The script includes DROP and CREATE
   statements for the views built into core, but any custom views will also
   need to be dropped then recreated afterwards. Also, the script includes
   references to indicia_user and indicia_report_user, the PostgreSQL
   connection's username. If your installation uses a different username then
   please replace it in the script. You should also check any users which have
   special grants on any of the views you drop (including the 4 core views in
   the script) and recreate those grants afterwards, for example if select
   access is granted to indicia_report_user.

```sql
ALTER TABLE cache_taxon_paths OWNER TO indicia_user;

-- This forces any new records to rebuild in the cache.
UPDATE system SET last_scheduled_task_check='#datetime#' WHERE name='cache_builder';

-- Ensure inserts since we started are in new cache.
INSERT INTO cache_occurrences_functional_v2
SELECT o.* FROM cache_occurrences_functional o
LEFT JOIN cache_occurrences_functional_v2 o2 ON o2.id=o.id
WHERE o2.id IS NULL
AND o.updated_on > '#datetime#';

-- Plus ensure any updates and deletes are applied.
INSERT INTO work_queue(task, entity, record_id,
    params, cost_estimate, priority, created_on)
  SELECT 'task_cache_builder_update', 'occurrence', id,
    CASE deleted WHEN true THEN '{"deleted":true}'::json ELSE NULL END,
    100, 2, now()
  FROM occurrences
  WHERE updated_on>'#datetime#'
  ORDER BY id;

DROP VIEW cache_occurrences;
DROP VIEW detail_occurrence_associations;
DROP VIEW list_occurrence_associations;

DROP TABLE cache_occurrences_functional;
ALTER TABLE cache_occurrences_functional_v2 RENAME TO cache_occurrences_functional;
ALTER TABLE cache_occurrences_functional OWNER TO indicia_user;
GRANT SELECT ON cache_occurrences_functional TO indicia_report_user;

CREATE OR REPLACE VIEW cache_occurrences AS
SELECT o.id,
  o.record_status,
  o.zero_abundance,
  o.website_id,
  o.survey_id,
  o.sample_id,
  snf.survey_title,
  snf.website_title,
  o.date_start,
  o.date_end,
  o.date_type,
  snf.public_entered_sref,
  snf.entered_sref_system,
  o.public_geom,
  o.taxa_taxon_list_id,
  cttl.preferred_taxa_taxon_list_id,
  cttl.taxonomic_sort_order,
  cttl.taxon,
  cttl.authority,
  cttl.preferred_taxon,
  cttl.preferred_authority,
  cttl.default_common_name,
  cttl.external_key as taxa_taxon_list_external_key,
  cttl.taxon_meaning_id,
  cttl.taxon_group_id,
  cttl.taxon_group,
  o.created_by_id,
  o.created_on as cache_created_on,
  o.updated_on as cache_updated_on,
  o.certainty,
  o.location_name,
  snf.recorders,
  onf.verifier,
  onf.media as images,
  o.training,
  o.location_id,
  o.input_form,
  o.data_cleaner_result,
  onf.data_cleaner_info,
  o.release_status,
  o.verified_on,
  onf.sensitivity_precision,
  o.map_sq_1km_id,
  o.map_sq_2km_id,
  o.map_sq_10km_id,
  o.group_id,
  onf.privacy_precision,
  onf.output_sref,
  o.record_substatus,
  o.query,
  o.licence_id,
  onf.licence_code,
  o.family_taxa_taxon_list_id,
  onf.attr_sex,
  onf.attr_stage,
  onf.attr_sex_stage,
  onf.attr_sex_stage_count,
  onf.attr_certainty,
  onf.attr_det_first_name,
  onf.attr_det_last_name,
  onf.attr_det_full_name,
  snf.attr_email,
  snf.attr_cms_user_id,
  snf.attr_cms_username,
  snf.attr_first_name,
  snf.attr_last_name,
  snf.attr_full_name,
  snf.attr_biotope,
  snf.attr_sref_precision,
  o.confidential,
  o.location_ids,
  o.taxon_path,
  o.blocked_sharing_tasks
  FROM cache_occurrences_functional o
  JOIN cache_occurrences_nonfunctional onf on onf.id=o.id
  JOIN cache_samples_nonfunctional snf on snf.id=o.sample_id
  JOIN cache_taxa_taxon_lists cttl on cttl.id=o.taxa_taxon_list_id;

CREATE OR REPLACE VIEW list_occurrence_associations AS
select
  oa.id, oa.from_occurrence_id, oa.to_occurrence_id, cttlfrom.taxon as from_taxon, cttlto.taxon as to_taxon, cofrom.sample_id,
  oa.association_type_id, atype.term as association_type, oa.part_id, part.term as part,
  oa.position_id, pos.term as position, oa.impact_id, impact.term as impact, cofrom.website_id
from occurrence_associations oa
join cache_occurrences_functional cofrom on cofrom.id=oa.from_occurrence_id
join cache_occurrences_functional coto on coto.id=oa.to_occurrence_id
join cache_taxa_taxon_lists cttlfrom on cttlfrom.id=cofrom.taxa_taxon_list_id
join cache_taxa_taxon_lists cttlto on cttlto.id=coto.taxa_taxon_list_id
join cache_termlists_terms atype on atype.id=oa.association_type_id
left join cache_termlists_terms part on part.id=oa.part_id
left join cache_termlists_terms pos on pos.id=oa.position_id
left join cache_termlists_terms impact on impact.id=oa.impact_id
where oa.deleted=false;

CREATE OR REPLACE VIEW detail_occurrence_associations AS
select
  oa.id, oa.from_occurrence_id, oa.to_occurrence_id, cttlfrom.taxon as from_taxon, cttlto.taxon as to_taxon, cofrom.sample_id,
  oa.association_type_id, atype.term as association_type, oa.part_id, part.term as part,
  oa.position_id, pos.term as position, oa.impact_id, impact.term as impact, oa.comment,
  oa.created_by_id, c.username AS created_by, oa.updated_by_id, u.username AS updated_by, cofrom.website_id
from occurrence_associations oa
join cache_occurrences_functional cofrom on cofrom.id=oa.from_occurrence_id
join cache_occurrences_functional coto on coto.id=oa.to_occurrence_id
join cache_taxa_taxon_lists cttlfrom on cttlfrom.id=cofrom.taxa_taxon_list_id
join cache_taxa_taxon_lists cttlto on cttlto.id=coto.taxa_taxon_list_id
join cache_termlists_terms atype on atype.id=oa.association_type_id
left join cache_termlists_terms part on part.id=oa.part_id
left join cache_termlists_terms pos on pos.id=oa.position_id
left join cache_termlists_terms impact on impact.id=oa.impact_id
JOIN users c ON c.id = oa.created_by_id
JOIN users u ON u.id = oa.updated_by_id
where oa.deleted=false;

ALTER VIEW cache_occurrences OWNER TO indicia_user;
ALTER VIEW list_occurrence_associations OWNER TO indicia_user;
ALTER VIEW detail_occurrence_associations OWNER TO indicia_user;
GRANT SELECT ON cache_occurrences TO indicia_report_user;
GRANT SELECT ON list_occurrence_associations TO indicia_report_user;
GRANT SELECT ON detail_occurrence_associations TO indicia_report_user;

DROP TABLE cache_samples_functional;
ALTER TABLE cache_samples_functional_v2 RENAME TO cache_samples_functional;
ALTER TABLE cache_samples_functional OWNER TO indicia_user;
GRANT SELECT ON cache_samples_functional TO indicia_report_user;

ALTER TABLE cache_occurrences_functional
  RENAME CONSTRAINT pk_cache_occurrences_functional_v2 TO pk_cache_occurrences_functional;

ALTER TABLE cache_samples_functional
  RENAME CONSTRAINT pk_cache_samples_functional_v2 TO pk_cache_samples_functional;

ALTER INDEX ix_cache_occurrences_functional_v2_created_by_id RENAME TO ix_cache_occurrences_functional_created_by_id;
ALTER INDEX ix_cache_occurrences_functional_v2_date_end RENAME TO ix_cache_occurrences_functional_date_end;
ALTER INDEX ix_cache_occurrences_functional_v2_date_start RENAME TO ix_cache_occurrences_functional_date_start;
ALTER INDEX ix_cache_occurrences_functional_v2_family_taxa_taxon_list_id RENAME TO ix_cache_occurrences_functional_family_taxa_taxon_list_id;
ALTER INDEX ix_cache_occurrences_functional_v2_group_id RENAME TO ix_cache_occurrences_functional_group_id;
ALTER INDEX ix_cache_occurrences_functional_v2_location_id RENAME TO ix_cache_occurrences_functional_location_id;
ALTER INDEX ix_cache_occurrences_functional_v2_map_sq_10km_id RENAME TO ix_cache_occurrences_functional_map_sq_10km_id;
ALTER INDEX ix_cache_occurrences_functional_v2_map_sq_1km_id RENAME TO ix_cache_occurrences_functional_map_sq_1km_id;
ALTER INDEX ix_cache_occurrences_functional_v2_map_sq_2km_id RENAME TO ix_cache_occurrences_functional_map_sq_2km_id;
ALTER INDEX ix_cache_occurrences_functional_v2_public_geom RENAME TO ix_cache_occurrences_functional_public_geom;
ALTER INDEX ix_cache_occurrences_functional_v2_status RENAME TO ix_cache_occurrences_functional_status;
ALTER INDEX ix_cache_occurrences_functional_v2_website_id RENAME TO ix_cache_occurrences_functional_website_id;
ALTER INDEX ix_cache_occurrences_functional_v2_survey_id RENAME TO ix_cache_occurrences_functional_survey_id;
ALTER INDEX ix_cache_occurrences_functional_v2_sample_id RENAME TO ix_cache_occurrences_functional_sample_id;
ALTER INDEX ix_cache_occurrences_functional_v2_taxa_taxon_list_external_key RENAME TO ix_cache_occurrences_functional_taxa_taxon_list_external_key;
ALTER INDEX ix_cache_occurrences_functional_v2_taxon_group_id RENAME TO ix_cache_occurrences_functional_taxon_group_id;
ALTER INDEX ix_cache_occurrences_functional_v2_updated_on RENAME TO ix_cache_occurrences_functional_updated_on;
ALTER INDEX ix_cache_occurrences_functional_v2_verified_on RENAME TO ix_cache_occurrences_functional_verified_on;
ALTER INDEX ix_cache_samples_functional_v2_created_by_id RENAME TO ix_cache_samples_functional_created_by_id;
ALTER INDEX ix_cache_samples_functional_v2_location_id RENAME TO ix_cache_samples_functional_location_id;
ALTER INDEX ix_cache_samples_functional_v2_map_sq_10km_id RENAME TO ix_cache_samples_functional_map_sq_10km_id;
ALTER INDEX ix_cache_samples_functional_v2_map_sq_1km_id RENAME TO ix_cache_samples_functional_map_sq_1km_id;
ALTER INDEX ix_cache_samples_functional_v2_map_sq_2km_id RENAME TO ix_cache_samples_functional_map_sq_2km_id;
ALTER INDEX ix_cache_samples_functional_v2_website_id RENAME TO ix_cache_samples_functional_website_id;
ALTER INDEX ix_cache_samples_functional_v2_survey_id RENAME TO ix_cache_samples_functional_survey_id;
ALTER INDEX ix_cache_occurrences_functional_v2_ttl_ext_key_map_sq_v RENAME TO ix_cache_occurrences_functional_ttl_ext_key_map_sq_v;
ALTER INDEX ix_cache_occurrences_functional_v2_taxon_meaning_id RENAME TO ix_cache_occurrences_functional_taxon_meaning_id;
ALTER INDEX ix_cache_occurrences_functional_v2_location_ids RENAME TO ix_cache_occurrences_functional_location_ids;
ALTER INDEX ix_cache_samples_functional_v2_location_ids RENAME TO ix_cache_samples_functional_location_ids;
ALTER INDEX ix_cache_occurrences_functional_v2_taxon_path RENAME TO ix_cache_occurrences_functional_taxon_path;
ALTER INDEX ix_cache_samples_functional_v2_public_geom RENAME TO ix_cache_samples_functional_public_geom;

CREATE TRIGGER delete_quick_reply_auth_trigger
  AFTER UPDATE
  ON cache_occurrences_functional
  FOR EACH ROW
  EXECUTE PROCEDURE delete_quick_reply_auth();

   ```

10. Make sure the scripts mentioned in #3 are removed from your warehouse, then visit
    index.php/home/upgrade on the website and run the upgrade scripts.
11. Go back online.
12. Drop the table index_locations_samples if and when you are sure that any
    custom reports that refer to this table have been updated to use the new
    location_ids fields.