update groups_users gu set deleted=true where id in (
select distinct gu2.id
from groups_users gu
join groups_users gu2 on gu2.group_id=gu.group_id and gu2.user_id=gu.user_id 
  and (gu2.id>gu.id or (gu2.id<gu.id and gu.administrator=true))
where gu.deleted=false
and gu2.deleted=false
and gu2.administrator=false
)