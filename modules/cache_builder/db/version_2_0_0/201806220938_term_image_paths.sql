
ALTER TABLE cache_termlists_terms
  ADD COLUMN image_path character varying;
COMMENT ON COLUMN cache_termlists_terms.image_path IS
  'Path to an image file representing the term, e.g. an explanatory diagram.';