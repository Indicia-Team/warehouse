CREATE OR REPLACE FUNCTION f_clone_survey(IN from_survey_id integer, IN to_survey_id integer, IN user_id integer, OUT success boolean)
  RETURNS boolean AS
$BODY$
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
  default_date_type_value,
  default_upper_value)
select distinct
  to_website_id,
  aw.occurrence_attribute_id,
  now(),
  user_id,
  to_survey_id,
  destblock.id,
  aw.validation_rules,
  aw.weight,
  aw.control_type_id,
  aw.default_text_value,
  aw.default_float_value,
  aw.default_int_value,
  aw.default_date_start_value,
  aw.default_date_end_value,
  aw.default_date_type_value,
  aw.default_upper_value
from occurrence_attributes_websites aw
left join form_structure_blocks sourceblock on sourceblock.id=aw.form_structure_block_id
left join form_structure_blocks sourcepblock on sourcepblock.id=sourceblock.parent_id -- parent block from the source aw record
-- find the matching destination block, with the correct parent.
left join form_structure_blocks destblock on destblock.survey_id=to_survey_id and destblock.name=sourceblock.name
left join form_structure_blocks destpblock on destpblock.survey_id=to_survey_id and destpblock.id=destblock.parent_id and coalesce(destpblock.name,'')=coalesce(sourcepblock.name, '')
where restrict_to_survey_id=from_survey_id
and coalesce(sourceblock.name || coalesce(sourcepblock.name, ''), '')=coalesce(destblock.name || coalesce(destpblock.name, ''), '')
and aw.deleted=false;

-- clone the sample_attributes_websites
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
  default_date_type_value,
  restrict_to_sample_method_id,
  default_upper_value)
select distinct
  to_website_id,
  aw.sample_attribute_id,
  now(),
  user_id,
  to_survey_id,
  destblock.id,
  aw.validation_rules,
  aw.weight,
  aw.control_type_id,
  aw.default_text_value,
  aw.default_float_value,
  aw.default_int_value,
  aw.default_date_start_value,
  aw.default_date_end_value,
  aw.default_date_type_value,
  aw.restrict_to_sample_method_id,
  aw.default_upper_value
from sample_attributes_websites aw
left join form_structure_blocks sourceblock on sourceblock.id=aw.form_structure_block_id -- block from the source aw record
left join form_structure_blocks sourcepblock on sourcepblock.id=sourceblock.parent_id -- parent block from the source aw record
-- find the matching destination block, with the correct parent.
left join form_structure_blocks destblock on destblock.survey_id=to_survey_id and destblock.name=sourceblock.name
left join form_structure_blocks destpblock on destpblock.survey_id=to_survey_id and destpblock.id=destblock.parent_id and coalesce(destpblock.name,'')=coalesce(sourcepblock.name, '')
where restrict_to_survey_id=from_survey_id
and coalesce(sourceblock.name || coalesce(sourcepblock.name, ''), '')=coalesce(destblock.name || coalesce(destpblock.name, ''), '')
and aw.deleted=false;

-- clone the location_attributes_websites
insert into location_attributes_websites (
  website_id,
  location_attribute_id,
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
  default_date_type_value,
  restrict_to_location_type_id,
  default_upper_value)
select distinct
  to_website_id,
  aw.location_attribute_id,
  now(),
  user_id,
  to_survey_id,
  destblock.id,
  aw.validation_rules,
  aw.weight,
  aw.control_type_id,
  aw.default_text_value,
  aw.default_float_value,
  aw.default_int_value,
  aw.default_date_start_value,
  aw.default_date_end_value,
  aw.default_date_type_value,
  aw.restrict_to_location_type_id,
  aw.default_upper_value
from location_attributes_websites aw
left join form_structure_blocks sourceblock on sourceblock.id=aw.form_structure_block_id -- block from the source aw record
left join form_structure_blocks sourcepblock on sourcepblock.id=sourceblock.parent_id -- parent block from the source aw record
-- find the matching destination block, with the correct parent.
left join form_structure_blocks destblock on destblock.survey_id=to_survey_id and destblock.name=sourceblock.name
left join form_structure_blocks destpblock on destpblock.survey_id=to_survey_id and destpblock.id=destblock.parent_id and coalesce(destpblock.name,'')=coalesce(sourcepblock.name, '')
where restrict_to_survey_id=from_survey_id
and coalesce(sourceblock.name || coalesce(sourcepblock.name, ''), '')=coalesce(destblock.name || coalesce(destpblock.name, ''), '')
and aw.deleted=false;

END;
$BODY$
  LANGUAGE plpgsql VOLATILE
  COST 100;
