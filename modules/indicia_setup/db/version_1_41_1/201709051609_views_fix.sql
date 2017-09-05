CREATE OR REPLACE VIEW list_sample_attributes AS 
 SELECT sa.id, sa.caption, fsb2.name AS outer_structure_block, fsb.name AS inner_structure_block, sa.data_type, ct.control AS control_type, sa.termlist_id, sa.multi_value, saw.website_id, saw.restrict_to_survey_id, (((sa.id || '|'::text) || sa.data_type::text) || '|'::text) || COALESCE(sa.termlist_id::text, ''::text) AS signature, saw.default_text_value, saw.default_int_value, saw.default_float_value, saw.default_date_start_value, saw.default_date_end_value, saw.default_date_type_value, COALESCE((saw.validation_rules::text || '
'::text) || sa.validation_rules::text, COALESCE(saw.validation_rules, ''::character varying)::text || COALESCE(sa.validation_rules, ''::character varying)::text) AS validation_rules, 
sa.deleted, saw.deleted AS website_deleted, saw.restrict_to_sample_method_id, sa.system_function,
fsb2.weight as outer_block_weight, fsb.weight as inner_block_weight, saw.weight as weight
   FROM sample_attributes sa
   LEFT JOIN sample_attributes_websites saw ON sa.id = saw.sample_attribute_id AND saw.deleted = false
   LEFT JOIN control_types ct ON ct.id = saw.control_type_id
   LEFT JOIN form_structure_blocks fsb ON fsb.id = saw.form_structure_block_id
   LEFT JOIN form_structure_blocks fsb2 ON fsb2.id = fsb.parent_id
  WHERE sa.deleted = false
  ORDER BY fsb2.weight, fsb.weight, saw.weight;

CREATE OR REPLACE VIEW list_occurrence_attributes AS 
 SELECT sa.id, sa.caption, fsb2.name AS outer_structure_block, fsb.name AS inner_structure_block, sa.data_type, ct.control AS control_type, sa.termlist_id, sa.multi_value, saw.website_id, saw.restrict_to_survey_id, (((sa.id || '|'::text) || sa.data_type::text) || '|'::text) || COALESCE(sa.termlist_id::text, ''::text) AS signature, saw.default_text_value, saw.default_int_value, saw.default_float_value, saw.default_date_start_value, saw.default_date_end_value, saw.default_date_type_value, COALESCE((saw.validation_rules::text || '
'::text) || sa.validation_rules::text, COALESCE(saw.validation_rules, ''::character varying)::text || COALESCE(sa.validation_rules, ''::character varying)::text) AS validation_rules, 
sa.deleted, saw.deleted AS website_deleted, sa.system_function,
fsb2.weight as outer_block_weight, fsb.weight as inner_block_weight, saw.weight as weight
   FROM occurrence_attributes sa
   LEFT JOIN occurrence_attributes_websites saw ON sa.id = saw.occurrence_attribute_id AND saw.deleted = false
   LEFT JOIN control_types ct ON ct.id = saw.control_type_id
   LEFT JOIN form_structure_blocks fsb ON fsb.id = saw.form_structure_block_id
   LEFT JOIN form_structure_blocks fsb2 ON fsb2.id = fsb.parent_id
  WHERE sa.deleted = false
  ORDER BY fsb2.weight, fsb.weight, saw.weight;

CREATE OR REPLACE VIEW list_location_attributes AS 
 SELECT la.id, la.caption, fsb2.name AS outer_structure_block, fsb.name AS inner_structure_block, la.data_type, ct.control AS control_type, la.termlist_id, la.multi_value, law.website_id, law.restrict_to_survey_id, (((la.id || '|'::text) || la.data_type::text) || '|'::text) || COALESCE(la.termlist_id::text, ''::text) AS signature, law.default_text_value, law.default_int_value, law.default_float_value, law.default_date_start_value, law.default_date_end_value, law.default_date_type_value, COALESCE((law.validation_rules::text || '
'::text) || la.validation_rules::text, COALESCE(law.validation_rules, ''::character varying)::text || COALESCE(la.validation_rules, ''::character varying)::text) AS validation_rules, 
la.deleted, law.deleted AS website_deleted, law.restrict_to_location_type_id, la.system_function,
fsb2.weight as outer_block_weight, fsb.weight as inner_block_weight, law.weight as weight
   FROM location_attributes la
   LEFT JOIN location_attributes_websites law ON la.id = law.location_attribute_id AND law.deleted = false
   LEFT JOIN control_types ct ON ct.id = law.control_type_id
   LEFT JOIN form_structure_blocks fsb ON fsb.id = law.form_structure_block_id
   LEFT JOIN form_structure_blocks fsb2 ON fsb2.id = fsb.parent_id
  WHERE la.deleted = false
  ORDER BY fsb2.weight, fsb.weight, law.weight;

CREATE OR REPLACE VIEW list_person_attributes AS 
 SELECT pa.id,
    pa.caption,
    fsb2.name AS outer_structure_block,
    fsb.name AS inner_structure_block,
    pa.data_type,
    ct.control AS control_type,
    pa.termlist_id,
    pa.multi_value,
    paw.website_id,
    (((pa.id || '|'::text) || pa.data_type::text) || '|'::text) || COALESCE(pa.termlist_id::text, ''::text) AS signature,
    paw.default_text_value,
    paw.default_int_value,
    paw.default_float_value,
    paw.default_date_start_value,
    paw.default_date_end_value,
    paw.default_date_type_value,
    COALESCE((paw.validation_rules::text || '
'::text) || pa.validation_rules::text, COALESCE(paw.validation_rules, ''::character varying)::text || COALESCE(pa.validation_rules, ''::character varying)::text) AS validation_rules,
    pa.deleted,
    paw.deleted AS website_deleted,
    pa.public,
    fsb2.weight as outer_block_weight,
    fsb.weight as inner_block_weight,
    paw.weight as weight
   FROM person_attributes pa
     LEFT JOIN person_attributes_websites paw ON pa.id = paw.person_attribute_id AND paw.deleted = false
     LEFT JOIN control_types ct ON ct.id = paw.control_type_id
     LEFT JOIN form_structure_blocks fsb ON fsb.id = paw.form_structure_block_id
     LEFT JOIN form_structure_blocks fsb2 ON fsb2.id = fsb.parent_id
  WHERE pa.deleted = false
  ORDER BY fsb2.weight, fsb.weight, paw.weight;

CREATE OR REPLACE VIEW list_survey_attributes AS 
 SELECT a.id,
    a.caption,
    fsb2.name AS outer_structure_block,
    fsb.name AS inner_structure_block,
    a.data_type,
    ct.control AS control_type,
    a.termlist_id,
    a.multi_value,
    aw.website_id,
    (((a.id || '|'::text) || a.data_type::text) || '|'::text) || COALESCE(a.termlist_id::text, ''::text) AS signature,
    aw.default_text_value,
    aw.default_int_value,
    aw.default_float_value,
    aw.default_date_start_value,
    aw.default_date_end_value,
    aw.default_date_type_value,
    COALESCE((aw.validation_rules::text || ''::text) || a.validation_rules::text, COALESCE(aw.validation_rules, ''::character varying)::text || COALESCE(a.validation_rules, ''::character varying)::text) AS validation_rules,
    a.deleted,
    aw.deleted AS website_deleted,
    a.public,
    fsb2.weight as outer_block_weight,
    fsb.weight as inner_block_weight,
    aw.weight as weight
   FROM survey_attributes a
     LEFT JOIN survey_attributes_websites aw ON a.id = aw.survey_attribute_id AND aw.deleted = false
     LEFT JOIN control_types ct ON ct.id = aw.control_type_id
     LEFT JOIN form_structure_blocks fsb ON fsb.id = aw.form_structure_block_id
     LEFT JOIN form_structure_blocks fsb2 ON fsb2.id = fsb.parent_id
  WHERE a.deleted = false
  ORDER BY fsb2.weight, fsb.weight, aw.weight;

CREATE OR REPLACE VIEW list_taxa_taxon_list_attributes AS 
 SELECT ta.id,
    ta.caption,
    fsb2.name AS outer_structure_block,
    fsb.name AS inner_structure_block,
    ta.data_type,
    ct.control AS control_type,
    ta.termlist_id,
    ta.multi_value,
    tla.taxon_list_id,
    (((ta.id || '|'::text) || ta.data_type::text) || '|'::text) || COALESCE(ta.termlist_id::text, ''::text) AS signature,
    tla.default_text_value,
    tla.default_int_value,
    tla.default_float_value,
    tla.default_date_start_value,
    tla.default_date_end_value,
    tla.default_date_type_value,
    COALESCE((tla.validation_rules::text || '
'::text) || ta.validation_rules::text, COALESCE(tla.validation_rules, ''::character varying)::text || COALESCE(ta.validation_rules, ''::character varying)::text) AS validation_rules,
    ta.deleted,
    tla.deleted AS taxon_list_deleted,
    fsb2.weight as outer_block_weight,
    fsb.weight as inner_block_weight,
    tla.weight as weight
   FROM taxa_taxon_list_attributes ta
     LEFT JOIN taxon_lists_taxa_taxon_list_attributes tla ON ta.id = tla.taxa_taxon_list_attribute_id AND tla.deleted = false
     LEFT JOIN control_types ct ON ct.id = tla.control_type_id
     LEFT JOIN form_structure_blocks fsb ON fsb.id = tla.form_structure_block_id
     LEFT JOIN form_structure_blocks fsb2 ON fsb2.id = fsb.parent_id
  WHERE ta.deleted = false
  ORDER BY fsb2.weight, fsb.weight, tla.weight;