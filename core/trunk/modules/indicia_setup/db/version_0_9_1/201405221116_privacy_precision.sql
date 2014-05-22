ALTER TABLE samples
    ADD COLUMN privacy_precision integer;

COMMENT ON COLUMN samples.privacy_precision IS 'Allows record precision to be blurred for public viewing for privacy (as opposed to sensitivity) reasons. An example might be to obscure the garden location of a minor.';

ALTER TABLE cache_occurrences
    ADD COLUMN privacy_precision integer;

CREATE OR REPLACE FUNCTION reduce_precision(geom_in geometry, confidential boolean, sensitivity_precision integer, privacy_precision integer, sref_system character varying)
  RETURNS geometry AS
$BODY$
DECLARE geom geometry;
DECLARE geomltln geometry;
DECLARE r geometry;
DECLARE precisionM integer;
DECLARE x float;
DECLARE y float;
DECLARE sref_metadata record;
DECLARE current_srid integer;
DECLARE blur integer;
BEGIN
  blur = GREATEST(sensitivity_precision, privacy_precision);
  -- Copy geom_in as values cannot be assigned to parameters in postgres <= 8.4
  geom = geom_in;
  IF confidential = true OR blur IS NOT NULL THEN
    precisionM = CASE
      WHEN blur IS NOT NULL THEN blur
      ELSE 1000
    END;
    -- If already low precision, then can return as it is
    IF sqrt(st_area(geom)) >= sensitivity_precision THEN
      r = geom;
    ELSE
      SELECT INTO sref_metadata srid, treat_srid_as_x_y_metres FROM spatial_systems WHERE code=lower(sref_system);
      -- look for some preferred grids to see if we are in range.
      current_srid=null;
      IF (current_srid IS NULL) AND (st_x(st_centroid(geom)) BETWEEN -1196000 AND -599200) AND (st_y(st_centroid(geom)) BETWEEN 6687800 AND 7442470) THEN -- rough check for OSIE
        geom = st_transform(st_centroid(geom_in), 29901); 
        IF (st_x(geom) BETWEEN 10000 AND 367300) AND (st_y(geom) BETWEEN 10000 AND 468100) AND (st_x(geom)<332000 OR st_y(geom)<445900) THEN -- exact check for OSIE. Cut out top right corner.
          current_srid = 29901;
        END IF;
      END IF;
      IF (current_srid IS NULL) AND (st_x(st_centroid(geom)) BETWEEN -1081873 AND 422933) AND (st_y(st_centroid(geom)) BETWEEN 6405988 AND 8944478) THEN -- rough check for OSGB
        geom = st_transform(st_centroid(geom_in), 27700);
        IF st_x(geom) BETWEEN 0 AND 700000 AND st_y(geom) BETWEEN 0 AND 14000000 THEN -- exact check for OSGB
          current_srid = 27700;
        END IF;
      END IF;
      IF (current_srid IS NULL) AND (st_x(st_centroid(geom)) BETWEEN 634030 AND 729730) AND (st_y(st_centroid(geom)) BETWEEN 6348260 AND 6484930) THEN -- rough check for LUGR
        geom = st_transform(st_centroid(geom_in), 2169);
        IF (st_x(geom) BETWEEN 46000 AND 108000) AND (st_y(geom) BETWEEN 55000 AND 141000) THEN -- exact check for OSGB
          current_srid = 2169;
        END IF;
      END IF;
      IF current_srid IS NULL THEN
        IF COALESCE(sref_metadata.treat_srid_as_x_y_metres, false) THEN
          geom = st_transform(geom_in, sref_metadata.srid);
          current_srid = sref_metadata.srid;
        ELSE
          current_srid = 900913;
          geom=geom_in;
        END IF;
      END IF;
      -- need to reduce this to a square on the grid
      x = floor(st_xmin(geom)::NUMERIC / precisionM) * precisionM;
      y = floor(st_ymin(geom)::NUMERIC / precisionM) * precisionM;
      r = st_geomfromtext('polygon((' || x::varchar || ' ' || y::varchar || ',' || (x + precisionM)::varchar || ' ' || y::varchar || ','
       || (x + precisionM)::varchar || ' ' || (y + precisionM)::varchar || ',' || x::varchar || ' ' || (y + precisionM)::varchar || ','
       || x::varchar || ' ' || y::varchar || '))', current_srid);
      IF current_srid<>900913 THEN
        r = st_transform(r, 900913);
      END IF;
    END IF;
  ELSE
    r = geom;
  END IF;
RETURN r;
END;
$BODY$
  LANGUAGE plpgsql VOLATILE
  COST 100;
