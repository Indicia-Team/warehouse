-- #slow script#

select co.id, get_output_sref(
    co.public_entered_sref,
    co.entered_sref_system,
    greatest(
      round(sqrt(st_area(st_transform(s.geom, sref_system_to_srid(s.entered_sref_system)))))::integer,
      co.sensitivity_precision,
      co.privacy_precision,
      -- work out best square size to reflect a lat long's true precision
      case
        when coalesce(spv.int_value, spv.float_value)>=501 then 10000
        when coalesce(spv.int_value, spv.float_value) between 51 and 500 then 1000
        when coalesce(spv.int_value, spv.float_value) between 6 and 50 then 100
        else 10
      end,
      10 -- default minimum square size
    ),
    co.public_geom
) as output_sref
into temporary to_update
from cache_occurrences co, samples s
left join (sample_attribute_values spv
  join sample_attributes spa on spa.id=spv.sample_attribute_id and spa.deleted=false
      and spa.system_function='sref_precision'
) on spv.sample_id=s.id and spv.deleted=false
where s.id=co.sample_id and s.deleted=false;

create index ix_temp_to_update on to_update(id);

update cache_occurrences co 
set output_sref = up.output_sref
from to_update up
where up.id=co.id
and co.output_sref<>up.output_sref;