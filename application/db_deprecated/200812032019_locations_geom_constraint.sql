-- Check: enforce_geotype_geom

ALTER TABLE locations DROP CONSTRAINT enforce_geotype_centroid_geom;

ALTER TABLE locations
  ADD CONSTRAINT enforce_geotype_centroid_geom CHECK (geometrytype(centroid_geom) IN ('POINT'::text, 'POLYGON'::text, 'LINESTRING'::text) OR centroid_geom IS NULL);
