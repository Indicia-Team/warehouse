/**
* Create a table to hold different types of user interface control, so that the user can associate default control
* types with attributes 
*/
CREATE TABLE control_types
(
   id serial NOT NULL, 
   control character varying(100) NOT NULL, 
   for_data_type character varying(100) NOT NULL, 
   multi_value boolean NULL, 
    PRIMARY KEY (id)
) WITH (OIDS=FALSE)
;

ALTER TABLE control_types ADD CONSTRAINT chk_for_data_type CHECK (for_data_type IN ('T','I','F','D','V','L','B'));

COMMENT ON TABLE control_types IS 'List of user interface control types available, which can be associated with custom attributes in a survey.';
COMMENT ON COLUMN control_types.control IS 'Type of user interface control';
COMMENT ON COLUMN control_types.for_data_type IS 'Data type this control type can be used for. Possible values are T (text), I (integer), F (float), D (date), V (vague date), L (item looked up from a termlist), B (boolean)';
COMMENT ON COLUMN control_types.multi_value IS 'Indicates if the control is available for multivalue attributes only (true), single value only (false), or either (null).';

INSERT INTO control_types (control, for_data_type, multi_value) VALUES ('autocomplete','L', false);
INSERT INTO control_types (control, for_data_type, multi_value) VALUES ('checkbox','B', false);
INSERT INTO control_types (control, for_data_type, multi_value) VALUES ('checkbox_group','L', true);
INSERT INTO control_types (control, for_data_type, multi_value) VALUES ('date_picker','D', false);
INSERT INTO control_types (control, for_data_type, multi_value) VALUES ('date_picker','D', false);
INSERT INTO control_types (control, for_data_type, multi_value) VALUES ('listbox','L', true);
INSERT INTO control_types (control, for_data_type, multi_value) VALUES ('postcode_textbox','T', false);
INSERT INTO control_types (control, for_data_type, multi_value) VALUES ('radio_group','L', false);
INSERT INTO control_types (control, for_data_type, multi_value) VALUES ('select','L', false);
INSERT INTO control_types (control, for_data_type, multi_value) VALUES ('textarea','T', false);
INSERT INTO control_types (control, for_data_type, multi_value) VALUES ('text_input','T', true);

/**
* Create a table to hold the structure of the sections holding controls within each survey form. E.g. defines the tab and fieldset structure.
*/
CREATE TABLE form_structure_blocks
(
   id serial NOT NULL, 
   "name" character varying(100) NOT NULL,
   parent_id integer NULL,
   survey_id integer NOT NULL,
   type CHAR(1) NOT NULL,
   weight integer NOT NULL DEFAULT 0,
    PRIMARY KEY (id)
) WITH (OIDS=FALSE)
;

ALTER TABLE form_structure_blocks
  ADD CONSTRAINT fk_form_structure_blocks_parent FOREIGN KEY (parent_id)
      REFERENCES form_structure_blocks (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION;

ALTER TABLE form_structure_blocks
  ADD CONSTRAINT fk_form_structure_blocks_survey FOREIGN KEY (survey_id)
      REFERENCES surveys (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION;

ALTER TABLE form_structure_blocks ADD CONSTRAINT chk_form_structure_block_type CHECK (type IN ('S','O','L'));


COMMENT ON TABLE form_structure_blocks IS 'List of the structural blocks which contain the controls on dynamically generated forms. For example, top level blocks might relate to tabs or wizard pages, and the next level blocks relate to the fieldsets within each tab or page.';
COMMENT ON COLUMN form_structure_blocks."name" IS 'Name of the structural block, typically displayed as the caption of the block on the form after translation.';
COMMENT ON COLUMN form_structure_blocks.parent_id IS 'Reference to the parent block if there is one.';
COMMENT ON COLUMN form_structure_blocks.survey_id IS 'Reference to the survey this block is part of.';
COMMENT ON COLUMN form_structure_blocks.type IS 'Defines the type data this block is for, either S(ample), O(ccurrence) or L(ocation).';
COMMENT ON COLUMN form_structure_blocks.weight IS 'Dictates the order of blocks within the parent block or at the top level. Blocks with a higher weight will sink to the end of the list.';

/**
* Now add attributes to the attributes data to allow them to be configured specifically for use within each survey.
*/
ALTER TABLE location_attributes_websites ADD COLUMN form_structure_block_id integer NULL;
ALTER TABLE location_attributes_websites ADD COLUMN validation_rules character varying(500) NULL;
ALTER TABLE location_attributes_websites ADD COLUMN weight integer NOT NULL DEFAULT 0;
ALTER TABLE location_attributes_websites ADD COLUMN control_type_id integer NULL;

ALTER TABLE location_attributes_websites
  ADD CONSTRAINT fk_location_attributes_website_control_type FOREIGN KEY (control_type_id)
      REFERENCES control_types (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION;

ALTER TABLE location_attributes_websites
  ADD CONSTRAINT fk_location_attributes_website_form_structure_block FOREIGN KEY (form_structure_block_id)
      REFERENCES form_structure_blocks (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION;

COMMENT ON COLUMN location_attributes_websites.form_structure_block_id IS 'Within the context of this website and survey, defines the blocks of custom attributes which define the form structure.';
COMMENT ON COLUMN location_attributes_websites.form_structure_block_id IS 'Additional validation rules that are defined for this attribute but only active within the context of this survey/website.';
COMMENT ON COLUMN location_attributes_websites.weight IS 'Dictates the order of controls within the block or at the top level. Controls with a higher weight will sink to the end of the list.';
COMMENT ON COLUMN location_attributes_websites.control_type_id IS 'Foreign key to the control_types table. Identifies the default type of control used for this attribute when used in this survey on a dynamically generated form.';

ALTER TABLE sample_attributes_websites ADD COLUMN form_structure_block_id integer NULL;
ALTER TABLE sample_attributes_websites ADD COLUMN validation_rules character varying(500) NULL;
ALTER TABLE sample_attributes_websites ADD COLUMN weight integer NOT NULL DEFAULT 0;
ALTER TABLE sample_attributes_websites ADD COLUMN control_type_id integer NULL;

ALTER TABLE sample_attributes_websites
  ADD CONSTRAINT fk_sample_attributes_website_control_type FOREIGN KEY (control_type_id)
      REFERENCES control_types (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION;

ALTER TABLE sample_attributes_websites
  ADD CONSTRAINT fk_sample_attributes_website_form_structure_block FOREIGN KEY (form_structure_block_id)
      REFERENCES form_structure_blocks (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION;

COMMENT ON COLUMN sample_attributes_websites.form_structure_block_id IS 'Within the context of this website and survey, defines the blocks of custom attributes which define the form structure.';
COMMENT ON COLUMN sample_attributes_websites.form_structure_block_id IS 'Additional validation rules that are defined for this attribute but only active within the context of this survey/website.';
COMMENT ON COLUMN sample_attributes_websites.weight IS 'Dictates the order of controls within the block or at the top level. Controls with a higher weight will sink to the end of the list.';
COMMENT ON COLUMN sample_attributes_websites.control_type_id IS 'Foreign key to the control_types table. Identifies the default type of control used for this attribute when used in this survey on a dynamically generated form.';

ALTER TABLE occurrence_attributes_websites ADD COLUMN form_structure_block_id integer NULL;
ALTER TABLE occurrence_attributes_websites ADD COLUMN validation_rules character varying(500) NULL;
ALTER TABLE occurrence_attributes_websites ADD COLUMN weight integer NOT NULL DEFAULT 0;
ALTER TABLE occurrence_attributes_websites ADD COLUMN control_type_id integer NULL;

ALTER TABLE occurrence_attributes_websites
  ADD CONSTRAINT fk_occurrence_attributes_website_control_type FOREIGN KEY (control_type_id)
      REFERENCES control_types (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION;

ALTER TABLE occurrence_attributes_websites
  ADD CONSTRAINT fk_occurrence_attributes_website_form_structure_block FOREIGN KEY (form_structure_block_id)
      REFERENCES form_structure_blocks (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION;
      
COMMENT ON COLUMN occurrence_attributes_websites.form_structure_block_id IS 'Within the context of this website and survey, defines the blocks of custom attributes which define the form structure.';
COMMENT ON COLUMN occurrence_attributes_websites.form_structure_block_id IS 'Additional validation rules that are defined for this attribute but only active within the context of this survey/website.';
COMMENT ON COLUMN occurrence_attributes_websites.weight IS 'Dictates the order of controls within the block or at the top level. Controls with a higher weight will sink to the end of the list.';
COMMENT ON COLUMN occurrence_attributes_websites.control_type_id IS 'Foreign key to the control_types table. Identifies the default type of control used for this attribute when used in this survey on a dynamically generated form.';