update cache_occurrences co
set location_name=coalesce(s.location_name, sp.location_name)
from samples s
join samples sp on sp.id=s.parent_id and sp.deleted=false
where s.deleted=false
and co.sample_id=s.id
and co.location_name is null;