
CREATE OR REPLACE VIEW list_occurrence_attribute_values AS 
SELECT oav.id, o.id AS occurrence_id, oa.id as occurrence_attribute_id,
CASE oa.data_type
    WHEN 'T'::bpchar THEN 'Text'::bpchar
    WHEN 'L'::bpchar THEN 'Lookup List'::bpchar
    WHEN 'I'::bpchar THEN 'Integer'::bpchar
    WHEN 'F'::bpchar THEN 'Float'::bpchar
    WHEN 'D'::bpchar THEN 'Specific Date'::bpchar
    WHEN 'V'::bpchar THEN 'Vague Date'::bpchar
    ELSE oa.data_type
END as data_type,
oa.caption, 
CASE oa.data_type
    WHEN 'T'::bpchar THEN oav.text_value
    WHEN 'L'::bpchar THEN t.term
    WHEN 'I'::bpchar THEN CAST(oav.int_value AS character varying)
    WHEN 'F'::bpchar THEN CAST(oav.float_value AS character varying)
    WHEN 'D'::bpchar THEN CAST(oav.date_start_value AS character varying)
    WHEN 'V'::bpchar THEN CAST(oav.date_start_value AS character varying) || ' - ' || CAST(oav.date_end_value AS character varying)
END as value,
oa.termlist_id
FROM occurrences o
JOIN samples s 
	ON s.id=o.sample_id
	AND s.deleted='f'
JOIN surveys su 
	ON su.id=s.survey_id
	AND su.deleted='f'
JOIN occurrence_attributes_websites oaw 
	ON oaw.website_id=su.website_id
	AND (oaw.restrict_to_survey_id=su.id OR oaw.id IS NULL)
	AND oaw.deleted='f'
JOIN occurrence_attributes oa 
	ON oa.id=oaw.occurrence_attribute_id
	AND oa.deleted='f'
LEFT JOIN occurrence_attribute_values oav 
	ON oav.occurrence_attribute_id=oa.id
	AND oav.occurrence_id=o.id
	AND oav.deleted='f'
LEFT JOIN (termlists_terms tt 
		JOIN terms t ON t.id=tt.term_id)
	ON tt.id=oav.int_value
	AND oa.data_type='L' -- lookup
WHERE o.deleted='f'
ORDER BY oa.id;

CREATE OR REPLACE VIEW list_sample_attribute_values AS 
SELECT sav.id, s.id AS sample_id, sa.id as sample_attribute_id,
CASE sa.data_type
    WHEN 'T'::bpchar THEN 'Text'::bpchar
    WHEN 'L'::bpchar THEN 'Lookup List'::bpchar
    WHEN 'I'::bpchar THEN 'Integer'::bpchar
    WHEN 'F'::bpchar THEN 'Float'::bpchar
    WHEN 'D'::bpchar THEN 'Specific Date'::bpchar
    WHEN 'V'::bpchar THEN 'Vague Date'::bpchar
    ELSE sa.data_type
END as data_type,
sa.caption, 
CASE sa.data_type
    WHEN 'T'::bpchar THEN sav.text_value
    WHEN 'L'::bpchar THEN t.term
    WHEN 'I'::bpchar THEN CAST(sav.int_value AS character varying)
    WHEN 'F'::bpchar THEN CAST(sav.float_value AS character varying)
    WHEN 'D'::bpchar THEN CAST(sav.date_start_value AS character varying)
    WHEN 'V'::bpchar THEN CAST(sav.date_start_value AS character varying) || ' - ' || CAST(sav.date_end_value AS character varying)
END as value,
sa.termlist_id
FROM samples s
JOIN surveys su 
	ON su.id=s.survey_id
	AND su.deleted='f'
JOIN sample_attributes_websites saw 
	ON saw.website_id=su.website_id
	AND (saw.restrict_to_survey_id=su.id OR saw.id IS NULL)
	AND saw.deleted='f'
JOIN sample_attributes sa 
	ON sa.id=saw.sample_attribute_id
	AND sa.deleted='f'
LEFT JOIN sample_attribute_values sav 
	ON sav.sample_attribute_id=sa.id
	AND sav.sample_id=s.id
	AND sav.deleted='f'
LEFT JOIN (termlists_terms tt 
		JOIN terms t ON t.id=tt.term_id)
	ON tt.id=sav.int_value
	AND sa.data_type='L' -- lookup
WHERE s.deleted='f'
ORDER BY sa.id;