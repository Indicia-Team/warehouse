/* Change the taxon field length to 200. But there are a lot of dependent views... */

DROP VIEW detail_occurrences;

DROP VIEW detail_taxa_taxon_lists;

DROP VIEW grid_occurrences_osgb_100k;

DROP VIEW grid_occurrences_osgb_10k;

DROP VIEW gv_occurrences;

DROP VIEW gv_taxon_lists_taxa;

DROP VIEW list_determinations;

DROP VIEW list_occurrences;

DROP VIEW list_taxa_taxon_lists;

ALTER TABLE taxa ALTER taxon TYPE character varying(200);

CREATE OR REPLACE VIEW detail_occurrences AS 
 SELECT o.id, o.confidential, o.comment, o.taxa_taxon_list_id, o.determiner_id, t.taxon, s.entered_sref, s.entered_sref_system, s.geom, s.location_name, s.survey_id, s.date_start, s.date_end, s.date_type, s.location_id, l.name AS location, l.code AS location_code, s.recorder_names, (d.first_name::text || ' '::text) || d.surname::text AS determiner, o.website_id, o.created_by_id, c.username AS created_by, o.created_on, o.updated_by_id, u.username AS updated_by, o.updated_on, o.record_status, o.downloaded_flag, o.sample_id, o.deleted
   FROM occurrences o
   JOIN samples s ON s.id = o.sample_id
   LEFT JOIN people d ON d.id = o.determiner_id
   LEFT JOIN locations l ON l.id = s.location_id
   LEFT JOIN taxa_taxon_lists ttl ON ttl.id = o.taxa_taxon_list_id
   LEFT JOIN taxa t ON t.id = ttl.taxon_id
   LEFT JOIN surveys su ON s.survey_id = su.id
   JOIN users c ON c.id = o.created_by_id
   JOIN users u ON u.id = o.updated_by_id;

CREATE OR REPLACE VIEW detail_taxa_taxon_lists AS 
 SELECT ttl.id, ttl.taxon_id, t.taxon, t.authority, l.iso AS language_iso, tc.taxon AS common, ttl.taxon_list_id, tl.title AS taxon_list, ttl.taxon_meaning_id, ttl.preferred, ttl.taxonomic_sort_order, ttl.description AS description_in_list, t.description AS general_description, ttl.parent_id, tp.taxon AS parent, ti.path AS image_path, ti.caption AS image_caption, ttl.created_by_id, c.username AS created_by, ttl.updated_by_id, u.username AS updated_by
   FROM taxa_taxon_lists ttl
   JOIN taxon_lists tl ON tl.id = ttl.taxon_list_id
   JOIN taxa t ON t.id = ttl.taxon_id AND t.deleted = false
   JOIN languages l ON l.id = t.language_id
   LEFT JOIN taxa tc ON tc.id = ttl.common_taxon_id AND tc.deleted = false
   JOIN users c ON c.id = ttl.created_by_id
   JOIN users u ON u.id = ttl.updated_by_id
   LEFT JOIN taxa_taxon_lists ttlp ON ttlp.id = ttl.parent_id AND ttlp.deleted = false
   LEFT JOIN taxa tp ON tp.id = ttlp.taxon_id AND tp.deleted = false
   LEFT JOIN taxon_images ti ON ti.taxon_meaning_id = ttl.taxon_meaning_id AND ti.deleted = false
  WHERE ttl.deleted = false;

CREATE OR REPLACE VIEW grid_occurrences_osgb_100k AS 
 SELECT t.taxon, grid.square, grid.geom, o.id AS occurrence_id, s.id AS sample_id, ttl.id AS taxa_taxon_list_id, tl.title AS taxon_list
   FROM occurrences o
   JOIN samples s ON s.id = o.sample_id
   JOIN grids_osgb_100k grid ON st_intersects(grid.geom, st_transform(s.geom, 27700))
   JOIN taxa_taxon_lists ttl ON ttl.id = o.taxa_taxon_list_id
   JOIN taxa t ON t.id = ttl.taxon_id
   JOIN taxon_lists tl ON tl.id = ttl.taxon_list_id;

CREATE OR REPLACE VIEW grid_occurrences_osgb_10k AS 
 SELECT t.taxon, grid.square, grid.geom, o.id AS occurrence_id, s.id AS sample_id, ttl.id AS taxa_taxon_list_id, tl.title AS taxon_list
   FROM occurrences o
   JOIN samples s ON s.id = o.sample_id
   JOIN grids_osgb_10k grid ON st_intersects(grid.geom, st_transform(s.geom, 27700))
   JOIN taxa_taxon_lists ttl ON ttl.id = o.taxa_taxon_list_id
   JOIN taxa t ON t.id = ttl.taxon_id
   JOIN taxon_lists tl ON tl.id = ttl.taxon_list_id;

CREATE OR REPLACE VIEW gv_occurrences AS 
 SELECT o.id, w.title AS website, s.title AS survey, o.sample_id, t.taxon, sa.date_start, sa.date_end, sa.date_type, sa.entered_sref, sa.entered_sref_system, sa.location_name, l.name, o.deleted, o.website_id
   FROM occurrences o
   JOIN taxa_taxon_lists ttl ON o.taxa_taxon_list_id = ttl.id
   JOIN taxa t ON ttl.taxon_id = t.id
   JOIN samples sa ON o.sample_id = sa.id
   JOIN websites w ON w.id = o.website_id
   JOIN surveys s ON s.id = sa.survey_id
   LEFT JOIN locations l ON sa.location_id = l.id;

CREATE OR REPLACE VIEW gv_taxon_lists_taxa AS 
 SELECT tt.id, tt.taxon_list_id, tt.taxon_id, tt.created_on, tt.created_by_id, tt.parent_id, tt.taxon_meaning_id, tt.taxonomic_sort_order, tt.preferred, tt.deleted, t.taxon, t.taxon_group_id, t.language_id, t.authority, t.search_code, t.scientific, l.language, tg.title AS taxon_group
   FROM taxa_taxon_lists tt
   JOIN taxa t ON tt.taxon_id = t.id
   JOIN languages l ON t.language_id = l.id
   JOIN taxon_groups tg ON t.taxon_group_id = tg.id;

CREATE OR REPLACE VIEW list_determinations AS 
 SELECT d.id, d.taxa_taxon_list_id, t.taxon, d.taxon_text_description, d.taxon_extra_info, d.occurrence_id, d.email_address, d.person_name, d.cms_ref, d.deleted, d.updated_on, o.website_id
   FROM determinations d
   JOIN occurrences o ON d.occurrence_id = o.id
   LEFT JOIN taxa_taxon_lists ttl ON d.taxa_taxon_list_id = ttl.id
   LEFT JOIN taxa t ON ttl.taxon_id = t.id;

CREATE OR REPLACE VIEW list_occurrences AS 
 SELECT su.title AS survey, l.name AS location, s.date_start, s.date_end, s.date_type, s.entered_sref, s.entered_sref_system, t.taxon, o.website_id, o.id, s.recorder_names
   FROM occurrences o
   JOIN samples s ON o.sample_id = s.id
   LEFT JOIN locations l ON s.location_id = l.id
   LEFT JOIN taxa_taxon_lists ttl ON o.taxa_taxon_list_id = ttl.id
   LEFT JOIN taxa t ON ttl.taxon_id = t.id
   LEFT JOIN surveys su ON s.survey_id = su.id;

CREATE OR REPLACE VIEW list_taxa_taxon_lists AS 
 SELECT ttl.id, ttl.taxon_id, t.taxon, t.authority, tc.taxon AS common, ttl.taxon_list_id, ttl.preferred, tl.title AS taxon_list, tl.website_id
   FROM taxa_taxon_lists ttl
   JOIN taxon_lists tl ON tl.id = ttl.taxon_list_id
   JOIN taxa t ON t.id = ttl.taxon_id
   LEFT JOIN taxa tc ON tc.id = ttl.common_taxon_id
  WHERE ttl.deleted = false;