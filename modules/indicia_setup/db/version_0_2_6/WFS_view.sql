DROP VIEW SPIPOLL_COLLECTIONS ;

CREATE OR REPLACE VIEW SPIPOLL_COLLECTIONS as
select s.id,
	s.date_start,
	s.date_end,
	l.centroid_geom as geom,
	li.path as location_image_path,
	oi.path as flower_image_path,
	d.taxa_taxon_list_id as flower_id,
	sav0.int_value as completed,
	sav2.text_value as owner, sav1.int_value as owner_ref,
	to_char(s.updated_on, 'YYYY-MM-DD') as updated_on,
	0 as flower_type,
	0 as flower_shade,
	'|0|'::text as habitat
from samples s
INNER JOIN locations l ON l.id = s.location_id
INNER JOIN location_images li ON l.id = li.location_id
INNER JOIN occurrences o ON o.sample_id = s.id and o.deleted = 'f' 
INNER JOIN determinations d ON o.id = d.occurrence_id and d.id = (select max(d2.id) from determinations d2 where d2.occurrence_id = o.id)
INNER JOIN occurrence_images oi ON o.id = oi.occurrence_id
INNER JOIN sample_attribute_values sav0 ON s.id = sav0.sample_id and sav0.deleted = 'f' and sav0.sample_attribute_id = 30 --completeness;
INNER JOIN sample_attribute_values sav1 ON s.id = sav1.sample_id and sav1.deleted = 'f' and sav1.sample_attribute_id = 18 --cms ref;
INNER JOIN sample_attribute_values sav2 ON s.id = sav2.sample_id and sav2.deleted = 'f' and sav2.sample_attribute_id = 19 --cms username;
where s.parent_id is null and s.deleted = 'f';