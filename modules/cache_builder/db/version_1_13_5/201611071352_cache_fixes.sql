-- #slow script#

CREATE TEMPORARY TABLE cache_occurrences_updates (
  id INTEGER NOT NULL,
  deleted BOOLEAN DEFAULT false,
  updated_on TIMESTAMP WITHOUT TIME ZONE,
  media CHARACTER VARYING,
  privacy_precision INTEGER,
  output_sref CHARACTER VARYING,
  licence_code CHARACTER VARYING,
  input_form CHARACTER VARYING,
  location_id INTEGER,
  location_name CHARACTER VARYING,
  public_geom geometry(Geometry,900913),
  date_start DATE,
  date_end DATE,
  date_type CHARACTER VARYING(2),
  preferred_taxa_taxon_list_id INTEGER,
  taxon_meaning_id INTEGER,
  taxa_taxon_list_external_key CHARACTER VARYING(50),
  family_taxa_taxon_list_id INTEGER,
  taxon_group_id INTEGER,
  taxon_rank_sort_order INTEGER,
  marine_flag BOOLEAN,
  media_count INTEGER DEFAULT 0,
  licence_id INTEGER,
  CONSTRAINT pk_cache_occurrences_updates_nonfunctional PRIMARY KEY (id)
);

-- Grab a list of all changed records which might not have been reflected in the occurrence cache
INSERT INTO cache_occurrences_updates (id, updated_on)
SELECT DISTINCT sub.id, now()
    FROM (
    SELECT o.id, s.deleted
    FROM cache_occurrences_functional o
    JOIN samples s ON s.id=o.sample_id
    WHERE s.updated_on>o.updated_on
    UNION
    SELECT o.id, sp.deleted
    FROM cache_occurrences_functional o
    JOIN samples s ON s.id=o.sample_id
    JOIN samples sp ON sp.id=s.parent_id
    WHERE sp.updated_on>o.updated_on
    UNION
    SELECT o.id, false
    FROM cache_occurrences_functional o
    JOIN samples s ON s.id=o.sample_id
    JOIN locations l ON l.id=s.location_id
    WHERE l.updated_on>o.updated_on
    UNION
    SELECT o.id, su.deleted
    FROM cache_occurrences_functional o
    JOIN samples s ON s.id=o.sample_id
    JOIN surveys su ON su.id=s.survey_id
    WHERE su.updated_on>o.updated_on
    UNION
    SELECT o.id, false
    FROM cache_occurrences_functional o
    JOIN taxa_taxon_lists ttl ON ttl.id=o.taxa_taxon_list_id
    WHERE ttl.updated_on>o.updated_on
    UNION
    SELECT o.id, false
    FROM cache_occurrences_functional o
    JOIN occurrence_media om ON om.occurrence_id=o.id
    WHERE om.updated_on>o.updated_on
    UNION
    SELECT o.id, false
    FROM cache_occurrences_functional o
    JOIN occurrence_comments oc ON oc.occurrence_id=o.id
    WHERE oc.auto_generated=false AND oc.updated_on>o.updated_on
    ) as sub;

UPDATE cache_occurrences_updates u
SET deleted=outersub.deleted
FROM (SELECT id, cast(max(cast(deleted as int)) as BOOLEAN) as deleted FROM (
    SELECT o.id, s.deleted
    FROM cache_occurrences_functional o
    JOIN samples s ON s.id=o.sample_id
    UNION
    SELECT o.id, sp.deleted
    FROM cache_occurrences_functional o
    JOIN samples s ON s.id=o.sample_id
    JOIN samples sp ON sp.id=s.parent_id
    UNION
    SELECT o.id, false
    FROM cache_occurrences_functional o
    JOIN samples s ON s.id=o.sample_id
    JOIN locations l ON l.id=s.location_id
    UNION
    SELECT o.id, su.deleted
    FROM cache_occurrences_functional o
    JOIN samples s ON s.id=o.sample_id
    JOIN surveys su ON su.id=s.survey_id
    UNION
    SELECT o.id, ttl.deleted
    FROM cache_occurrences_functional o
    JOIN taxa_taxon_lists ttl ON ttl.id=o.taxa_taxon_list_id
    UNION
    SELECT o.id, false
    FROM cache_occurrences_functional o
    JOIN occurrence_media om ON om.occurrence_id=o.id
    UNION
    SELECT o.id, false
    FROM cache_occurrences_functional o
    JOIN occurrence_comments oc ON oc.occurrence_id=o.id
    WHERE oc.auto_generated=false
) AS innersub GROUP BY id) as outersub
WHERE outersub.id=u.id;

-- Do deletions first to save unnecessary work later
DELETE FROM cache_occurrences_functional WHERE id IN (SELECT id FROM cache_occurrences_updates WHERE deleted=TRUE);
DELETE FROM cache_occurrences_nonfunctional WHERE id IN (SELECT id FROM cache_occurrences_updates WHERE deleted=TRUE);
DELETE FROM cache_occurrences_updates WHERE deleted=TRUE;

UPDATE cache_occurrences_updates u SET
  privacy_precision=s.privacy_precision,
  output_sref=get_output_sref(
      case when o.confidential=true or o.sensitivity_precision is not null or s.privacy_precision is not null then null else
      case
        when s.entered_sref_system = '4326' and coalesce(s.entered_sref, l.centroid_sref) ~ '^-?[0-9]*\.[0-9]*,[ ]*-?[0-9]*\.[0-9]*' then
          abs(round(((string_to_array(coalesce(s.entered_sref, l.centroid_sref), ','))[1])::numeric, 3))::varchar
          || case when ((string_to_array(coalesce(s.entered_sref, l.centroid_sref), ','))[1])::float>0 then 'N' else 'S' end
          || ', '
          || abs(round(((string_to_array(coalesce(s.entered_sref, l.centroid_sref), ','))[2])::numeric, 3))::varchar
          || case when ((string_to_array(coalesce(s.entered_sref, l.centroid_sref), ','))[2])::float>0 then 'E' else 'W' end
        when s.entered_sref_system = '4326' and coalesce(s.entered_sref, l.centroid_sref) ~ '^-?[0-9]*\.[0-9]*[NS](, |[, ])*-?[0-9]*\.[0-9]*[EW]' then
          abs(round(((regexp_split_to_array(coalesce(s.entered_sref, l.centroid_sref), '([NS](, |[, ]))|[EW]'))[1])::numeric, 3))::varchar
          || case when coalesce(s.entered_sref, l.centroid_sref) like '%N%' then 'N' else 'S' end
          || ', '
          || abs(round(((regexp_split_to_array(coalesce(s.entered_sref, l.centroid_sref), '([NS](, |[, ]))|[EW]'))[2])::numeric, 3))::varchar
          || case when coalesce(s.entered_sref, l.centroid_sref) like '%E%' then 'E' else 'W' end
      else
        coalesce(s.entered_sref, l.centroid_sref)
      end
    end,
    case when s.entered_sref_system is null then l.centroid_sref_system else s.entered_sref_system end,
    greatest(
      round(sqrt(st_area(st_transform(s.geom, sref_system_to_srid(s.entered_sref_system)))))::integer,
      o.sensitivity_precision,
      s.privacy_precision,
      -- work out best square size to reflect a lat long's true precision
      case
        when coalesce(spv.int_value, spv.float_value)>=501 then 10000
        when coalesce(spv.int_value, spv.float_value) between 51 and 500 then 1000
        when coalesce(spv.int_value, spv.float_value) between 6 and 50 then 100
        else 10
      end,
      10 -- default minimum square size
    ), reduce_precision(coalesce(s.geom, l.centroid_geom), o.confidential, greatest(o.sensitivity_precision, s.privacy_precision),
    case when s.entered_sref_system is null then l.centroid_sref_system else s.entered_sref_system end)
  ),
  licence_code=li.code,
  licence_id=li.id,
  input_form=COALESCE(sp.input_form, s.input_form),
  location_id= s.location_id,
  location_name=CASE WHEN s.privacy_precision IS NOT NULL THEN NULL ELSE COALESCE(l.name, s.location_name, lp.name, sp.location_name) END,
  public_geom=reduce_precision(coalesce(s.geom, l.centroid_geom), false, s.privacy_precision,
        case when s.entered_sref_system is null then l.centroid_sref_system else s.entered_sref_system end),
  date_start=s.date_start,
  date_end=s.date_end,
  date_type=s.date_type,
  preferred_taxa_taxon_list_id=cttl.preferred_taxa_taxon_list_id,
  taxon_meaning_id=cttl.taxon_meaning_id,
  taxa_taxon_list_external_key=cttl.external_key,
  family_taxa_taxon_list_id=cttl.family_taxa_taxon_list_id,
  taxon_group_id=cttl.taxon_group_id,
  taxon_rank_sort_order=cttl.taxon_rank_sort_order,
  marine_flag=cttl.marine_flag
FROM occurrences o
JOIN samples s ON s.id=o.sample_id AND s.deleted=false
LEFT JOIN samples sp ON sp.id=s.parent_id AND  sp.deleted=false
LEFT JOIN locations l ON l.id=s.location_id AND l.deleted=false
LEFT JOIN locations lp ON lp.id=sp.location_id AND lp.deleted=false
LEFT JOIN (sample_attribute_values spv
  JOIN sample_attributes spa on spa.id=spv.sample_attribute_id and spa.deleted=false
      and spa.system_function='sref_precision'
) on spv.sample_id=s.id and spv.deleted=false
JOIN cache_taxa_taxon_lists cttl ON cttl.id=o.taxa_taxon_list_id
LEFT JOIN licences li on li.id=s.licence_id
WHERE o.id=u.id AND u.deleted=FALSE;

UPDATE cache_occurrences_updates u
SET media_count=(SELECT COUNT(om.*)
FROM occurrence_media om WHERE om.occurrence_id=o.id AND om.deleted=false)
FROM occurrences o
WHERE o.id=u.id;

UPDATE cache_occurrences_updates o
SET media=(SELECT array_to_string(array_agg(om.path), ',')
FROM occurrence_media om WHERE om.occurrence_id=o.id AND om.deleted=false)
FROM occurrences occ
WHERE occ.id=o.id
AND occ.deleted=false;

UPDATE cache_occurrences_nonfunctional o SET
  media=u.media,
  privacy_precision=u.privacy_precision,
  output_sref=u.output_sref,
  licence_code=u.licence_code
FROM cache_occurrences_updates u
WHERE u.id=o.id
AND (
  COALESCE(o.media, '')<>COALESCE(u.media, '')
  OR COALESCE(o.privacy_precision, -1)<>COALESCE(u.privacy_precision, -1)
  OR o.output_sref<>u.output_sref
  OR COALESCE(o.licence_code, '')<>COALESCE(u.licence_code, '')
);

UPDATE cache_occurrences_functional o SET
  input_form=u.input_form,
  location_id=u.location_id,
  location_name=u.location_name,
  public_geom=u.public_geom,
  date_start=u.date_start,
  date_end=u.date_end,
  date_type=u.date_type,
  preferred_taxa_taxon_list_id=u.preferred_taxa_taxon_list_id,
  taxon_meaning_id=u.taxon_meaning_id,
  taxa_taxon_list_external_key=u.taxa_taxon_list_external_key,
  family_taxa_taxon_list_id=u.family_taxa_taxon_list_id,
  taxon_group_id=u.taxon_group_id,
  taxon_rank_sort_order=u.taxon_rank_sort_order,
  marine_flag=u.marine_flag,
  media_count=u.media_count,
  licence_id=u.licence_id
FROM cache_occurrences_updates u
WHERE u.id=o.id
AND (
  COALESCE(o.input_form, '')<>COALESCE(u.input_form, '')
  OR COALESCE(o.location_id, 0)<>COALESCE(u.location_id, 0)
  OR COALESCE(o.location_name, '')<>COALESCE(u.location_name, '')
  OR NOT o.public_geom=u.public_geom
  OR COALESCE(o.date_start, '1000-01-01'::date)<>COALESCE(u.date_start, '1000-01-01'::date)
  OR COALESCE(o.date_end, '1000-01-01'::date)<>COALESCE(u.date_end, '1000-01-01'::date)
  OR COALESCE(o.date_type, '')<>COALESCE(u.date_type, '')
  OR o.preferred_taxa_taxon_list_id<>u.preferred_taxa_taxon_list_id
  OR o.taxon_meaning_id<>u.taxon_meaning_id
  OR COALESCE(o.taxa_taxon_list_external_key, '')<>COALESCE(u.taxa_taxon_list_external_key, '')
  OR COALESCE(o.family_taxa_taxon_list_id, 0)<>COALESCE(u.family_taxa_taxon_list_id, 0)
  OR o.taxon_group_id<>u.taxon_group_id
  OR COALESCE(o.taxon_rank_sort_order, 0)<>COALESCE(u.taxon_rank_sort_order, 0)
  OR o.marine_flag<>u.marine_flag
  OR o.media_count<>u.media_count
  OR COALESCE(o.licence_id, 0)<>COALESCE(u.licence_id, 0)
);