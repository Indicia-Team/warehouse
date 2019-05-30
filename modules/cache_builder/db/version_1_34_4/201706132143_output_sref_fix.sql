-- #slow script#
UPDATE cache_occurrences_nonfunctional onf
SET output_sref=get_output_sref(
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
    ),
    reduce_precision(coalesce(s.geom, l.centroid_geom), o.confidential, greatest(o.sensitivity_precision, s.privacy_precision),
    case when s.entered_sref_system is null then l.centroid_sref_system else s.entered_sref_system end)
  )
FROM occurrences o
JOIN samples s on s.id=o.sample_id
LEFT JOIN samples sp ON sp.id=s.parent_id AND  sp.deleted=false
LEFT JOIN locations l ON l.id=s.location_id AND l.deleted=false
LEFT JOIN locations lp ON lp.id=sp.location_id AND lp.deleted=false
LEFT JOIN licences li on li.id=s.licence_id
LEFT JOIN (sample_attribute_values spv
  JOIN sample_attributes spa on spa.id=spv.sample_attribute_id and spa.deleted=false
      and spa.system_function='sref_precision'
) on spv.sample_id=s.id and spv.deleted=false
WHERE o.id=onf.id
AND onf.output_sref <> get_output_sref(
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
    ),
    reduce_precision(coalesce(s.geom, l.centroid_geom), o.confidential, greatest(o.sensitivity_precision, s.privacy_precision),
    case when s.entered_sref_system is null then l.centroid_sref_system else s.entered_sref_system end)
  );