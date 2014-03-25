update cache_occurrences co
set recorders=s.recorder_names
from samples s
where s.id=co.sample_id and s.deleted=false
and co.recorders is null;

update cache_occurrences co
set recorders=sav.text_value
from sample_attribute_values sav
join sample_attributes sa on sa.id=sav.sample_attribute_id and sa.system_function = 'cms_username' and sa.deleted=false
where sav.sample_id=co.sample_id and sav.deleted=false
and co.recorders is null;

update cache_occurrences co
set recorders=sav.text_value
from samples s
join samples sp on sp.id=s.parent_id and sp.deleted=false
join sample_attribute_values sav on sav.sample_id=sp.id and sav.deleted=false
join sample_attributes sa on sa.id=sav.sample_attribute_id and sa.system_function = 'cms_username' and sa.deleted=false
where s.id=co.sample_id and s.deleted=false
and co.recorders is null;

update cache_occurrences co
set recorders=coalesce(savf.text_value || ' ', '') || sav.text_value
from sample_attribute_values sav
join sample_attributes sa on sa.id=sav.sample_attribute_id and sa.system_function = 'last_name' and sa.deleted=false
left join (sample_attribute_values savf 
join sample_attributes saf on saf.id=savf.sample_attribute_id and saf.system_function = 'first_name' and saf.deleted=false
) on savf.deleted=false
where sav.sample_id=co.sample_id 
and sav.deleted=false
and savf.sample_id=co.sample_id
and co.recorders is null;

update cache_occurrences co
set recorders=coalesce(savf.text_value || ' ', '') || sav.text_value
from samples s
join samples sp on sp.id=s.parent_id and sp.deleted=false
join sample_attribute_values sav on sav.sample_id=sp.id and sav.deleted=false
join sample_attributes sa on sa.id=sav.sample_attribute_id and sa.system_function = 'last_name' and sa.deleted=false
left join (sample_attribute_values savf 
join sample_attributes saf on saf.id=savf.sample_attribute_id and saf.system_function = 'first_name' and saf.deleted=false
) on savf.deleted=false
where s.id=co.sample_id 
and s.deleted=false
and co.recorders is null
and savf.sample_id=sp.id;

update cache_occurrences co
set recorders=u.username
from users u
where u.id=co.created_by_id
and co.recorders is null
and u.username <> 'admin'