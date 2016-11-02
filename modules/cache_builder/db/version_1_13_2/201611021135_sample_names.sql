-- #slow script#

-- Corrects issues with population of recorder names
update cache_samples_nonfunctional cs
set recorders=s.recorder_names
from samples s
where cs.id=s.id;

-- sample attributes are best bet
update cache_samples_nonfunctional
set recorders = coalesce(
  nullif(attr_full_name, ''),
  attr_last_name || coalesce(', ' || attr_first_name, '')
)
where recorders is null
and (
  nullif(attr_full_name, '') is not null or
  nullif(attr_last_name, '') is not null
);

-- Sample recorder names in parent sample
update cache_samples_nonfunctional cs
set recorders=sp.recorder_names
from samples s
join samples sp on sp.id=s.parent_id and sp.deleted=false and sp.recorder_names is not null
where cs.recorders is null
and s.id=cs.id and s.deleted=false;

-- full recorder name in parent sample
update cache_samples_nonfunctional cs
set recorders=sav.text_value
from samples s
join samples sp on sp.id=s.parent_id and sp.deleted=false
join sample_attribute_values sav on sav.sample_id=sp.id and sav.deleted=false and sav.text_value <> ', '
join sample_attributes sa on sa.id=sav.sample_attribute_id and sa.system_function = 'full_name' and sa.deleted=false
where cs.recorders is null
and s.id=cs.id and s.deleted=false;

-- firstname and surname in parent sample
update cache_samples_nonfunctional cs
set recorders = coalesce(savf.text_value || ' ', '') || sav.text_value
from samples s
join samples sp on sp.id=s.parent_id and sp.deleted=false
join sample_attribute_values sav on sav.sample_id=sp.id and sav.deleted=false
join sample_attributes sa on sa.id=sav.sample_attribute_id and sa.system_function = 'last_name' and sa.deleted=false
left join (sample_attribute_values savf
  join sample_attributes saf on saf.id=savf.sample_attribute_id and saf.system_function = 'first_name' and saf.deleted=false
) on savf.deleted=false
where cs.recorders is null
and savf.sample_id=sp.id and s.id=cs.id and s.deleted=false;

-- warehouse surname, first name
update cache_samples_nonfunctional cs
set recorders = p.surname || coalesce(', ' || p.first_name, '')
from people p, users u
join cache_samples_functional csf on csf.created_by_id=u.id
where cs.recorders is null
and csf.id=cs.id and p.id=u.person_id and p.deleted=false
and u.id<>1;

-- CMS username
update cache_samples_nonfunctional cs
set recorders = sav.text_value
from sample_attribute_values sav
join sample_attributes sa on sa.id=sav.sample_attribute_id and sa.system_function = 'cms_username' and sa.deleted=false
where cs.recorders is null
and sav.sample_id=cs.id and sav.deleted=false;

-- CMS username in parent sample
update cache_samples_nonfunctional cs
set recorders = sav.text_value
from samples s
join samples sp on sp.id=s.parent_id and sp.deleted=false
join sample_attribute_values sav on sav.sample_id=sp.id and sav.deleted=false
join sample_attributes sa on sa.id=sav.sample_attribute_id and sa.system_function = 'cms_username' and sa.deleted=false
where cs.recorders is null
and s.id=cs.id and s.deleted=false;

-- warehouse username
update cache_samples_nonfunctional cs
set recorders=u.username
from users u
join cache_samples_functional csf on csf.created_by_id=u.id
where cs.recorders is null
and cs.id=csf.id and u.id<>1;