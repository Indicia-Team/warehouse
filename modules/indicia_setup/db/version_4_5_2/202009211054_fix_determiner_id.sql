--#slow script#

update occurrences o
set determiner_id=u.person_id
from users u
where u.id=o.determiner_id
and o.determiner_id<>u.person_id
and (o.determiner_id=o.created_by_id or o.determiner_id=o.verified_by_id);