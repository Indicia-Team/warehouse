--#slow script#

-- Script to fix occurrences.determiner_id when it points to a users record
-- (incorrect) so it points to a people record (correct).

drop table if exists to_fix;

-- Find the occurrences where determiner_id points to the user ID of someone
-- related to the record (creator, verifier, commenter etc).
select distinct o.id
into temporary to_fix
from occurrences o
join cache_occurrences_nonfunctional onf on onf.id=o.id
join users uc on uc.id=o.created_by_id
left join users uwrong on uwrong.id=o.determiner_id
left join people pwrong on pwrong.id=uwrong.person_id
left join users uv on uv.id=o.verified_by_id
where o.determiner_id is not null and o.deleted=false
-- Exclude if determiner_id matches one of the obvious known person IDs.
and o.determiner_id<>uc.person_id
and uc.person_id<>o.determiner_id
and uv.person_id<>o.determiner_id
-- Include only if the determiner_id has been confirmed as a user_id related to the record.
and (uc.id=o.determiner_id
		or uv.id=o.determiner_id
		or exists(select id from occurrence_comments oc where oc.occurrence_id=o.id and oc.created_by_id=o.determiner_id)
		or case when pwrong.id is null or onf.attr_det_full_name is null then false else onf.attr_det_full_name=pwrong.first_name || ' ' || pwrong.surname or onf.attr_det_full_name=pwrong.surname || ', ' || pwrong.first_name end=true
		);

-- Now update.
update occurrences o
set determiner_id=u.person_id
from users u, to_fix tf
where u.id=o.determiner_id
and o.id=tf.id;