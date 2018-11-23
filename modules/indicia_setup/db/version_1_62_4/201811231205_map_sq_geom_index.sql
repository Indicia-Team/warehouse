CREATE INDEX ix_map_squares_geom
  ON map_squares
  USING gist(geom);