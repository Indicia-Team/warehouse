DROP VIEW IF EXISTS list_taxa_taxon_designations;

CREATE OR REPLACE VIEW list_taxa_taxon_designations AS
 SELECT ttd.id, td.title, td.code, td.abbreviation, t.taxon, cttl.default_common_name as common,
   cttl.preferred_taxon as preferred_name, cttl.language, cttl.taxon_group, ttd.taxon_id, ttd.start_date, ttd.source,
   cttl.website_id, ttd.taxon_designation_id, ttd.geographical_constraint
   FROM taxon_designations td
   JOIN taxa_taxon_designations ttd ON ttd.taxon_designation_id = td.id AND ttd.deleted = false
   JOIN taxa t on t.id=ttd.taxon_id and t.deleted=false
   JOIN taxa_taxon_lists ttl on ttl.taxon_id=t.id and ttl.deleted=false
   JOIN cache_taxa_taxon_lists cttl on cttl.id=ttl.id
  WHERE td.deleted = false;

DROP VIEW IF EXISTS detail_taxa_taxon_designations;

CREATE OR REPLACE VIEW detail_taxa_taxon_designations AS
 SELECT DISTINCT ttd.id, td.title, td.code, td.abbreviation, t.taxon, cttl.default_common_name as common,
     cttl.preferred_taxon as preferred_name, cttl.language_iso as language, cttl.taxon_group, tcat.term AS category,
     ttd.created_by_id, c.username AS created_by, ttd.updated_by_id, u.username AS updated_by, ttd.created_on,
     ttd.updated_on, ttd.taxon_id, ttd.start_date, ttd.source, cttl.website_id, ttd.taxon_designation_id,
     ttd.geographical_constraint
   FROM taxon_designations td
   JOIN taxa_taxon_designations ttd ON ttd.taxon_designation_id = td.id and ttd.deleted=false
   JOIN taxa t ON t.id=ttd.taxon_id AND t.deleted=false
   JOIN taxa_taxon_lists ttl ON ttl.taxon_id = ttd.taxon_id and ttl.deleted=false
   JOIN cache_taxa_taxon_lists cttl on cttl.id=ttl.id
   JOIN termlists_terms tltcat on tltcat.id = td.category_id and tltcat.deleted=false
   JOIN terms tcat on tcat.id=tltcat.term_id and tcat.deleted=false
   JOIN users c ON c.id = ttd.created_by_id
   JOIN users u ON u.id = ttd.updated_by_id
  WHERE ttd.deleted = false;