CREATE OR REPLACE VIEW list_user_trusts AS 
 SELECT ut.id, ut.user_id, ut.survey_id, ut.location_id, ut.taxon_group_id, 
    ut.created_on, ut.created_by_id, ut.updated_on, ut.updated_by_id, 
    ut.deleted
   FROM user_trusts ut;
