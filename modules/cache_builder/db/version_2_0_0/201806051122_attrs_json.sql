ALTER TABLE cache_occurrences_nonfunctional
  ADD COLUMN attrs_json json;

ALTER TABLE cache_samples_nonfunctional
  ADD COLUMN attrs_json json;