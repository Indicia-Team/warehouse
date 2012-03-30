-- View: list_occurrences

DROP VIEW IF EXISTS list_occurrences;

CREATE OR REPLACE VIEW list_occurrences AS 
 SELECT su.title AS survey, l.name AS location, s.date_start, s.date_end, s.date_type, s.entered_sref, s.entered_sref_system, t.taxon, o.website_id, o.id
   FROM occurrences o
   JOIN samples s ON o.sample_id = s.id
   LEFT JOIN locations l ON s.location_id = l.id
   JOIN taxa_taxon_lists ttl ON o.taxa_taxon_list_id = ttl.id
   JOIN taxa t ON ttl.taxon_id = t.id
   LEFT JOIN surveys su ON s.survey_id = su.id;
