-- #slow script#

update cache_occurrences co
set output_sref=get_output_sref(
    co.public_entered_sref,
    co.entered_sref_system,
    greatest(
      co.sensitivity_precision,
      co.privacy_precision,
      -- no need to consider the sref_precision sample attribute at this stage as it has only just been added
      10 -- default minimum square size
    ),
    co.public_geom
)
from samples s
where s.id=co.sample_id and s.deleted=false;