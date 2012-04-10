ALTER TABLE occurrence_attributes_websites ADD COLUMN default_text_value text;
ALTER TABLE occurrence_attributes_websites ADD COLUMN default_float_value double precision;
ALTER TABLE occurrence_attributes_websites ADD COLUMN default_int_value integer;
ALTER TABLE occurrence_attributes_websites ADD COLUMN default_date_start_value date;
ALTER TABLE occurrence_attributes_websites ADD COLUMN default_date_end_value date;
ALTER TABLE occurrence_attributes_websites ADD COLUMN default_date_type_value character varying(2);

COMMENT ON COLUMN occurrence_attributes_websites.default_text_value IS 'For default text values, provides the value.';
COMMENT ON COLUMN occurrence_attributes_websites.default_float_value IS 'For default float values, provides the value.';
COMMENT ON COLUMN occurrence_attributes_websites.default_int_value IS 'For default integer values, provides the value. For default lookup values, provides the term id. ';
COMMENT ON COLUMN occurrence_attributes_websites.default_date_start_value IS 'For default vague date and date values, provides the start date of the range of dates covered by the date.';
COMMENT ON COLUMN occurrence_attributes_websites.default_date_end_value IS 'For default vague date and date values, provides the start date of the range of dates covered by the date.';
COMMENT ON COLUMN occurrence_attributes_websites.default_date_type_value IS 'For default vague date values, provides the date type identifier.';

ALTER TABLE sample_attributes_websites ADD COLUMN default_text_value text;
ALTER TABLE sample_attributes_websites ADD COLUMN default_float_value double precision;
ALTER TABLE sample_attributes_websites ADD COLUMN default_int_value integer;
ALTER TABLE sample_attributes_websites ADD COLUMN default_date_start_value date;
ALTER TABLE sample_attributes_websites ADD COLUMN default_date_end_value date;
ALTER TABLE sample_attributes_websites ADD COLUMN default_date_type_value character varying(2);

COMMENT ON COLUMN sample_attributes_websites.default_text_value IS 'For default text values, provides the value.';
COMMENT ON COLUMN sample_attributes_websites.default_float_value IS 'For default float values, provides the value.';
COMMENT ON COLUMN sample_attributes_websites.default_int_value IS 'For default integer values, provides the value. For default lookup values, provides the term id. ';
COMMENT ON COLUMN sample_attributes_websites.default_date_start_value IS 'For default vague date and date values, provides the start date of the range of dates covered by the date.';
COMMENT ON COLUMN sample_attributes_websites.default_date_end_value IS 'For default vague date and date values, provides the start date of the range of dates covered by the date.';
COMMENT ON COLUMN sample_attributes_websites.default_date_type_value IS 'For default vague date values, provides the date type identifier.';

ALTER TABLE location_attributes_websites ADD COLUMN default_text_value text;
ALTER TABLE location_attributes_websites ADD COLUMN default_float_value double precision;
ALTER TABLE location_attributes_websites ADD COLUMN default_int_value integer;
ALTER TABLE location_attributes_websites ADD COLUMN default_date_start_value date; 
ALTER TABLE location_attributes_websites ADD COLUMN default_date_end_value date;
ALTER TABLE location_attributes_websites ADD COLUMN default_date_type_value character varying(2);

COMMENT ON COLUMN location_attributes_websites.default_text_value IS 'For default text values, provides the value.';
COMMENT ON COLUMN location_attributes_websites.default_float_value IS 'For default float values, provides the value.';
COMMENT ON COLUMN location_attributes_websites.default_int_value IS 'For default integer values, provides the value. For default lookup values, provides the term id. ';
COMMENT ON COLUMN location_attributes_websites.default_date_start_value IS 'For default vague date and date values, provides the start date of the range of dates covered by the date.';
COMMENT ON COLUMN location_attributes_websites.default_date_end_value IS 'For default vague date and date values, provides the start date of the range of dates covered by the date.';
COMMENT ON COLUMN location_attributes_websites.default_date_type_value IS 'For default vague date values, provides the date type identifier.';