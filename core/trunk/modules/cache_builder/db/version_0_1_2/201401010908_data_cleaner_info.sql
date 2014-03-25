update cache_occurrences o
set data_cleaner_info=case when sub.last_verification_check_date is null then null else
  case when sub.info is null then 'pass' else sub.info end
end
from (
	select o.id, o.last_verification_check_date, 
	  array_to_string(array_agg(distinct '[' || oc.generated_by || ']{' || oc.comment || '}'),' ') as info
	from occurrences o
	left join occurrence_comments oc 
	      on oc.occurrence_id=o.id 
	      and oc.implies_manual_check_required=true 
	      and oc.deleted=false
	group by o.id, o.last_verification_check_date
) sub
where sub.id=o.id;