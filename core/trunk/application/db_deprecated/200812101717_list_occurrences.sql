CREATE OR REPLACE VIEW list_occurrences AS
 SELECT su.title as "survey", l.name AS "location", (d.first_name::text || ' '::text) || d.surname::text AS determiner, t.taxon
   FROM occurrences o
   JOIN samples s ON o.sample_id = s.id
   JOIN people d ON o.determiner_id = d.id
   JOIN locations l ON s.location_id = l.id
   JOIN taxa_taxon_lists ttl ON o.taxa_taxon_list_id = ttl.id
   JOIN taxa t ON ttl.taxon_id = t.id
   JOIN surveys su ON s.survey_id = su.id;