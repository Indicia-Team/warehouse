---
--- These should now all be legacy
---

DROP VIEW IF EXISTS POLL_COLL_FAST ;
CREATE OR REPLACE VIEW POLL_COLL_FAST as
select s.id as collection_id,
	s.survey_id,
	s.date_start,
	s.date_end,
	l.centroid_geom as geom,
	l.name as location_name,
	li.path as location_image_path,
	oi.path as flower_image_path,
	o.id as flower_id,
	sav1.sample_attribute_id as closed_attr_id,
	sav1.int_value as closed_attr_value,
	sav2.sample_attribute_id as username_attr_id,
	sav2.text_value as username_attr_value
from samples s
INNER JOIN locations l ON l.id = s.location_id and l.deleted = false
INNER JOIN location_images li ON l.id = li.location_id and li.deleted = false
INNER JOIN occurrences o ON o.sample_id = s.id and o.deleted = false 
INNER JOIN occurrence_images oi ON o.id = oi.occurrence_id and oi.deleted = false
INNER JOIN sample_attribute_values sav1 ON sav1.sample_id = s.id and sav1.deleted = false
INNER JOIN sample_attribute_values sav2 ON sav2.sample_id = s.id and sav2.deleted = false
where s.parent_id is null and s.deleted = false
ORDER BY s.id desc;

DROP VIEW IF EXISTS POLL_COLLECTIONS ;
CREATE OR REPLACE VIEW POLL_COLLECTIONS as
select s.id as collection_id,
	s.survey_id,
	s.date_start,
	s.date_end,
	l.centroid_geom as geom,
	l.name as location_name,
	li.path as location_image_path,
	oi.path as flower_image_path,
	o.id as flower_id,
	CASE WHEN d.taxa_taxon_list_id IS NOT NULL THEN (ARRAY['|'||d.taxa_taxon_list_id||'|']::text)::text
		ELSE (ARRAY(select '|'||unnest(d.taxa_taxon_list_id_list)::text||'|' from determinations d3 where d3.id = d.id))::text END as flower_taxon,
	d.taxon_extra_info as flower_extra_info,
	ARRAY(select (ARRAY['|' || sample_attribute_id || '|',
		CASE sa.data_type WHEN 'T' THEN text_value ELSE int_value::text END ])::text from sample_attribute_values sav, sample_attributes sa where sav.deleted = false and sa.deleted = false and sav.sample_id = s.id and sa.id = sav.sample_attribute_id)::text as collection_attributes,
	ARRAY(select (ARRAY['|' || location_attribute_id || '|',
		CASE la.data_type WHEN 'T' THEN text_value ELSE int_value::text END ])::text from location_attribute_values lav, location_attributes la where lav.deleted = false and la.deleted = false and lav.location_id = l.id and la.id = lav.location_attribute_id)::text as location_attributes,
	ARRAY(select (ARRAY['|' || occurrence_attribute_id || '|',
		CASE oa.data_type WHEN 'T' THEN text_value ELSE int_value::text END ])::text from occurrence_attribute_values oav, occurrence_attributes oa where oav.deleted = false and oa.deleted = false and oav.occurrence_id = o.id and oa.id = oav.occurrence_attribute_id)::text as flower_attributes
from samples s
INNER JOIN locations l ON l.id = s.location_id and l.deleted = false
INNER JOIN location_images li ON l.id = li.location_id and li.deleted = false
INNER JOIN occurrences o ON o.sample_id = s.id and o.deleted = false 
INNER JOIN determinations d ON o.id = d.occurrence_id and d.deleted = false and d.id = (select max(d2.id) from determinations d2 where d2.occurrence_id = o.id and d2.deleted = false)
INNER JOIN occurrence_images oi ON o.id = oi.occurrence_id and oi.deleted = false
where s.parent_id is null and s.deleted = false
ORDER BY s.id desc;

DROP VIEW IF EXISTS POLL_COLLECTION_INSECTS ;
CREATE OR REPLACE VIEW POLL_COLLECTION_INSECTS as
select s.id as collection_id,
	s.survey_id,
	s.date_start,
	s.date_end,
	l.centroid_geom as geom,
	l.name as location_name,
	li.path as location_image_path,
	fi.path as flower_image_path,
	f.id as flower_id,
	CASE WHEN fd.taxa_taxon_list_id IS NOT NULL THEN (ARRAY['|'||fd.taxa_taxon_list_id||'|']::text)::text
		ELSE (ARRAY(select '|'||unnest(fd.taxa_taxon_list_id_list)::text||'|' from determinations fd3 where fd3.id = f.id))::text END as flower_taxon,
	fd.taxon_extra_info as flower_extra_info,
	ARRAY(select (ARRAY['|' || sample_attribute_id || '|',
		CASE sa.data_type WHEN 'T' THEN text_value ELSE int_value::text END ])::text from sample_attribute_values sav, sample_attributes sa where sav.deleted = false and sa.deleted = false and sav.sample_id = s.id and sa.id = sav.sample_attribute_id)::text as collection_attributes,
	ARRAY(select (ARRAY['|' || location_attribute_id || '|',
		CASE la.data_type WHEN 'T' THEN text_value ELSE int_value::text END ])::text from location_attribute_values lav, location_attributes la where lav.deleted = false and la.deleted = false and lav.location_id = l.id and la.id = lav.location_attribute_id)::text as location_attributes,
	ARRAY(select (ARRAY['|' || occurrence_attribute_id || '|',
		CASE oa.data_type WHEN 'T' THEN text_value ELSE int_value::text END ])::text from occurrence_attribute_values oav, occurrence_attributes oa where oav.deleted = false and oa.deleted = false and oav.occurrence_id = f.id and oa.id = oav.occurrence_attribute_id)::text as flower_attributes,
	ARRAY(select (ARRAY['|' || sample_attribute_id || '|',
		CASE sa2.data_type WHEN 'T' THEN text_value ELSE int_value::text END ])::text from sample_attribute_values sav2, sample_attributes sa2 where sav2.deleted = false and sa2.deleted = false and sav2.sample_id = sessions.id and sa2.id = sav2.sample_attribute_id)::text as session_attributes,
	ARRAY(select id4.taxon_extra_info
		from occurrences i4, determinations id4 where i4.deleted = false and id4.deleted = false and i4.sample_id = sessions.id and i4.id = id4.occurrence_id and id4.id = (select max(id5.id) from determinations id5 where id5.occurrence_id = i4.id and id5.deleted = false))::text as insect_extra_info,
	ARRAY(select (CASE WHEN id.taxa_taxon_list_id IS NOT NULL THEN (ARRAY['|'||id.taxa_taxon_list_id||'|']::text)::text
			ELSE (ARRAY(select '|'||unnest(id.taxa_taxon_list_id_list)::text||'|' from determinations id3 where id3.id = i.id))::text END) as insect_taxon
		from occurrences i, determinations id where i.deleted = false and id.deleted = false and i.sample_id = sessions.id and i.id = id.occurrence_id  and id.id = (select max(id2.id) from determinations id2 where id2.occurrence_id = i.id and id2.deleted = false))::text as insects
from samples s
INNER JOIN locations l ON l.id = s.location_id and l.deleted = false
INNER JOIN location_images li ON l.id = li.location_id and li.deleted = false
INNER JOIN occurrences f ON f.sample_id = s.id and f.deleted = false 
INNER JOIN determinations fd ON f.id = fd.occurrence_id and fd.deleted = false and fd.id = (select max(fd2.id) from determinations fd2 where fd2.occurrence_id = f.id and fd2.deleted = false)
INNER JOIN occurrence_images fi ON f.id = fi.occurrence_id and fi.deleted = false
INNER JOIN samples sessions ON sessions.parent_id = s.id AND sessions.deleted = false
where s.parent_id is null and s.deleted = false
ORDER BY s.id desc;

DROP VIEW IF EXISTS POLL_INSECTS ;
CREATE OR REPLACE VIEW POLL_INSECTS as
select i.id as insect_id,
	s.id as collection_id,
	s.survey_id,
	s.date_start,
	s.date_end,
	l.centroid_geom as geom,
	ii.path as insect_image_path,
	CASE WHEN fd.taxa_taxon_list_id IS NOT NULL THEN (ARRAY['|'||fd.taxa_taxon_list_id||'|']::text)::text
		ELSE (ARRAY(select '|'||unnest(fd.taxa_taxon_list_id_list)::text||'|' from determinations fd3 where fd3.id = f.id))::text END as flower_taxon,
	fd.taxon_extra_info as flower_extra_info,
	CASE WHEN id.taxa_taxon_list_id IS NOT NULL THEN (ARRAY['|'||id.taxa_taxon_list_id||'|']::text)::text
		ELSE (ARRAY(select '|'||unnest(id.taxa_taxon_list_id_list)::text||'|' from determinations id3 where id3.id = i.id))::text END as insect_taxon,
	id.taxon_extra_info as insect_extra_info,
	ARRAY(select (ARRAY['|' || sample_attribute_id || '|',
		CASE sa.data_type WHEN 'T' THEN text_value ELSE int_value::text END ])::text from sample_attribute_values sav, sample_attributes sa where sav.deleted = false and sa.deleted = false and sav.sample_id = s.id and sa.id = sav.sample_attribute_id)::text as collection_attributes,
	ARRAY(select (ARRAY['|' || location_attribute_id || '|',
		CASE la.data_type WHEN 'T' THEN text_value ELSE int_value::text END ])::text from location_attribute_values lav, location_attributes la where lav.deleted = false and la.deleted = false and lav.location_id = l.id and la.id = lav.location_attribute_id)::text as location_attributes,
	ARRAY(select (ARRAY['|' || occurrence_attribute_id || '|',
		CASE oa.data_type WHEN 'T' THEN text_value ELSE int_value::text END ])::text from occurrence_attribute_values oav, occurrence_attributes oa where oav.deleted = false and oa.deleted = false and oav.occurrence_id = f.id and oa.id = oav.occurrence_attribute_id)::text as flower_attributes,
	ARRAY(select (ARRAY['|' || sample_attribute_id || '|',
		CASE sa2.data_type WHEN 'T' THEN text_value ELSE int_value::text END ])::text from sample_attribute_values sav2, sample_attributes sa2 where sav2.deleted = false and sa2.deleted = false and sav2.sample_id = sessions.id and sa2.id = sav2.sample_attribute_id)::text as session_attributes
from samples s
INNER JOIN locations l ON l.id = s.location_id and l.deleted = false
INNER JOIN location_images li ON l.id = li.location_id and li.deleted = false
INNER JOIN occurrences f ON f.sample_id = s.id and f.deleted = false 
INNER JOIN determinations fd ON f.id = fd.occurrence_id and fd.deleted = false and fd.id = (select max(fd2.id) from determinations fd2 where fd2.occurrence_id = f.id and fd2.deleted = false)
INNER JOIN samples sessions ON sessions.parent_id = s.id AND sessions.deleted = false
INNER JOIN occurrences i ON i.sample_id = sessions.id and i.deleted = false 
INNER JOIN occurrence_images ii ON i.id = ii.occurrence_id and ii.deleted = false
LEFT OUTER JOIN determinations id ON i.id = id.occurrence_id and id.deleted = false and id.id = (select max(id2.id) from determinations id2 where id2.occurrence_id = i.id and id2.deleted = false)
where s.parent_id is null and s.deleted = false
ORDER BY i.id desc;