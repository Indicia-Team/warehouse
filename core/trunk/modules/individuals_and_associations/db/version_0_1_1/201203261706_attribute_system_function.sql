-- add system_function column to attributes

-- SET search_path TO ind01,public;

alter table known_subject_attributes
  add column system_function character varying(30);

comment on column known_subject_attributes.system_function is 'Machine readable function of this attribute, e.g. sex. Defines how the field can be interpreted by the system.';

alter table subject_observation_attributes
  add column system_function character varying(30);

comment on column subject_observation_attributes.system_function is 'Machine readable function of this attribute, e.g. sex/stage. Defines how the field can be interpreted by the system.';

alter table identifier_attributes
  add column system_function character varying(30);

comment on column identifier_attributes.system_function is 'Machine readable function of this attribute. Defines how the field can be interpreted by the system.';

DROP VIEW list_identifier_attributes;

CREATE OR REPLACE VIEW list_identifier_attributes AS 
 SELECT pa.id, pa.caption, fsb2.name AS outer_structure_block, fsb.name AS inner_structure_block, pa.data_type, ct.control AS control_type, pa.termlist_id, pa.multi_value, pa.system_function, paw.website_id, (((pa.id || '|'::text) || pa.data_type::text) || '|'::text) || COALESCE(pa.termlist_id::text, ''::text) AS signature, paw.default_text_value, paw.default_int_value, paw.default_float_value, paw.default_date_start_value, paw.default_date_end_value, paw.default_date_type_value, COALESCE((paw.validation_rules::text || '
'::text) || pa.validation_rules::text, COALESCE(paw.validation_rules, ''::character varying)::text || COALESCE(pa.validation_rules, ''::character varying)::text) AS validation_rules, pa.deleted, paw.deleted AS website_deleted, pa.public
   FROM identifier_attributes pa
   LEFT JOIN identifier_attributes_websites paw ON pa.id = paw.identifier_attribute_id AND paw.deleted = false
   LEFT JOIN control_types ct ON ct.id = paw.control_type_id
   LEFT JOIN form_structure_blocks fsb ON fsb.id = paw.form_structure_block_id
   LEFT JOIN form_structure_blocks fsb2 ON fsb2.id = fsb.parent_id
  WHERE pa.deleted = false
  ORDER BY fsb2.weight, fsb.weight, paw.weight;

-- ALTER TABLE list_identifier_attributes OWNER TO indicia_user;

DROP VIEW list_known_subject_attributes;

CREATE OR REPLACE VIEW list_known_subject_attributes AS 
 SELECT pa.id, pa.caption, fsb2.name AS outer_structure_block, fsb.name AS inner_structure_block, pa.data_type, ct.control AS control_type, pa.termlist_id, pa.multi_value, pa.system_function, paw.website_id, (((pa.id || '|'::text) || pa.data_type::text) || '|'::text) || COALESCE(pa.termlist_id::text, ''::text) AS signature, paw.default_text_value, paw.default_int_value, paw.default_float_value, paw.default_date_start_value, paw.default_date_end_value, paw.default_date_type_value, COALESCE((paw.validation_rules::text || '
'::text) || pa.validation_rules::text, COALESCE(paw.validation_rules, ''::character varying)::text || COALESCE(pa.validation_rules, ''::character varying)::text) AS validation_rules, pa.deleted, paw.deleted AS website_deleted, pa.public
   FROM known_subject_attributes pa
   LEFT JOIN known_subject_attributes_websites paw ON pa.id = paw.known_subject_attribute_id AND paw.deleted = false
   LEFT JOIN control_types ct ON ct.id = paw.control_type_id
   LEFT JOIN form_structure_blocks fsb ON fsb.id = paw.form_structure_block_id
   LEFT JOIN form_structure_blocks fsb2 ON fsb2.id = fsb.parent_id
  WHERE pa.deleted = false
  ORDER BY fsb2.weight, fsb.weight, paw.weight;

-- ALTER TABLE list_known_subject_attributes OWNER TO indicia_user;

DROP VIEW list_subject_observation_attributes;

CREATE OR REPLACE VIEW list_subject_observation_attributes AS 
 SELECT pa.id, pa.caption, fsb2.name AS outer_structure_block, fsb.name AS inner_structure_block, pa.data_type, ct.control AS control_type, pa.termlist_id, pa.multi_value, pa.system_function, paw.website_id, paw.restrict_to_survey_id, (((pa.id || '|'::text) || pa.data_type::text) || '|'::text) || COALESCE(pa.termlist_id::text, ''::text) AS signature, paw.default_text_value, paw.default_int_value, paw.default_float_value, paw.default_date_start_value, paw.default_date_end_value, paw.default_date_type_value, COALESCE((paw.validation_rules::text || '
'::text) || pa.validation_rules::text, COALESCE(paw.validation_rules, ''::character varying)::text || COALESCE(pa.validation_rules, ''::character varying)::text) AS validation_rules, pa.deleted, paw.deleted AS website_deleted, pa.public
   FROM subject_observation_attributes pa
   LEFT JOIN subject_observation_attributes_websites paw ON pa.id = paw.subject_observation_attribute_id AND paw.deleted = false
   LEFT JOIN control_types ct ON ct.id = paw.control_type_id
   LEFT JOIN form_structure_blocks fsb ON fsb.id = paw.form_structure_block_id
   LEFT JOIN form_structure_blocks fsb2 ON fsb2.id = fsb.parent_id
   LEFT JOIN surveys s ON s.id = paw.restrict_to_survey_id AND s.deleted = false
  WHERE pa.deleted = false
  ORDER BY fsb2.weight, fsb.weight, paw.weight;

-- ALTER TABLE list_subject_observation_attributes OWNER TO indicia_user;

