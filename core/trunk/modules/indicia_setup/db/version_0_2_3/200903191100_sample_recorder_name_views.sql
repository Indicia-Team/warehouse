DROP VIEW list_samples;

CREATE OR REPLACE VIEW list_samples AS
 SELECT s.id, su.title AS survey, l.name AS location, s.date_start, s.date_end, s.date_type, s.entered_sref, s.entered_sref_system, s.recorder_names
   FROM samples s
   LEFT JOIN locations l ON s.location_id = l.id
   LEFT JOIN surveys su ON s.survey_id = su.id;

DROP VIEW detail_samples;

CREATE OR REPLACE VIEW detail_samples AS
 SELECT s.id, s.entered_sref, s.entered_sref_system, s.geom, s.location_name, s.date_start, s.date_end, s.date_type,
 	s.location_id, l.name AS location, l.code AS location_code, s.recorder_names,
 	s.created_by_id, c.username AS created_by, s.created_on, s.updated_by_id, u.username AS updated_by, s.updated_on
   FROM samples s
   LEFT JOIN locations l ON l.id = s.location_id
   LEFT JOIN surveys su ON s.survey_id = su.id
   JOIN users c ON c.id = s.created_by_id
   JOIN users u ON u.id = s.updated_by_id;

DROP VIEW detail_occurrences;

CREATE OR REPLACE VIEW detail_occurrences AS
 SELECT o.id, o.confidential, o.comment, o.taxa_taxon_list_id, o.determiner_id, t.taxon, s.entered_sref, s.entered_sref_system,
 	s.geom, s.location_name, s.survey_id, s.date_start, s.date_end, s.date_type,
 	s.location_id, l.name AS location, l.code AS location_code, s.recorder_names,
 	(d.first_name::text || ' '::text) || d.surname::text AS determiner, o.website_id, o.created_by_id, c.username AS created_by, o.created_on, o.updated_by_id, u.username AS updated_by, o.updated_on
   FROM occurrences o
   JOIN samples s ON s.id = o.sample_id
   LEFT JOIN people d ON d.id = o.determiner_id
   LEFT JOIN locations l ON l.id = s.location_id
   JOIN taxa_taxon_lists ttl ON ttl.id = o.taxa_taxon_list_id
   JOIN taxa t ON t.id = ttl.taxon_id
   LEFT JOIN surveys su ON s.survey_id = su.id
   JOIN users c ON c.id = o.created_by_id
   JOIN users u ON u.id = o.updated_by_id;

DROP VIEW list_occurrences;

CREATE OR REPLACE VIEW list_occurrences AS
 SELECT su.title AS survey, l.name AS location, s.date_start, s.date_end, s.date_type, s.entered_sref, s.entered_sref_system,
 	t.taxon, o.website_id, o.id, s.recorder_names
   FROM occurrences o
   JOIN samples s ON o.sample_id = s.id
   LEFT JOIN locations l ON s.location_id = l.id
   JOIN taxa_taxon_lists ttl ON o.taxa_taxon_list_id = ttl.id
   JOIN taxa t ON ttl.taxon_id = t.id
   LEFT JOIN surveys su ON s.survey_id = su.id;

DROP VIEW gv_occurrences;

CREATE OR REPLACE VIEW gv_occurrences AS
 SELECT o.id, o.sample_id, t.taxon, sa.date_start, sa.date_end, sa.date_type,
 	sa.entered_sref, sa.entered_sref_system, sa.location_name, l.name, o.deleted, sa.recorder_names
   FROM occurrences o
   JOIN taxa_taxon_lists ttl ON o.taxa_taxon_list_id = ttl.id
   JOIN taxa t ON ttl.taxon_id = t.id
   JOIN samples sa ON o.sample_id = sa.id
   LEFT JOIN locations l ON sa.location_id = l.id;
