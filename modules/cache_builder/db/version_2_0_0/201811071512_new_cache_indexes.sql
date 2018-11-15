-- #slow script#

-- Index to improve performance on WithoutPolygon data cleaning.
CREATE INDEX ix_cache_occurrences_functional_ttl_ext_key_map_sq_v
  ON cache_occurrences_functional(taxa_taxon_list_external_key, map_sq_10km_id)
  WHERE record_status='V';

-- Improve performance of taxon meaning filtering.
CREATE INDEX ix_cache_occurrences_functional_taxon_meaning_id
  ON cache_occurrences_functional(taxon_meaning_id);

-- indexes for the new location_ids fields.
CREATE INDEX ix_cache_occurrences_functional_location_ids
  ON cache_occurrences_functional
  USING GIN(location_ids);
CREATE INDEX ix_cache_samples_functional_location_ids
  ON cache_samples_functional
  USING GIN(location_ids);

-- Index on the array of ancestors for each taxon.
CREATE INDEX ix_cache_occurrences_functional_taxon_path
  ON cache_occurrences_functional
  USING gin
  (taxon_path);

CREATE INDEX ix_cache_samples_functional_public_geom
    ON cache_samples_functional USING gist
    (public_geom);