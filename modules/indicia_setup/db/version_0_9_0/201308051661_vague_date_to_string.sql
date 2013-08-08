CREATE OR REPLACE FUNCTION date_to_season(date_string date)
  RETURNS character varying AS
$BODY$
DECLARE
  month_string varchar;
BEGIN
  month_string := to_char(date_string, 'MM');
  CASE month_string
	WHEN '03'::bpchar THEN
		RETURN 'Spring';
	WHEN '06'::bpchar THEN
		RETURN 'Summer';
	WHEN '09'::bpchar THEN
		RETURN 'Autumn';
	WHEN '12'::bpchar THEN
		RETURN 'Winter';
	END CASE;
	RETURN 'Unknown season';
END
$BODY$
  LANGUAGE plpgsql IMMUTABLE;

COMMENT ON FUNCTION date_to_season(date_string date) IS 'Returns the season of the given date.';
 
CREATE OR REPLACE FUNCTION vague_date_to_string(date_start date, date_end date, date_type character varying)
  RETURNS character varying AS
$BODY$
DECLARE
  DAY_FORMAT CONSTANT varchar := 'DD/MM/YYYY';
  YEAR_FORMAT CONSTANT varchar := 'YYYY';
  MONTH_FORMAT CONSTANT varchar := 'MM/YYYY';
BEGIN
  CASE date_type
	WHEN 'D'::bpchar THEN
		RETURN to_char(date_start, DAY_FORMAT);
	WHEN 'DD'::bpchar THEN
		RETURN to_char(date_start, DAY_FORMAT) || ' to '::text || to_char(date_end, DAY_FORMAT);
	WHEN 'O'::bpchar THEN
		RETURN to_char(date_start, MONTH_FORMAT);
	WHEN 'OO'::bpchar THEN
		RETURN to_char(date_start, MONTH_FORMAT) || ' to '::text || to_char(date_end, MONTH_FORMAT);
	WHEN 'Y'::bpchar THEN
		RETURN to_char(date_start, YEAR_FORMAT);
	WHEN 'YY'::bpchar THEN
		RETURN to_char(date_start, YEAR_FORMAT) || ' to '::text || to_char(date_end, YEAR_FORMAT);
	WHEN 'Y-'::bpchar THEN
		RETURN 'From ' || to_char(date_start, YEAR_FORMAT);
	WHEN '-Y'::bpchar THEN
		RETURN 'To ' || to_char(date_end, YEAR_FORMAT);
	WHEN 'M'::bpchar THEN
		RETURN to_char(date_start, 'Month');
	WHEN 'U'::bpchar THEN
		RETURN 'Unknown';
	WHEN 'C'::bpchar THEN
		RETURN to_char(date_start, 'CC') || 'c';
	WHEN 'CC'::bpchar THEN
		RETURN to_char(date_start, 'CC') || 'c to ' || to_char(date_end, 'CC');
	WHEN 'C-'::bpchar THEN
		RETURN 'From ' || to_char(date_start, 'CC');
	WHEN '-C'::bpchar THEN
		RETURN 'To ' || to_char(date_end, 'CC');
	WHEN 'S'::bpchar THEN
		RETURN date_to_season(date_start);
	WHEN 'P'::bpchar THEN
		RETURN date_to_season(date_start) || ' ' || to_char(date_start, YEAR_FORMAT);
	ELSE
		RETURN NULL::TEXT;
	END CASE;
END
$BODY$
  LANGUAGE plpgsql IMMUTABLE;

COMMENT ON FUNCTION vague_date_to_string(date_start date, date_end date, date_type character varying) IS 'Formats a vague date to a string.';

CREATE OR REPLACE FUNCTION vague_date_to_raw_string(date_start date, date_end date, date_type character varying)
  RETURNS character varying AS
$BODY$
DECLARE
  DAY_FORMAT CONSTANT varchar := 'YYYY-MM-DD';
BEGIN
  RETURN COALESCE(to_char(date_start, DAY_FORMAT), '') || '#' || COALESCE(to_char(date_end, DAY_FORMAT), '') || '#' || date_type;
END
$BODY$
  LANGUAGE plpgsql IMMUTABLE;
  
CREATE OR REPLACE VIEW list_person_attribute_values AS 
 SELECT pav.id, p.id AS person_id, pa.id AS person_attribute_id, 
        CASE pa.data_type
            WHEN 'T'::bpchar THEN 'Text'::bpchar
            WHEN 'L'::bpchar THEN 'Lookup List'::bpchar
            WHEN 'I'::bpchar THEN 'Integer'::bpchar
            WHEN 'B'::bpchar THEN 'Boolean'::bpchar
            WHEN 'F'::bpchar THEN 'Float'::bpchar
            WHEN 'D'::bpchar THEN 'Specific Date'::bpchar
            WHEN 'V'::bpchar THEN 'Vague Date'::bpchar
            ELSE pa.data_type
        END AS data_type, pa.caption, 
        CASE pa.data_type
            WHEN 'T'::bpchar THEN pav.text_value
            WHEN 'L'::bpchar THEN t.term::text
            WHEN 'I'::bpchar THEN pav.int_value::character varying::text
            WHEN 'B'::bpchar THEN pav.int_value::character varying::text
            WHEN 'F'::bpchar THEN pav.float_value::character varying::text
            WHEN 'D'::bpchar THEN pav.date_start_value::character varying::text
            WHEN 'V'::bpchar THEN vague_date_to_string(pav.date_start_value, pav.date_end_value, pav.date_type_value)
            ELSE NULL::text
        END AS value, 
        CASE pa.data_type
            WHEN 'T'::bpchar THEN pav.text_value
            WHEN 'L'::bpchar THEN pav.int_value::character varying::text
            WHEN 'I'::bpchar THEN pav.int_value::character varying::text
            WHEN 'B'::bpchar THEN pav.int_value::character varying::text
            WHEN 'F'::bpchar THEN pav.float_value::character varying::text
            WHEN 'D'::bpchar THEN pav.date_start_value::character varying::text
            WHEN 'V'::bpchar THEN vague_date_to_raw_string(pav.date_start_value, pav.date_end_value, pav.date_type_value)
            ELSE NULL::text
        END AS raw_value, pa.termlist_id, l.iso, paw.website_id
   FROM people p
   LEFT JOIN person_attribute_values pav ON pav.person_id = p.id AND pav.deleted = false
   LEFT JOIN (users u
   JOIN users_websites uw ON uw.user_id = u.id AND uw.site_role_id IS NOT NULL
   JOIN person_attributes_websites paw ON paw.website_id = uw.website_id AND paw.deleted = false) ON u.person_id = p.id
   JOIN person_attributes pa ON (pa.id = COALESCE(pav.person_attribute_id, paw.person_attribute_id) OR pa.public = true) AND (pa.id = pav.person_attribute_id OR pav.id IS NULL) AND pa.deleted = false
   LEFT JOIN (termlists_terms tt
   JOIN terms t ON t.id = tt.term_id
   JOIN languages l ON l.id = t.language_id) ON tt.meaning_id = pav.int_value AND pa.data_type = 'L'::bpchar
  WHERE p.deleted = false
  ORDER BY pa.id;
  
CREATE OR REPLACE VIEW list_occurrence_attribute_values AS 
 SELECT oav.id, o.id AS occurrence_id, oa.id AS occurrence_attribute_id, 
        CASE oa.data_type
            WHEN 'T'::bpchar THEN 'Text'::bpchar
            WHEN 'L'::bpchar THEN 'Lookup List'::bpchar
            WHEN 'I'::bpchar THEN 'Integer'::bpchar
            WHEN 'B'::bpchar THEN 'Boolean'::bpchar
            WHEN 'F'::bpchar THEN 'Float'::bpchar
            WHEN 'D'::bpchar THEN 'Specific Date'::bpchar
            WHEN 'V'::bpchar THEN 'Vague Date'::bpchar
            ELSE oa.data_type
        END AS data_type, oa.caption, 
        CASE oa.data_type
            WHEN 'T'::bpchar THEN oav.text_value
            WHEN 'L'::bpchar THEN t.term::text
            WHEN 'I'::bpchar THEN oav.int_value::character varying::text
            WHEN 'B'::bpchar THEN oav.int_value::character varying::text
            WHEN 'F'::bpchar THEN oav.float_value::character varying::text
            WHEN 'D'::bpchar THEN oav.date_start_value::character varying::text
            WHEN 'V'::bpchar THEN vague_date_to_string(oav.date_start_value, oav.date_end_value, oav.date_type_value)
            ELSE NULL::text
        END AS value, 
        CASE oa.data_type
            WHEN 'T'::bpchar THEN oav.text_value
            WHEN 'L'::bpchar THEN oav.int_value::character varying::text
            WHEN 'I'::bpchar THEN oav.int_value::character varying::text
            WHEN 'B'::bpchar THEN oav.int_value::character varying::text
            WHEN 'F'::bpchar THEN oav.float_value::character varying::text
            WHEN 'D'::bpchar THEN oav.date_start_value::character varying::text
            WHEN 'V'::bpchar THEN vague_date_to_raw_string(oav.date_start_value, oav.date_end_value, oav.date_type_value)
            ELSE NULL::text
        END AS raw_value, oa.termlist_id, l.iso, oaw.website_id
   FROM occurrences o
   JOIN samples s ON s.id = o.sample_id AND s.deleted = false
   JOIN surveys su ON su.id = s.survey_id AND su.deleted = false
   JOIN occurrence_attributes_websites oaw ON oaw.website_id = su.website_id AND (oaw.restrict_to_survey_id = su.id OR oaw.restrict_to_survey_id IS NULL) AND oaw.deleted = false
   JOIN occurrence_attributes oa ON oa.id = oaw.occurrence_attribute_id AND oa.deleted = false
   LEFT JOIN occurrence_attribute_values oav ON oav.occurrence_attribute_id = oa.id AND oav.occurrence_id = o.id AND oav.deleted = false
   LEFT JOIN (termlists_terms tt
   JOIN terms t ON t.id = tt.term_id
   JOIN languages l ON l.id = t.language_id) ON tt.id = oav.int_value AND oa.data_type = 'L'::bpchar
  WHERE o.deleted = false
  ORDER BY oa.id;
  
  CREATE OR REPLACE VIEW list_sample_attribute_values AS 
 SELECT sav.id, s.id AS sample_id, sa.id AS sample_attribute_id, 
        CASE sa.data_type
            WHEN 'T'::bpchar THEN 'Text'::bpchar
            WHEN 'L'::bpchar THEN 'Lookup List'::bpchar
            WHEN 'I'::bpchar THEN 'Integer'::bpchar
            WHEN 'B'::bpchar THEN 'Boolean'::bpchar
            WHEN 'F'::bpchar THEN 'Float'::bpchar
            WHEN 'D'::bpchar THEN 'Specific Date'::bpchar
            WHEN 'V'::bpchar THEN 'Vague Date'::bpchar
            ELSE sa.data_type
        END AS data_type, sa.caption, 
        CASE sa.data_type
            WHEN 'T'::bpchar THEN sav.text_value
            WHEN 'L'::bpchar THEN t.term::text
            WHEN 'I'::bpchar THEN sav.int_value::character varying::text
            WHEN 'B'::bpchar THEN sav.int_value::character varying::text
            WHEN 'F'::bpchar THEN sav.float_value::character varying::text
            WHEN 'D'::bpchar THEN sav.date_start_value::character varying::text
            WHEN 'V'::bpchar THEN vague_date_to_string(sav.date_start_value, sav.date_end_value, sav.date_type_value)
            ELSE NULL::text
        END AS value, 
        CASE sa.data_type
            WHEN 'T'::bpchar THEN sav.text_value
            WHEN 'L'::bpchar THEN sav.int_value::character varying::text
            WHEN 'I'::bpchar THEN sav.int_value::character varying::text
            WHEN 'B'::bpchar THEN sav.int_value::character varying::text
            WHEN 'F'::bpchar THEN sav.float_value::character varying::text
            WHEN 'D'::bpchar THEN sav.date_start_value::character varying::text
            WHEN 'V'::bpchar THEN vague_date_to_raw_string(sav.date_start_value, sav.date_end_value, sav.date_type_value)
            ELSE NULL::text
        END AS raw_value, sa.termlist_id, l.iso, saw.website_id
   FROM samples s
   JOIN surveys su ON su.id = s.survey_id AND su.deleted = false
   JOIN sample_attributes_websites saw ON saw.website_id = su.website_id AND (saw.restrict_to_survey_id = su.id OR saw.restrict_to_survey_id IS NULL) AND saw.deleted = false
   JOIN sample_attributes sa ON sa.id = saw.sample_attribute_id AND sa.deleted = false
   LEFT JOIN sample_attribute_values sav ON sav.sample_attribute_id = sa.id AND sav.sample_id = s.id AND sav.deleted = false
   LEFT JOIN (termlists_terms tt
   JOIN terms t ON t.id = tt.term_id
   JOIN languages l ON l.id = t.language_id) ON tt.id = sav.int_value AND sa.data_type = 'L'::bpchar
  WHERE s.deleted = false
  ORDER BY sa.id;