-- #slow script#

-- rough cut of data to grab stuff which might be best output as channel islands grid
select id into temporary tofix
from cache_occurrences_functional
where st_x(st_centroid(public_geom)) between -257600 and -210500
and st_y(st_centroid(public_geom)) between 6271000 and 6415000;

-- recalculate the output_sref for these data
update cache_occurrences_nonfunctional onf
set output_sref=get_output_sref(
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
  )
from tofix, occurrences o
join samples s on s.id=o.sample_id
left join (sample_attribute_values spv
  join sample_attributes spa on spa.id=spv.sample_attribute_id and spa.deleted=false
      and spa.system_function='sref_precision'
) on spv.sample_id=s.id and spv.deleted=false
left join locations l on l.id=s.location_id
where onf.id=tofix.id and o.id=tofix.id;

drop table tofix;