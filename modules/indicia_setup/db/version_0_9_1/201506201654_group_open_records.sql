ALTER TABLE groups
   ALTER COLUMN implicit_record_inclusion DROP NOT NULL;

COMMENT ON COLUMN groups.implicit_record_inclusion IS
  'If true, then records are included in this group''s content if they are posted by a group member and meet the groups filter criteria. If false, then records must be explicitly posted into the group by a group member. If null, then they are included if they match the filter no matter who or how they were posted.';


CREATE OR REPLACE VIEW list_groups AS
 SELECT g.id,
    g.title,
    g.code,
    g.group_type_id,
    g.description,
    g.from_date,
    g.to_date,
    g.private_records,
    g.website_id,
    g.filter_id,
    g.joining_method,
    g.logo_path,
    g.implicit_record_inclusion
   FROM groups g
  WHERE g.deleted = false;

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
    CASE g.joining_method
        WHEN 'P'::bpchar THEN btrim(regexp_replace(regexp_replace(lower(g.title::text), '[ ]'::text, '-'::text, 'g'::text), '[^a-z0-9\-]'::text, ''::text, 'g'::text), '-'::text)
        ELSE NULL::text
    END AS url_safe_title,
    g.implicit_record_inclusion
   FROM groups g
     LEFT JOIN filters f ON f.id = g.filter_id AND f.deleted = false
     JOIN users c ON c.id = g.created_by_id
     JOIN users u ON u.id = g.updated_by_id
  WHERE g.deleted = false;