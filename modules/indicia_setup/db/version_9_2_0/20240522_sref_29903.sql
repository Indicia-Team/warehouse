CREATE OR REPLACE FUNCTION get_output_srid(
    geom_in geometry)
  RETURNS integer AS
$BODY$
DECLARE geom GEOMETRY;
DECLARE origCentroid GEOMETRY;
DECLARE output_srid varchar;
DECLARE origSridX float;
DECLARE origSridY float;
DECLARE newSridX float;
DECLARE newSridY float;
BEGIN
  output_srid = null;
  origCentroid = st_centroid(geom_in);
  origSridX = st_x(origCentroid);
  origSridY = st_y(origCentroid);

  -- Look for some preferred projections to see if we are in range.
  -- First, rough check for OSIE
  IF (origSridX BETWEEN -1196000 AND -599200) AND (origSridY BETWEEN 6687800 AND 7442470) THEN
    geom = st_transform(origCentroid, 29903);
    newSridX = st_x(geom);
    newSridY = st_y(geom);
    -- Exact check for OSIE. Cut out top right corner.
    IF (newSridX BETWEEN 10000 AND 367300) AND (newSridY BETWEEN 10000 AND 468100)
      AND (newSridX<332000 OR newSridY<445900) THEN
      output_srid = 29903; -- Irish Grid
    END IF;
  END IF;
  -- Rough check for OSGB.
  IF (output_srid IS NULL) AND (origSridX BETWEEN -1081873 AND 422933) AND (origSridY BETWEEN 6405988 AND 8944478) THEN
    geom = st_transform(origCentroid, 27700);
    -- Exact check for OSGB
    IF st_x(geom) BETWEEN 0 AND 700000 AND st_y(geom) BETWEEN 0 AND 14000000 THEN
      output_srid = 27700; -- British National Grid
    END IF;
  END IF;
  -- Rough check for Luxembourg
  IF (output_srid IS NULL) AND (origSridX BETWEEN 634030 AND 729730) AND (origSridY BETWEEN 6348260 AND 6484930) THEN
    geom = st_transform(origCentroid, 2169);
    -- Exact check for Luxembourg
    IF (st_x(geom) BETWEEN 46000 AND 108000) AND (st_y(geom) BETWEEN 55000 AND 141000) THEN
      output_srid = 2169; -- Gauss-Luxembourg
    END IF;
  END IF;
  -- Rough check for Channel Islands
  IF (output_srid IS NULL) AND (origSridX BETWEEN -257600 AND -210500) AND (origSridY BETWEEN 6271000 AND 6415000) THEN
    geom = st_transform(origCentroid, 23030);
    newSridX = st_x(geom);
    newSridY = st_y(geom);
    -- Exact checkes for each Island
    IF (newSridX BETWEEN 540000 AND 585000) AND (newSridY BETWEEN 5435000 AND 5465000) OR -- exact check for Jersey area
      (newSridX BETWEEN 515000 AND 555000) AND (newSridY BETWEEN 5465000 AND 5490000) OR -- Guernsey area
      (newSridX BETWEEN 530000 AND 565000) AND (newSridY BETWEEN 5495000 AND 5515000) THEN -- Alderney area
      output_srid = 23030; -- Channel Islands utm30ed50
    END IF;
  END IF;
  IF (output_srid IS NULL) THEN
    -- Calculate UTM zone EPSG code.
    geom = st_transform(origCentroid, 4326);
    output_srid = 32700-round((45+st_y(geom))/90)*100+round((183+st_x(geom))/6);
  END IF;
  RETURN output_srid;
END;
$BODY$
  LANGUAGE plpgsql IMMUTABLE
  COST 90;

CREATE OR REPLACE FUNCTION sref_system_to_srid(sref_system character varying)
  RETURNS integer AS
$BODY$
  BEGIN
    RETURN CASE lower(sref_system)
      WHEN 'osgb' THEN 27700
      WHEN 'osie' THEN 29903
      WHEN 'mtbqyx' THEN 4314
      WHEN 'mtbqqq' THEN 4314
      WHEN 'guernsey' THEN 3108
      WHEN 'jersey' THEN 3109
      WHEN 'utm30ed50' THEN 23030
      WHEN 'utm30wgs84' THEN 32630
      ELSE sref_system :: INTEGER
    END;
  END;
  $BODY$
  LANGUAGE plpgsql IMMUTABLE
  COST 50;

CREATE OR REPLACE FUNCTION srid_to_sref_system(
    input_srid integer)
  RETURNS character varying AS
$BODY$
BEGIN
  RETURN CASE input_srid
    WHEN 27700 THEN 'OSGB'
    -- deprecated
    WHEN 29901 THEN 'OSIE'
    -- current
    WHEN 29903 THEN 'OSIE'
    WHEN 4314 THEN 'MTBQQQ'
    WHEN 3108 THEN 'GUERNSEY'
    WHEN 3109 THEN 'JERSEY'
    WHEN 23030 THEN 'UTM30ED50'
    WHEN 32630 THEN 'UTM30WGS84'
    ELSE input_srid :: CHARACTER VARYING
  END;
END;
$BODY$
  LANGUAGE plpgsql IMMUTABLE
  COST 50;

CREATE OR REPLACE FUNCTION get_output_sref(
	accuracy integer,
	geom geometry)
    RETURNS character varying
    LANGUAGE 'plpgsql'
    COST 100
    IMMUTABLE PARALLEL UNSAFE
AS $BODY$
DECLARE output_srid INTEGER;
DECLARE centroid_in_srid GEOMETRY;
DECLARE east FLOAT;
DECLARE north FLOAT;
DECLARE usedAccuracy INTEGER;
DECLARE prefix VARCHAR;
BEGIN

-- Set a default if accuracy not recorded.
usedAccuracy = COALESCE(accuracy, 10);
-- Prefix for imprecise data that can't be reflected in a grid notation.
prefix = CASE
  WHEN usedAccuracy>100000 then '~ '
  ELSE ''
END;
-- Find the best local grid system appropriate to the area on the map
output_srid = get_output_srid(geom);
-- Round accuracy up to a supported grid square size.
usedAccuracy = CASE
   WHEN usedAccuracy>10000 THEN 100000
   WHEN usedAccuracy>2000 THEN 10000
   -- Only support Tetrads in OSGB currently.
   WHEN usedAccuracy>1000 THEN CASE output_srid WHEN 27700 THEN 2000 ELSE 10000 END
   WHEN usedAccuracy>100 THEN 1000
   WHEN usedAccuracy>10 THEN 100
   ELSE 10
END;

centroid_in_srid = st_transform(st_centroid(geom), output_srid);
east = st_x(centroid_in_srid);
north = st_y(centroid_in_srid);
-- this currently only supports OSGB and OSIE so will need extending to support other grid systems
IF output_srid=27700 THEN
  RETURN prefix || convert_east_north_to_osgb(east, north, usedAccuracy);
ELSEIF output_srid=29903 THEN
  RETURN prefix || convert_east_north_to_osie(east, north, usedAccuracy);
ELSEIF output_srid=23030 THEN
  RETURN prefix || convert_east_north_to_utm30ed50(east, north, usedAccuracy);
ELSE
  RETURN prefix || format_geom_as_latlong(geom, 4326, accuracy);
END IF;

END
$BODY$;