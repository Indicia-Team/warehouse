update cache_occurrences co
set location_name=coalesce(l.name, s.location_name, lp.name, sp.location_name)
from samples s
left join samples sp on sp.id=s.parent_id and sp.deleted=false
left join locations l on l.id=s.location_id and l.deleted=false
left join locations lp on lp.id=sp.location_id and lp.deleted=false
where s.deleted=false
and co.sample_id=s.id
and co.location_name is null
and coalesce(l.name, s.location_name, lp.name, sp.location_name) is not null;