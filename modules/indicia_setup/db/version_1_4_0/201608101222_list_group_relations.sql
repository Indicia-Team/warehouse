-- View: list_group_relations

-- DROP VIEW list_groups;

CREATE OR REPLACE VIEW list_group_relations AS
 SELECT gr.id,
    gr.from_group_id,
    gr.to_group_id,
    gr.relationship_type_id,
    g.website_id
   FROM group_relations gr
   JOIN groups g on g.id=gr.from_group_id and g.deleted=false
  WHERE gr.deleted = false;

