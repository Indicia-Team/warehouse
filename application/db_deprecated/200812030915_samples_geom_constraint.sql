-- Check: enforce_geotype_geom

ALTER TABLE samples DROP CONSTRAINT enforce_geotype_geom;

ALTER TABLE samples
  ADD CONSTRAINT enforce_geotype_geom CHECK (geometrytype(geom) IN ('POINT'::text, 'POLYGON'::text, 'LINESTRING'::text) OR geom IS NULL);
