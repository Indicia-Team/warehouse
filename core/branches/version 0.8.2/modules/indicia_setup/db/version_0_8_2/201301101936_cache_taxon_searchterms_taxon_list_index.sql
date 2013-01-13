ALTER TABLE cache_taxon_searchterms ADD CONSTRAINT fk_taxon_searchterms_taxon_list FOREIGN KEY (taxon_list_id) REFERENCES taxon_lists (id)
   ON UPDATE NO ACTION ON DELETE NO ACTION;
CREATE INDEX fki_taxon_searchterms_taxon_list ON cache_taxon_searchterms(taxon_list_id);
