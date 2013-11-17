
CREATE OR REPLACE VIEW list_filters_users AS 
 SELECT fu.id, fu.filter_id, fu.user_id, f.title as filter_title, f.description as filter_description, f.definition as filter_definition, 
     f.sharing as filter_sharing, f.public as filter_public, f.defines_permissions as filter_defines_permissions,
     f.created_by_id as filter_created_by_id, p.surname || coalesce(', ' || p.first_name, '') as person_name
   FROM filters_users fu
   JOIN users u on u.id=fu.user_id and u.deleted=false
   JOIN people p on p.id=u.person_id and p.deleted=false
   join filters f on f.id=fu.filter_id and f.deleted=false
  WHERE fu.deleted = false;