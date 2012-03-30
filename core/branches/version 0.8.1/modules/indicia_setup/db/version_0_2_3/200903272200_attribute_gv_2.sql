
--
-- Definition for view gv_occurrence_attributes :
--
DROP VIEW IF EXISTS gv_occurrence_attributes;
CREATE VIEW gv_occurrence_attributes AS
SELECT oa.id, oaw.website_id, oaw.restrict_to_survey_id AS survey_id,
    w.title AS website, s.title AS survey, oa.caption, CASE oa.data_type
 				WHEN 'T' THEN 'Text'
				WHEN 'L' THEN 'Lookup List'
				WHEN 'I' THEN 'Integer'
				WHEN 'F' THEN 'Float'
				WHEN 'D' THEN 'Specific Date'
				WHEN 'V' THEN 'Vague Date'
				ELSE oa.data_type
 			END as data_type, oa.public, oa.created_by_id
FROM occurrence_attributes oa 
	INNER JOIN occurrence_attributes_websites oaw ON (oa.id = oaw.occurrence_attribute_id and oaw.deleted = 'f')
	LEFT JOIN websites w ON (w.id = oaw.website_id)
	LEFT JOIN surveys s ON (s.id = oaw.restrict_to_survey_id)
UNION
SELECT oa.id, cast (null as integer) as website_id,
	cast (null as integer) as survey_id, cast (null as text) as website,
	cast (null as text) as survey, oa.caption, CASE oa.data_type
 				WHEN 'T' THEN 'Text'
				WHEN 'L' THEN 'Lookup List'
				WHEN 'I' THEN 'Integer'
				WHEN 'F' THEN 'Float'
				WHEN 'D' THEN 'Specific Date'
				WHEN 'V' THEN 'Vague Date'
				ELSE oa.data_type
 			END as data_type, oa.public, oa.created_by_id
FROM occurrence_attributes oa 
;

--
-- Definition for view gv_location_attributes :
--
DROP VIEW IF EXISTS gv_location_attributes;
CREATE VIEW gv_location_attributes AS
SELECT la.id, law.website_id, law.restrict_to_survey_id AS survey_id,
    w.title AS website, s.title AS survey, la.caption, CASE la.data_type
 				WHEN 'T' THEN 'Text'
				WHEN 'L' THEN 'Lookup List'
				WHEN 'I' THEN 'Integer'
				WHEN 'F' THEN 'Float'
				WHEN 'D' THEN 'Specific Date'
				WHEN 'V' THEN 'Vague Date'
				ELSE la.data_type
 			END as data_type, la.public, la.created_by_id
FROM location_attributes la 
	INNER JOIN location_attributes_websites law ON (la.id = law.location_attribute_id and law.deleted = 'f')
	LEFT JOIN websites w ON (w.id = law.website_id)
	LEFT JOIN surveys s ON (s.id = law.restrict_to_survey_id)
UNION
SELECT la.id, cast (null as integer) as website_id,
	cast (null as integer) as survey_id, cast (null as text) as website,
	cast (null as text) as survey, la.caption, CASE la.data_type
 				WHEN 'T' THEN 'Text'
				WHEN 'L' THEN 'Lookup List'
				WHEN 'I' THEN 'Integer'
				WHEN 'F' THEN 'Float'
				WHEN 'D' THEN 'Specific Date'
				WHEN 'V' THEN 'Vague Date'
				ELSE la.data_type
 			END as data_type, la.public, la.created_by_id
FROM location_attributes la 
;

--
-- Definition for view gv_sample_attributes :
--
DROP VIEW IF EXISTS gv_sample_attributes;
CREATE VIEW gv_sample_attributes AS
SELECT sa.id, saw.website_id, saw.restrict_to_survey_id AS survey_id,
    w.title AS website, s.title AS survey, sa.caption, CASE sa.data_type
 				WHEN 'T' THEN 'Text'
				WHEN 'L' THEN 'Lookup List'
				WHEN 'I' THEN 'Integer'
				WHEN 'F' THEN 'Float'
				WHEN 'D' THEN 'Specific Date'
				WHEN 'V' THEN 'Vague Date'
				ELSE sa.data_type
 			END as data_type, sa.public, sa.created_by_id
FROM sample_attributes sa 
	INNER JOIN sample_attributes_websites saw ON (sa.id = saw.sample_attribute_id and saw.deleted = 'f')
	LEFT JOIN websites w ON (w.id = saw.website_id)
	LEFT JOIN surveys s ON (s.id = saw.restrict_to_survey_id)
UNION
SELECT sa.id, cast (null as integer) as website_id,
	cast (null as integer) as survey_id, cast (null as text) as website,
	cast (null as text) as survey, sa.caption, CASE sa.data_type
 				WHEN 'T' THEN 'Text'
				WHEN 'L' THEN 'Lookup List'
				WHEN 'I' THEN 'Integer'
				WHEN 'F' THEN 'Float'
				WHEN 'D' THEN 'Specific Date'
				WHEN 'V' THEN 'Vague Date'
				ELSE sa.data_type
 			END as data_type, sa.public, sa.created_by_id
FROM sample_attributes sa 
;