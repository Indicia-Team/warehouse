ALTER TABLE taxa
ADD image_path character varying(500),
ADD description character varying;

ALTER TABLE taxa_taxon_lists
ADD image_path character varying(500),
ADD description character varying;

DROP VIEW detail_taxa_taxon_lists;

DROP VIEW list_taxa_taxon_lists;


CREATE VIEW detail_taxa_taxon_lists AS
 SELECT ttl.id, ttl.taxon_id, t.taxon, t.authority, ttl.taxon_list_id, tl.title AS taxon_list, ttl.taxon_meaning_id, ttl.preferred, ttl.parent_id, tp.taxon AS parent,
	l.iso as language_iso, t.image_path as taxon_image_path, ttl.image_path, t.description as taxon_description, ttl.description,
	ttl.created_by_id, c.username AS created_by, ttl.updated_by_id, u.username AS updated_by
   FROM taxa_taxon_lists ttl
   JOIN taxon_lists tl ON tl.id = ttl.taxon_list_id
   JOIN taxa t ON t.id = ttl.taxon_id
   JOIN users c ON c.id = ttl.created_by_id
   JOIN users u ON u.id = ttl.updated_by_id
   JOIN languages l ON l.id=t.language_id
   LEFT JOIN taxa_taxon_lists ttlp ON ttlp.id = ttl.parent_id
   LEFT JOIN taxa tp ON tp.id = ttlp.taxon_id
  WHERE ttl.deleted = false;

CREATE VIEW list_taxa_taxon_lists AS
 SELECT ttl.id, ttl.taxon_id, t.taxon, t.authority, ttl.taxon_list_id, ttl.preferred, tl.title AS taxon_list,
	l.iso as language_iso, t.image_path as taxon_image_path, ttl.image_path
   FROM taxa_taxon_lists ttl
   JOIN taxon_lists tl ON tl.id = ttl.taxon_list_id
   JOIN taxa t ON t.id = ttl.taxon_id
   JOIN languages l ON l.id=t.language_id
  WHERE ttl.deleted = false;