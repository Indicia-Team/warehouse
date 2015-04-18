CREATE OR REPLACE VIEW detail_groups AS 
 SELECT g.id,
    g.title,
    g.code,
    g.group_type_id,
    g.description,
    g.from_date,
    g.to_date,
    g.private_records,
    g.website_id,
    g.joining_method,
    g.filter_id,
    f.definition AS filter_definition,
    g.created_by_id,
    c.username AS created_by,
    g.updated_by_id,
    u.username AS updated_by,
    g.logo_path,
    case g.joining_method 
      when 'P' then trim(both '-' from regexp_replace(regexp_replace(lower(g.title), '[ ]', '-', 'g'), '[^a-z0-9\-]', '', 'g')) 
      else null 
    end as url_safe_title
   FROM groups g
     LEFT JOIN filters f ON f.id = g.filter_id AND f.deleted = false
     JOIN users c ON c.id = g.created_by_id
     JOIN users u ON u.id = g.updated_by_id
  WHERE g.deleted = false;