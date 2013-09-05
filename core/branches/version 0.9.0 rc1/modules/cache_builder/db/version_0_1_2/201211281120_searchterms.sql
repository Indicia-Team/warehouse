ALTER TABLE cache_taxon_searchterms
ADD parent_id integer null;

ALTER TABLE cache_taxon_searchterms
ADD preferred_taxa_taxon_list_id integer null;

COMMENT ON COLUMN cache_taxon_searchterms.parent_id IS 'Identifies the parent of the taxon in the hierarchy, if one exists. ';
COMMENT ON COLUMN cache_taxon_searchterms.preferred_taxa_taxon_list_id IS 'ID of the preferred version of this term.';

ALTER TABLE cache_taxon_searchterms
  ADD CONSTRAINT fk_taxon_searchterms__parent_taxa_taxon_list FOREIGN KEY (parent_id)
      REFERENCES taxa_taxon_lists (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION;
      
ALTER TABLE cache_taxon_searchterms
  ADD CONSTRAINT fk_taxon_searchterms__preferred_taxa_taxon_list FOREIGN KEY (preferred_taxa_taxon_list_id)
      REFERENCES taxa_taxon_lists (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION;
      
-- update existing data
UPDATE cache_taxon_searchterms cts
SET parent_id=ttl.parent_id, preferred_taxa_taxon_list_id=ttl.preferred_taxa_taxon_list_id
FROM cache_taxa_taxon_lists ttl
WHERE ttl.id=cts.taxa_taxon_list_id;