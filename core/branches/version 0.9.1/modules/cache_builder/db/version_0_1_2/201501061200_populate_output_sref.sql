-- #slow script#

update cache_occurrences co
set output_sref=get_output_sref(
    co.public_entered_sref,
    co.entered_sref_system,
    greatest(
      co.sensitivity_precision,
      co.privacy_precision,
      -- work out best square size to reflect a lat long's true precision
      case
        when sav.int_value>=501 then 10000
        when sav.int_value between 51 and 500 then 1000
        when sav.int_value between 6 and 50 then 100
        else 10
      end,
      10 -- default minimum square size
    ),
    co.public_geom
)
from samples s
left join sample_attribute_values sav on sav.sample_id=s.id and sav.deleted=false
left join sample_attributes sa on sa.id=sav.sample_attribute_id and sa.system_function='sref_precision' and sa.deleted=false
where s.id=co.sample_id and s.deleted=false;