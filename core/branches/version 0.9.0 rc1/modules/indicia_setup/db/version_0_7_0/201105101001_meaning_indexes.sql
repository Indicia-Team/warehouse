DROP INDEX IF EXISTS ix_taxa_taxon_lists_taxon_meaning_id;

DROP INDEX IF EXISTS ix_termlists_terms_meaning_id;

CREATE INDEX ix_taxa_taxon_lists_taxon_meaning_id
  ON taxa_taxon_lists
  USING btree
  (taxon_meaning_id);

CREATE INDEX ix_termlists_terms_meaning_id
  ON termlists_terms
  USING btree
  (meaning_id);