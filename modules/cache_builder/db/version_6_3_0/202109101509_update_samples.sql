-- #slow script#

UPDATE cache_samples_functional u
SET external_key=u.external_key,
  public_geom=reduce_precision(coalesce(s.geom, l.centroid_geom), false, greatest(s.privacy_precision, (SELECT max(sensitivity_precision) FROM occurrences WHERE sample_id=s.id))),
  sensitive=(SELECT max(sensitivity_precision) FROM occurrences WHERE sample_id=s.id) IS NOT NULL,
  private=s.privacy_precision IS NOT NULL
FROM samples s
LEFT JOIN locations l ON l.id=s.location_id AND l.deleted=false
WHERE s.id=u.id;

UPDATE cache_samples_nonfunctional u
  SET public_entered_sref=case
    when s.privacy_precision is not null OR (SELECT max(sensitivity_precision) FROM occurrences WHERE sample_id=s.id) IS NOT NULL then
      get_output_sref(
        greatest(
          round(sqrt(st_area(st_transform(s.geom, sref_system_to_srid(s.entered_sref_system)))))::integer,
          (SELECT max(sensitivity_precision) FROM occurrences WHERE sample_id=s.id),
          s.privacy_precision,
          -- work out best square size to reflect a lat long's true precision
          case
          when coalesce(v_sref_precision.int_value, v_sref_precision.float_value)>=501 then 10000
          when coalesce(v_sref_precision.int_value, v_sref_precision.float_value) between 51 and 500 then 1000
          when coalesce(v_sref_precision.int_value, v_sref_precision.float_value) between 6 and 50 then 100
          else 10
          end,
          10 -- default minimum square size
        ), reduce_precision(coalesce(s.geom, l.centroid_geom), (SELECT bool_or(confidential) FROM occurrences WHERE sample_id=s.id), greatest((SELECT max(sensitivity_precision) FROM occurrences WHERE sample_id=s.id), s.privacy_precision))
      )
   else
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
  output_sref=get_output_sref(
    greatest(
      round(sqrt(st_area(st_transform(s.geom, sref_system_to_srid(s.entered_sref_system)))))::integer,
      (SELECT max(sensitivity_precision) FROM occurrences WHERE sample_id=s.id),
      s.privacy_precision,
      -- work out best square size to reflect a lat long's true precision
      case
        when coalesce(v_sref_precision.int_value, v_sref_precision.float_value)>=501 then 10000
        when coalesce(v_sref_precision.int_value, v_sref_precision.float_value) between 51 and 500 then 1000
        when coalesce(v_sref_precision.int_value, v_sref_precision.float_value) between 6 and 50 then 100
        else 10
      end,
      10 -- default minimum square size
    ), reduce_precision(coalesce(s.geom, l.centroid_geom), (SELECT bool_or(confidential) FROM occurrences WHERE sample_id=s.id), greatest((SELECT max(sensitivity_precision) FROM occurrences WHERE sample_id=s.id), s.privacy_precision))
  ),
  output_sref_system=get_output_system(
    reduce_precision(coalesce(s.geom, l.centroid_geom), (SELECT bool_or(confidential) FROM occurrences WHERE sample_id=s.id), greatest((SELECT max(sensitivity_precision) FROM occurrences WHERE sample_id=s.id), s.privacy_precision))
  ),
  verifier=pv.surname || ', ' || pv.first_name
FROM samples s
LEFT JOIN locations l ON l.id=s.location_id AND l.deleted=false
LEFT JOIN (sample_attribute_values v_sref_precision
  JOIN sample_attributes a_sref_precision on a_sref_precision.id=v_sref_precision.sample_attribute_id and a_sref_precision.deleted=false and a_sref_precision.system_function='sref_precision'
  LEFT JOIN cache_termlists_terms t_sref_precision on a_sref_precision.data_type='L' and t_sref_precision.id=v_sref_precision.int_value
) on v_sref_precision.sample_id=s.id and v_sref_precision.deleted=false
LEFT JOIN users uv on uv.id=s.verified_by_id and uv.deleted=false
LEFT JOIN people pv on pv.id=uv.person_id and pv.deleted=false
WHERE s.id=u.id;
