DROP VIEW list_taxa_taxon_lists;

ALTER TABLE taxa_taxon_lists
ADD common_taxon_id INT;

COMMENT ON COLUMN taxa_taxon_lists.common_taxon_id IS 'Link to the first common name for this taxon entry.';

-- Update the table so there is an entry for each taxon that has a common name. It may not be perfect though as we don't know 
-- which is the best when there are several.
UPDATE taxa_taxon_lists
SET common_taxon_id=ttl2.taxon_id
FROM taxa_taxon_lists ttl2 
JOIN taxa t2 
	ON t2.id=ttl2.taxon_id
	AND t2.language_id!=2 -- not latin
WHERE 	ttl2.taxon_meaning_id=taxa_taxon_lists.taxon_meaning_id
	AND ttl2.preferred='f';

CREATE OR REPLACE VIEW list_taxa_taxon_lists AS 
 SELECT ttl.id, ttl.taxon_id, t.taxon, t.authority, ttl.taxon_list_id, ttl.preferred, tl.title AS taxon_list, l.iso AS language_iso, t.image_path AS taxon_image_path, ttl.image_path, tcommon.taxon as common
   FROM taxa_taxon_lists ttl
   JOIN taxon_lists tl ON tl.id = ttl.taxon_list_id
   JOIN taxa t ON t.id = ttl.taxon_id   
   JOIN languages l ON l.id = t.language_id
   LEFT JOIN taxa tcommon ON tcommon.id=ttl.common_taxon_id
  WHERE ttl.deleted = false;

