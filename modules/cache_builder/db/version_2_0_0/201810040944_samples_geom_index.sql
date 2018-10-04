-- #slow script#
CREATE INDEX ix_cache_samples_functional_public_geom
    ON cache_samples_functional USING gist
    (public_geom);