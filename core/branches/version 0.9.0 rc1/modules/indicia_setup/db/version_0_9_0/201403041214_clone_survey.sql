CREATE OR REPLACE function f_clone_survey (from_survey_id integer, to_survey_id integer, user_id integer, OUT success bool)
    LANGUAGE plpgsql AS
$$
DECLARE to_website_id integer;
BEGIN 

SELECT website_id INTO to_website_id FROM surveys WHERE id=to_survey_id;
  
-- clone the form structure blocks, top level first
insert into form_structure_blocks (name, parent_id, survey_id, type, weight)
select name, 
  null, 
  to_survey_id,
  type, 
  weight
from form_structure_blocks
where survey_id=from_survey_id
and parent_id is null;

-- then the second level
insert into form_structure_blocks (name, parent_id, survey_id, type, weight)
select source.name, 
  destp.id, 
  to_survey_id,
  source.type, 
  source.weight
from form_structure_blocks source
-- find the parents of the blocks we are copying from
join form_structure_blocks sourcep on sourcep.id=source.parent_id
-- find the blocks we already copied across by matching to these parents
join form_structure_blocks destp on destp.survey_id=to_survey_id and destp.name=sourcep.name -- match on named block
where source.survey_id=from_survey_id;


-- clone the occurrence_attributes_websites
insert into occurrence_attributes_websites (
  website_id, 
  occurrence_attribute_id, 
  created_on, 
  created_by_id, 
  restrict_to_survey_id, 
  form_structure_block_id, 
  validation_rules, 
  weight, 
  control_type_id, 
  default_text_value, 
  default_float_value, 
  default_int_value, 
  default_date_start_value, 
  default_date_end_value, 
  default_date_type_value)
select
  to_website_id,
  oaw.occurrence_attribute_id, 
  now(),
  user_id,
  to_survey_id,
  destblock.id,
  oaw.validation_rules, 
  oaw.weight, 
  oaw.control_type_id, 
  oaw.default_text_value, 
  oaw.default_float_value, 
  oaw.default_int_value, 
  oaw.default_date_start_value, 
  oaw.default_date_end_value, 
  oaw.default_date_type_value
from occurrence_attributes_websites oaw
left join form_structure_blocks sourceblock on sourceblock.id=oaw.form_structure_block_id 
left join form_structure_blocks sourcepblock on sourcepblock.id=sourceblock.parent_id -- parent block from the source oaw record
-- find the matching destination block, with the correct parent.
left join form_structure_blocks destblock on destblock.survey_id=to_survey_id and destblock.name=sourceblock.name 
left join form_structure_blocks destpblock on destpblock.survey_id=to_survey_id and destpblock.id=destblock.parent_id and coalesce(destpblock.name,'')=coalesce(sourcepblock.name, '')
where restrict_to_survey_id=from_survey_id
and coalesce(sourceblock.name || coalesce(sourcepblock.name, ''), '')=coalesce(destblock.name || coalesce(destpblock.name, ''), '');

-- clone the samples_attributes_websites
insert into sample_attributes_websites (
  website_id, 
  sample_attribute_id, 
  created_on, 
  created_by_id, 
  restrict_to_survey_id, 
  form_structure_block_id, 
  validation_rules, 
  weight, 
  control_type_id, 
  default_text_value, 
  default_float_value, 
  default_int_value, 
  default_date_start_value, 
  default_date_end_value, 
  default_date_type_value)
select
  to_website_id,
  oaw.sample_attribute_id, 
  now(),
  user_id,
  to_survey_id,
  destblock.id,
  oaw.validation_rules, 
  oaw.weight, 
  oaw.control_type_id, 
  oaw.default_text_value, 
  oaw.default_float_value, 
  oaw.default_int_value, 
  oaw.default_date_start_value, 
  oaw.default_date_end_value, 
  oaw.default_date_type_value
from sample_attributes_websites oaw
left join form_structure_blocks sourceblock on sourceblock.id=oaw.form_structure_block_id -- block from the source oaw record
left join form_structure_blocks sourcepblock on sourcepblock.id=sourceblock.parent_id -- parent block from the source oaw record
-- find the matching destination block, with the correct parent.
left join form_structure_blocks destblock on destblock.survey_id=to_survey_id and destblock.name=sourceblock.name 
left join form_structure_blocks destpblock on destpblock.survey_id=to_survey_id and destpblock.id=destblock.parent_id and coalesce(destpblock.name,'')=coalesce(sourcepblock.name, '')
where restrict_to_survey_id=from_survey_id
and coalesce(sourceblock.name || coalesce(sourcepblock.name, ''), '')=coalesce(destblock.name || coalesce(destpblock.name, ''), '');

END;
$$;