-- View: gv_occurrences

DROP VIEW IF EXISTS gv_occurrences;

CREATE OR REPLACE VIEW gv_occurrences AS 
 SELECT o.id, o.sample_id, t.taxon, sa.date_start, sa.date_end, sa.date_type, sa.entered_sref, sa.entered_sref_system, sa.location_name, l.name, o.deleted
   FROM occurrences o
   JOIN taxa_taxon_lists ttl ON o.taxa_taxon_list_id = ttl.id
   JOIN taxa t ON ttl.taxon_id = t.id
   JOIN samples sa ON o.sample_id = sa.id
   LEFT JOIN locations l ON sa.location_id = l.id;

