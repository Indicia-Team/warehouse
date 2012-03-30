DROP VIEW IF EXISTS gv_occurrence_attributes;
DROP VIEW IF EXISTS gv_location_attributes;
DROP VIEW IF EXISTS gv_sample_attributes;

CREATE OR REPLACE VIEW gv_occurrence_attributes AS
 SELECT oaw.id,
 		oaw.website_id,
 		oaw.restrict_to_survey_id as survey_id,
 		w.title as website,
 		s.title as survey,
 		oa.caption as caption,
 		CASE oa.data_type
 				WHEN 'T' THEN 'Text'
				WHEN 'L' THEN 'Lookup List'
				WHEN 'I' THEN 'Integer'
				WHEN 'F' THEN 'Float'
				WHEN 'D' THEN 'Specific Date'
				WHEN 'V' THEN 'Vague Date'
				ELSE oa.data_type
 			END as data_type
   FROM occurrence_attributes_websites oaw
   LEFT JOIN occurrence_attributes oa on oa.id = oaw.occurrence_attribute_id
   LEFT JOIN websites w on w.id = oaw.website_id
   LEFT JOIN surveys s on s.id = oaw.restrict_to_survey_id;   
   ;

CREATE OR REPLACE VIEW gv_location_attributes AS
 SELECT law.id,
 		law.website_id,
 		law.restrict_to_survey_id as survey_id,
 		w.title as website,
 		s.title as survey,
 		la.caption as caption,
 		CASE la.data_type
 				WHEN 'T' THEN 'Text'
				WHEN 'L' THEN 'Lookup List'
				WHEN 'I' THEN 'Integer'
				WHEN 'F' THEN 'Float'
				WHEN 'D' THEN 'Specific Date'
				WHEN 'V' THEN 'Vague Date'
				ELSE la.data_type
 			END as data_type
   FROM location_attributes_websites law
   LEFT JOIN location_attributes la on la.id = law.location_attribute_id
   LEFT JOIN websites w on w.id = law.website_id
   LEFT JOIN surveys s on s.id = law.restrict_to_survey_id;   
   ;

CREATE OR REPLACE VIEW gv_sample_attributes AS
 SELECT saw.id,
 		saw.website_id,
 		saw.restrict_to_survey_id as survey_id,
 		w.title as website,
 		s.title as survey,
 		sa.caption as caption,
 		CASE sa.data_type
 				WHEN 'T' THEN 'Text'
				WHEN 'L' THEN 'Lookup List'
				WHEN 'I' THEN 'Integer'
				WHEN 'F' THEN 'Float'
				WHEN 'D' THEN 'Specific Date'
				WHEN 'V' THEN 'Vague Date'
				ELSE sa.data_type
 			END as data_type
   FROM sample_attributes_websites saw
   LEFT JOIN sample_attributes sa on sa.id = saw.sample_attribute_id
   LEFT JOIN websites w on w.id = saw.website_id
   LEFT JOIN surveys s on s.id = saw.restrict_to_survey_id;   
   ;
