-- #slow script#
INSERT INTO cache_occurrences_functional(
            id, sample_id, website_id, survey_id, input_form, location_id,
            location_name, public_geom,
            date_start, date_end, date_type, created_on, updated_on, verified_on,
            created_by_id, group_id, taxa_taxon_list_id, preferred_taxa_taxon_list_id,
            taxon_meaning_id, taxa_taxon_list_external_key, family_taxa_taxon_list_id,
            taxon_group_id, taxon_rank_sort_order, record_status, record_substatus,
            certainty, query, sensitive, release_status, marine_flag, data_cleaner_result,
            training, zero_abundance, licence_id)
SELECT distinct on (o.id) o.id, o.sample_id, o.website_id, s.survey_id, s.input_form, s.location_id,
    case when o.confidential=true or o.sensitivity_precision is not null or s.privacy_precision is not null
        then null else coalesce(l.name, s.location_name, lp.name, sp.location_name) end,
    reduce_precision(coalesce(s.geom, l.centroid_geom), o.confidential, greatest(o.sensitivity_precision, s.privacy_precision),
        case when s.entered_sref_system is null then l.centroid_sref_system else s.entered_sref_system end) as public_geom,
    s.date_start, s.date_end, s.date_type, o.created_on, o.updated_on, o.verified_on,
    o.created_by_id, s.group_id, o.taxa_taxon_list_id, cttl.preferred_taxa_taxon_list_id,
    cttl.taxon_meaning_id, cttl.external_key, cttl.family_taxa_taxon_list_id,
    cttl.taxon_group_id, cttl.taxon_rank_sort_order, o.record_status, o.record_substatus,
    case when certainty.sort_order is null then null
        when certainty.sort_order <100 then 'C'
        when certainty.sort_order <200 then 'L'
        else 'U'
    end,
    case
        when oc1.id is null then null
        when oc2.id is null and o.updated_on<=oc1.created_on then 'Q'
        else 'A'
    end,
    o.sensitivity_precision is not null, o.release_status, cttl.marine_flag,
    case when o.last_verification_check_date is null then null else dc.id is null end,
    o.training, o.zero_abundance, s.licence_id
FROM occurrences o
JOIN samples s ON s.id=o.sample_id AND s.deleted=false
LEFT JOIN samples sp ON sp.id=s.parent_id AND  sp.deleted=false
LEFT JOIN locations l ON l.id=s.location_id AND l.deleted=false
LEFT JOIN locations lp ON lp.id=sp.location_id AND lp.deleted=false
JOIN cache_taxa_taxon_lists cttl ON cttl.id=o.taxa_taxon_list_id
LEFT JOIN (occurrence_attribute_values oav
    JOIN termlists_terms certainty ON certainty.id=oav.int_value
    JOIN occurrence_attributes oa ON oa.id=oav.occurrence_attribute_id and oa.deleted='f' and oa.system_function='certainty'
  ) ON oav.occurrence_id=o.id AND oav.deleted='f'
LEFT JOIN occurrence_comments oc1 ON oc1.occurrence_id=o.id AND oc1.deleted=false AND oc1.auto_generated=false
    AND oc1.query=true AND (o.verified_on IS NULL OR oc1.created_on>o.verified_on)
LEFT JOIN occurrence_comments oc2 ON oc2.occurrence_id=o.id AND oc2.deleted=false AND oc2.auto_generated=false
    AND oc2.query=false AND (o.verified_on IS NULL OR oc2.created_on>o.verified_on) AND oc2.id>oc1.id
LEFT JOIN occurrence_comments dc
    ON dc.occurrence_id=o.id
    AND dc.implies_manual_check_required=true
    AND dc.deleted=false
WHERE o.deleted=false;

UPDATE cache_occurrences_functional u
SET media_count=(SELECT COUNT(om.*)
FROM occurrence_media om WHERE om.occurrence_id=u.id AND om.deleted=false)
FROM occurrences o
WHERE o.id=u.id;

INSERT INTO cache_occurrences_nonfunctional(
            id, comment, sensitivity_precision, privacy_precision, output_sref, verifier, licence_code)
SELECT o.id,
  o.comment, o.sensitivity_precision,
  s.privacy_precision,
  get_output_sref(
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
  pv.surname || ', ' || pv.first_name,
  li.code
FROM occurrences o
JOIN samples s ON s.id=o.sample_id AND s.deleted=false
LEFT JOIN samples sp ON sp.id=s.parent_id AND  sp.deleted=false
LEFT JOIN locations l ON l.id=s.location_id AND l.deleted=false
LEFT JOIN locations lp ON lp.id=sp.location_id AND lp.deleted=false
LEFT JOIN users uv on uv.id=o.verified_by_id and uv.deleted=false
LEFT JOIN people pv on pv.id=uv.person_id and pv.deleted=false
LEFT JOIN licences li on li.id=s.licence_id
LEFT JOIN (sample_attribute_values spv
  JOIN sample_attributes spa on spa.id=spv.sample_attribute_id and spa.deleted=false
      and spa.system_function='sref_precision'
) on spv.sample_id=s.id and spv.deleted=false
WHERE o.deleted=false;

UPDATE cache_occurrences_nonfunctional
SET
  attr_sex_stage=CASE a_sex_stage.data_type
      WHEN 'T'::bpchar THEN v_sex_stage.text_value
      WHEN 'L'::bpchar THEN t_sex_stage.term
      ELSE NULL::text
  END,
  attr_sex=CASE a_sex.data_type
      WHEN 'T'::bpchar THEN v_sex.text_value
      WHEN 'L'::bpchar THEN t_sex.term
      ELSE NULL::text
  END,
  attr_stage=CASE a_stage.data_type
      WHEN 'T'::bpchar THEN v_stage.text_value
      WHEN 'L'::bpchar THEN t_stage.term
      ELSE NULL::text
  END,
  attr_sex_stage_count=CASE a_sex_stage_count.data_type
      WHEN 'T'::bpchar THEN v_sex_stage_count.text_value
      WHEN 'L'::bpchar THEN t_sex_stage_count.term
      WHEN 'I'::bpchar THEN v_sex_stage_count.int_value::text
      WHEN 'F'::bpchar THEN v_sex_stage_count.float_value::text
      ELSE NULL::text
  END,
  attr_certainty=CASE a_certainty.data_type
      WHEN 'T'::bpchar THEN v_certainty.text_value
      WHEN 'L'::bpchar THEN t_certainty.term
      WHEN 'I'::bpchar THEN v_certainty.int_value::text
      WHEN 'B'::bpchar THEN v_certainty.int_value::text
      WHEN 'F'::bpchar THEN v_certainty.float_value::text
      ELSE NULL::text
  END,
  attr_det_first_name=CASE a_det_first_name.data_type
      WHEN 'T'::bpchar THEN v_det_first_name.text_value
      ELSE NULL::text
  END,
  attr_det_last_name=CASE a_det_last_name.data_type
      WHEN 'T'::bpchar THEN v_det_last_name.text_value
      ELSE NULL::text
  END,
  attr_det_full_name=CASE a_det_full_name.data_type
      WHEN 'T'::bpchar THEN v_det_full_name.text_value
      ELSE NULL::text
  END
FROM occurrences o
LEFT JOIN (occurrence_attribute_values v_sex_stage
  JOIN occurrence_attributes a_sex_stage on a_sex_stage.id=v_sex_stage.occurrence_attribute_id and a_sex_stage.deleted=false and a_sex_stage.system_function='sex_stage'
  LEFT JOIN cache_termlists_terms t_sex_stage on a_sex_stage.data_type='L' and t_sex_stage.id=v_sex_stage.int_value
) on v_sex_stage.occurrence_id=o.id and v_sex_stage.deleted=false
LEFT JOIN (occurrence_attribute_values v_sex
  JOIN occurrence_attributes a_sex on a_sex.id=v_sex.occurrence_attribute_id and a_sex.deleted=false and a_sex.system_function='sex'
  LEFT JOIN cache_termlists_terms t_sex on a_sex.data_type='L' and t_sex.id=v_sex.int_value
) on v_sex.occurrence_id=o.id and v_sex.deleted=false
LEFT JOIN (occurrence_attribute_values v_stage
  JOIN occurrence_attributes a_stage on a_stage.id=v_stage.occurrence_attribute_id and a_stage.deleted=false and a_stage.system_function='stage'
  LEFT JOIN cache_termlists_terms t_stage on a_stage.data_type='L' and t_stage.id=v_stage.int_value
) on v_stage.occurrence_id=o.id and v_stage.deleted=false
LEFT JOIN (occurrence_attribute_values v_sex_stage_count
  JOIN occurrence_attributes a_sex_stage_count on a_sex_stage_count.id=v_sex_stage_count.occurrence_attribute_id and a_sex_stage_count.deleted=false and a_sex_stage_count.system_function='sex_stage_count'
  LEFT JOIN cache_termlists_terms t_sex_stage_count on a_sex_stage_count.data_type='L' and t_sex_stage_count.id=v_sex_stage_count.int_value
) on v_sex_stage_count.occurrence_id=o.id and v_sex_stage_count.deleted=false
LEFT JOIN (occurrence_attribute_values v_certainty
  JOIN occurrence_attributes a_certainty on a_certainty.id=v_certainty.occurrence_attribute_id and a_certainty.deleted=false and a_certainty.system_function='certainty'
  LEFT JOIN cache_termlists_terms t_certainty on a_certainty.data_type='L' and t_certainty.id=v_certainty.int_value
) on v_certainty.occurrence_id=o.id and v_certainty.deleted=false
LEFT JOIN (occurrence_attribute_values v_det_first_name
  JOIN occurrence_attributes a_det_first_name on a_det_first_name.id=v_det_first_name.occurrence_attribute_id and a_det_first_name.deleted=false and a_det_first_name.system_function='det_first_name'
  LEFT JOIN cache_termlists_terms t_det_first_name on a_det_first_name.data_type='L' and t_det_first_name.id=v_det_first_name.int_value
) on v_det_first_name.occurrence_id=o.id and v_det_first_name.deleted=false
LEFT JOIN (occurrence_attribute_values v_det_last_name
  JOIN occurrence_attributes a_det_last_name on a_det_last_name.id=v_det_last_name.occurrence_attribute_id and a_det_last_name.deleted=false and a_det_last_name.system_function='det_last_name'
  LEFT JOIN cache_termlists_terms t_det_last_name on a_det_last_name.data_type='L' and t_det_last_name.id=v_det_last_name.int_value
) on v_det_last_name.occurrence_id=o.id and v_det_last_name.deleted=false
LEFT JOIN (occurrence_attribute_values v_det_full_name
  JOIN occurrence_attributes a_det_full_name on a_det_full_name.id=v_det_full_name.occurrence_attribute_id and a_det_full_name.deleted=false and a_det_full_name.system_function='det_full_name'
  LEFT JOIN cache_termlists_terms t_det_full_name on a_det_full_name.data_type='L' and t_det_full_name.id=v_det_full_name.int_value
) on v_det_full_name.occurrence_id=o.id and v_det_full_name.deleted=false
WHERE cache_occurrences_nonfunctional.id=o.id;

UPDATE cache_occurrences_nonfunctional o
SET media=(SELECT array_to_string(array_agg(om.path), ',')
FROM occurrence_media om WHERE om.occurrence_id=o.id AND om.deleted=false)
FROM occurrences occ
WHERE occ.id=o.id
AND occ.deleted=false;

UPDATE cache_occurrences_nonfunctional o
SET data_cleaner_info=
  CASE WHEN occ.last_verification_check_date IS NULL THEN NULL ELSE
    COALESCE((SELECT array_to_string(array_agg(distinct '[' || oc.generated_by || ']{' || oc.comment || '}'),' ')
      FROM occurrence_comments oc
      WHERE oc.occurrence_id=o.id
         AND oc.implies_manual_check_required=true
         AND oc.deleted=false), 'pass') END
FROM occurrences occ
WHERE occ.id=o.id
AND occ.deleted=false;

INSERT INTO cache_samples_functional(
            id, website_id, survey_id, input_form, location_id, location_name,
            public_geom, date_start, date_end, date_type, created_on, updated_on, verified_on, created_by_id,
            group_id, record_status, query)
SELECT distinct on (s.id) s.id, su.website_id, s.survey_id, s.input_form, s.location_id,
  CASE WHEN s.privacy_precision IS NOT NULL THEN NULL ELSE COALESCE(l.name, s.location_name, lp.name, sp.location_name) END,
  reduce_precision(coalesce(s.geom, l.centroid_geom), false, s.privacy_precision,
        case when s.entered_sref_system is null then l.centroid_sref_system else s.entered_sref_system end),
  s.date_start, s.date_end, s.date_type, s.created_on, s.updated_on, s.verified_on, s.created_by_id,
  s.group_id, s.record_status,
  case
    when sc1.id is null then null
    when sc2.id is null and s.updated_on<=sc1.created_on then 'Q'
    else 'A'
  end
FROM samples s
LEFT JOIN samples sp ON sp.id=s.parent_id AND  sp.deleted=false
LEFT JOIN locations l ON l.id=s.location_id AND l.deleted=false
LEFT JOIN locations lp ON lp.id=sp.location_id AND lp.deleted=false
JOIN surveys su on su.id=s.survey_id and su.deleted=false
LEFT JOIN sample_comments sc1 ON sc1.sample_id=s.id AND sc1.deleted=false
    AND sc1.query=true AND (s.verified_on IS NULL OR sc1.created_on>s.verified_on)
LEFT JOIN sample_comments sc2 ON sc2.sample_id=s.id AND sc2.deleted=false
    AND sc2.query=false AND (s.verified_on IS NULL OR sc2.created_on>s.verified_on) AND sc2.id>sc1.id
WHERE s.deleted=false;

UPDATE cache_samples_functional u
SET media_count=(SELECT COUNT(sm.*)
FROM sample_media sm WHERE sm.sample_id=u.id AND sm.deleted=false)
FROM samples s
WHERE s.id=u.id;



INSERT INTO cache_samples_nonfunctional(
            id, website_title, survey_title, group_title, public_entered_sref,
            entered_sref_system, recorders, comment, privacy_precision, licence_code)
SELECT distinct on (s.id) s.id, w.title, su.title, g.title,
  case when s.privacy_precision is not null then null else
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
  s.recorder_names, s.comment, s.privacy_precision, li.code
FROM samples s
JOIN surveys su on su.id=s.survey_id and su.deleted=false
JOIN websites w on w.id=su.website_id and w.deleted=false
LEFT JOIN groups g on g.id=s.group_id and g.deleted=false
LEFT JOIN locations l on l.id=s.location_id and l.deleted=false
LEFT JOIN licences li on li.id=s.licence_id and li.deleted=false
WHERE s.deleted=false;

UPDATE cache_samples_nonfunctional
SET
  attr_email=CASE a_email.data_type
      WHEN 'T'::bpchar THEN v_email.text_value
      ELSE NULL::text
  END,
  attr_cms_user_id=CASE a_cms_user_id.data_type
      WHEN 'I'::bpchar THEN v_cms_user_id.int_value
      ELSE NULL::integer
  END,
  attr_cms_username=CASE a_cms_username.data_type
      WHEN 'T'::bpchar THEN v_cms_username.text_value
      ELSE NULL::text
  END,
  attr_first_name=CASE a_first_name.data_type
      WHEN 'T'::bpchar THEN v_first_name.text_value
      ELSE NULL::text
  END,
  attr_last_name=CASE a_last_name.data_type
      WHEN 'T'::bpchar THEN v_last_name.text_value
      ELSE NULL::text
  END,
  attr_full_name=CASE a_full_name.data_type
      WHEN 'T'::bpchar THEN v_full_name.text_value
      ELSE NULL::text
  END,
  attr_biotope=CASE a_biotope.data_type
      WHEN 'T'::bpchar THEN v_biotope.text_value
      WHEN 'L'::bpchar THEN t_biotope.term
      ELSE NULL::text
  END,
  attr_sref_precision=CASE a_sref_precision.data_type
      WHEN 'I'::bpchar THEN v_sref_precision.int_value::double precision
      WHEN 'F'::bpchar THEN v_sref_precision.float_value
      ELSE NULL::double precision
  END
FROM samples s
LEFT JOIN (sample_attribute_values v_email
  JOIN sample_attributes a_email on a_email.id=v_email.sample_attribute_id and a_email.deleted=false and a_email.system_function='email'
  LEFT JOIN cache_termlists_terms t_email on a_email.data_type='L' and t_email.id=v_email.int_value
) on v_email.sample_id=s.id and v_email.deleted=false
LEFT JOIN (sample_attribute_values v_cms_user_id
  JOIN sample_attributes a_cms_user_id on a_cms_user_id.id=v_cms_user_id.sample_attribute_id and a_cms_user_id.deleted=false and a_cms_user_id.system_function='cms_user_id'
  LEFT JOIN cache_termlists_terms t_cms_user_id on a_cms_user_id.data_type='L' and t_cms_user_id.id=v_cms_user_id.int_value
) on v_cms_user_id.sample_id=s.id and v_cms_user_id.deleted=false
LEFT JOIN (sample_attribute_values v_cms_username
  JOIN sample_attributes a_cms_username on a_cms_username.id=v_cms_username.sample_attribute_id and a_cms_username.deleted=false and a_cms_username.system_function='cms_username'
  LEFT JOIN cache_termlists_terms t_cms_username on a_cms_username.data_type='L' and t_cms_username.id=v_cms_username.int_value
) on v_cms_username.sample_id=s.id and v_cms_username.deleted=false
LEFT JOIN (sample_attribute_values v_first_name
  JOIN sample_attributes a_first_name on a_first_name.id=v_first_name.sample_attribute_id and a_first_name.deleted=false and a_first_name.system_function='first_name'
  LEFT JOIN cache_termlists_terms t_first_name on a_first_name.data_type='L' and t_first_name.id=v_first_name.int_value
) on v_first_name.sample_id=s.id and v_first_name.deleted=false
LEFT JOIN (sample_attribute_values v_last_name
  JOIN sample_attributes a_last_name on a_last_name.id=v_last_name.sample_attribute_id and a_last_name.deleted=false and a_last_name.system_function='last_name'
  LEFT JOIN cache_termlists_terms t_last_name on a_last_name.data_type='L' and t_last_name.id=v_last_name.int_value
) on v_last_name.sample_id=s.id and v_last_name.deleted=false
LEFT JOIN (sample_attribute_values v_full_name
  JOIN sample_attributes a_full_name on a_full_name.id=v_full_name.sample_attribute_id and a_full_name.deleted=false and a_full_name.system_function='full_name'
  LEFT JOIN cache_termlists_terms t_full_name on a_full_name.data_type='L' and t_full_name.id=v_full_name.int_value
) on v_full_name.sample_id=s.id and v_full_name.deleted=false
LEFT JOIN (sample_attribute_values v_biotope
  JOIN sample_attributes a_biotope on a_biotope.id=v_biotope.sample_attribute_id and a_biotope.deleted=false and a_biotope.system_function='biotope'
  LEFT JOIN cache_termlists_terms t_biotope on a_biotope.data_type='L' and t_biotope.id=v_biotope.int_value
) on v_biotope.sample_id=s.id and v_biotope.deleted=false
LEFT JOIN (sample_attribute_values v_sref_precision
  JOIN sample_attributes a_sref_precision on a_sref_precision.id=v_sref_precision.sample_attribute_id and a_sref_precision.deleted=false and a_sref_precision.system_function='sref_precision'
  LEFT JOIN cache_termlists_terms t_sref_precision on a_sref_precision.data_type='L' and t_sref_precision.id=v_sref_precision.int_value
) on v_sref_precision.sample_id=s.id and v_sref_precision.deleted=false
WHERE s.id=cache_samples_nonfunctional.id;

UPDATE cache_samples_nonfunctional s
SET media=(SELECT array_to_string(array_agg(sm.path), ',')
FROM sample_media sm WHERE sm.sample_id=s.id AND sm.deleted=false)
FROM samples smp
WHERE smp.id=s.id
AND smp.deleted=false;

-- Queries to update the recorder names - highest priority approach first
UPDATE cache_samples_nonfunctional cs
SET recorders = COALESCE(
  NULLIF(cs.attr_full_name, ''),
  cs.attr_last_name || COALESCE(', ' || cs.attr_first_name, '')
)
WHERE cs.recorders is null
AND (
  NULLIF(cs.attr_full_name, '') IS NOT NULL OR
  NULLIF(cs.attr_last_name, '') IS NOT NULL
);

UPDATE cache_samples_nonfunctional cs
    SET recorders=sp.recorder_names
    FROM samples s
    JOIN samples sp on sp.id=s.parent_id and sp.deleted=false
    WHERE cs.recorders IS NULL
    and s.id=cs.id and s.deleted=false and sp.recorder_names is not null and sp.recorder_names<>'';

UPDATE cache_samples_nonfunctional cs
    SET recorders=sav.text_value
    FROM samples s
    JOIN samples sp on sp.id=s.parent_id and sp.deleted=false
    JOIN sample_attribute_values sav on sav.sample_id=sp.id and sav.deleted=false and sav.text_value <> ', '
    JOIN sample_attributes sa on sa.id=sav.sample_attribute_id and sa.system_function = 'full_name' and sa.deleted=false
    WHERE cs.recorders IS NULL
    AND s.id=cs.id AND s.deleted=false;

UPDATE cache_samples_nonfunctional cs
    SET recorders=sav.text_value || coalesce(', ' || savf.text_value, '')
    FROM samples s
    JOIN samples sp on sp.id=s.parent_id and sp.deleted=false
    JOIN sample_attribute_values sav on sav.sample_id=sp.id and sav.deleted=false
    JOIN sample_attributes sa on sa.id=sav.sample_attribute_id and sa.system_function = 'last_name' and sa.deleted=false
    LEFT JOIN (sample_attribute_values savf
    JOIN sample_attributes saf on saf.id=savf.sample_attribute_id and saf.system_function = 'first_name' and saf.deleted=false
    ) on savf.deleted=false
    WHERE cs.recorders IS NULL
    AND savf.sample_id=sp.id
    AND s.id=cs.id AND s.deleted=false;

UPDATE cache_samples_nonfunctional cs
    SET recorders=p.surname || coalesce(', ' || p.first_name, '')
    FROM users u
    JOIN cache_samples_functional csf on csf.created_by_id=u.id
    JOIN people p on p.id=u.person_id and p.deleted=false
    WHERE cs.recorders IS NULL
    AND cs.id=csf.id and u.id<>1;

UPDATE cache_samples_nonfunctional cs
    SET recorders=sav.text_value
    FROM sample_attribute_values sav
    JOIN sample_attributes sa on sa.id=sav.sample_attribute_id and sa.system_function = 'cms_username' and sa.deleted=false
    WHERE cs.recorders IS NULL
    AND sav.sample_id=cs.id AND sav.deleted=false;

UPDATE cache_samples_nonfunctional cs
    SET recorders=sav.text_value
    FROM samples s
    JOIN samples sp on sp.id=s.parent_id and sp.deleted=false
    JOIN sample_attribute_values sav on sav.sample_id=sp.id and sav.deleted=false
    JOIN sample_attributes sa on sa.id=sav.sample_attribute_id and sa.system_function = 'cms_username' and sa.deleted=false
    WHERE cs.recorders IS NULL
    AND s.id=cs.id AND s.deleted=false;

UPDATE cache_samples_nonfunctional cs
    SET recorders=u.username
    FROM users u
    JOIN cache_samples_functional csf on csf.created_by_id=u.id
    WHERE cs.recorders IS NULL
    AND cs.id=csf.id AND u.id<>1;

-- map squares, the quickest way to populate is to use the existing cache
UPDATE cache_occurrences_functional
SET map_sq_1km_id=co.map_sq_1km_id,
  map_sq_2km_id=co.map_sq_2km_id,
  map_sq_10km_id=co.map_sq_10km_id
FROM cache_occurrences co
WHERE co.id=cache_occurrences_functional.id;

UPDATE cache_samples_functional
SET map_sq_1km_id=co.map_sq_1km_id,
  map_sq_2km_id=co.map_sq_2km_id,
  map_sq_10km_id=co.map_sq_10km_id
FROM cache_occurrences co
WHERE co.sample_id=cache_samples_functional.id;

-- ensure samples containing sensitive records don't give anything away
update cache_samples_nonfunctional snf
set public_entered_sref=null where id in (
  select s.id from samples s
  join cache_occurrences_functional o on o.sample_id=s.id
  where o.sensitive=true
);

update cache_samples_functional snf
set location_name=null, location_id=null where id in (
  select s.id from samples s
  join cache_occurrences_functional o on o.sample_id=s.id
  where o.sensitive=true
)