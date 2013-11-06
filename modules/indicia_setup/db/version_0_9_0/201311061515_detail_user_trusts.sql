CREATE OR REPLACE VIEW detail_user_trusts AS 
 select ut.id, ut.user_id, ut.survey_id, ut.location_id, ut.taxon_group_id, p.surname || ', ' || p.first_name as person,
    pc.surname || ', ' || pc.first_name as trusted_by,
    su.title as survey, tg.title as taxon_group, l.name as location, 
    ut.created_by_id, uc.username AS created_by, ut.updated_by_id, uu.username AS updated_by, ut.deleted
   from user_trusts ut
   join users u on u.id=ut.user_id and u.deleted=false
   join people p on p.id=u.person_id and p.deleted=false
   join users uc on uc.id=ut.created_by_id and uc.deleted=false
   join users uu on uu.id=ut.updated_by_id and uu.deleted=false
   join people pc on pc.id=uc.person_id and pc.deleted=false
   left join surveys su on su.id=ut.survey_id and su.deleted=false
   left join taxon_groups tg on tg.id=ut.taxon_group_id and tg.deleted=false
   left join locations l on l.id=ut.location_id and l.deleted=false
   where ut.deleted = false;
