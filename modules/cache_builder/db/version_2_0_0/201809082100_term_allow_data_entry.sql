ALTER TABLE cache_termlists_terms
  ADD COLUMN allow_data_entry boolean NOT NULL DEFAULT true;

COMMENT ON COLUMN cache_termlists_terms.allow_data_entry IS
  'Flag allowing terms that should remain in the database but can no longer be selected for data entry to be identified.';