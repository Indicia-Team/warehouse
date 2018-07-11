
ALTER TABLE cache_termlists_terms
  ADD COLUMN preferred_image_path character varying;
COMMENT ON COLUMN cache_termlists_terms.preferred_image_path IS
  'Path to an image file representing the term, e.g. an explanatory diagram.';