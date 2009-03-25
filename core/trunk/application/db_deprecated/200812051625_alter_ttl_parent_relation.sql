ALTER TABLE taxa_taxon_lists
DROP CONSTRAINT fk_taxon_parent,
ADD CONSTRAINT fk_taxon_parent FOREIGN KEY (parent_id)
      REFERENCES taxa_taxon_lists (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION;