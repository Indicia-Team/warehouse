CREATE OR REPLACE VIEW list_taxon_designations AS
SELECT id, title, code, abbreviation
FROM taxon_designations
WHERE deleted=false;

CREATE OR REPLACE VIEW detail_taxon_designations AS
SELECT td.id, td.title, td.code, td.abbreviation, td.description, cat.term as category,
td.created_by_id, c.username AS created_by, td.updated_by_id, u.username AS updated_by,
td.created_on, td.updated_on
FROM taxon_designations td
JOIN users c ON c.id = td.created_by_id
JOIN users u ON u.id = td.updated_by_id
JOIN list_termlists_terms cat on cat.id=td.category_id
WHERE td.deleted=false;

CREATE OR REPLACE VIEW list_taxa_taxon_designations AS
SELECT ttd.id, td.title, td.code, td.abbreviation, ttl.taxon, ttl.common, ttl.preferred_name, ttl.language, ttl.taxon_group
FROM taxon_designations td
JOIN taxa_taxon_designations ttd ON ttd.taxon_designation_id=td.id and ttd.deleted=false
JOIN list_taxa_taxon_lists ttl ON ttl.taxon_id=ttd.taxon_id 
WHERE td.deleted=false;

CREATE OR REPLACE VIEW detail_taxa_taxon_designations AS
SELECT ttd.id, td.title, td.code, td.abbreviation, ttl.taxon, ttl.common, ttl.preferred_name, ttl.language, ttl.taxon_group,
cat.term as category, ttd.created_by_id, c.username AS created_by, ttd.updated_by_id, u.username AS updated_by,
ttd.created_on, ttd.updated_on
FROM taxon_designations td
JOIN taxa_taxon_designations ttd ON ttd.taxon_designation_id=td.id
JOIN list_taxa_taxon_lists ttl ON ttl.taxon_id=ttd.taxon_id
JOIN list_termlists_terms cat on cat.id=td.category_id
JOIN users c ON c.id = ttd.created_by_id
JOIN users u ON u.id = ttd.updated_by_id
WHERE ttd.deleted=false;
