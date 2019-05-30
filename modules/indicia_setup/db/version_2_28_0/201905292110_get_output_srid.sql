
CREATE OR REPLACE FUNCTION get_output_srid(
    geom_in geometry)
  RETURNS integer AS
$BODY$
DECLARE geom geometry;
DECLARE output_srid varchar;
BEGIN
  output_srid=null;
  -- look for some preferred projections to see if we are in range.
  IF (st_x(st_centroid(geom_in)) BETWEEN -1196000 AND -599200) AND (st_y(st_centroid(geom_in)) BETWEEN 6687800 AND 7442470) THEN -- rough check for OSIE
    geom = st_transform(st_centroid(geom_in), 29901);
    IF (st_x(geom) BETWEEN 10000 AND 367300) AND (st_y(geom) BETWEEN 10000 AND 468100) AND (st_x(geom)<332000 OR st_y(geom)<445900) THEN -- exact check for OSIE. Cut out top right corner.
      output_srid = 29901; -- Irish Grid
    END IF;
  END IF;
  IF (output_srid IS NULL) AND (st_x(st_centroid(geom_in)) BETWEEN -1081873 AND 422933) AND (st_y(st_centroid(geom_in)) BETWEEN 6405988 AND 8944478) THEN -- rough check for OSGB
    geom = st_transform(st_centroid(geom_in), 27700);
    IF st_x(geom) BETWEEN 0 AND 700000 AND st_y(geom) BETWEEN 0 AND 14000000 THEN -- exact check for OSGB
      output_srid = 27700; -- British National Grid
    END IF;
  END IF;
  IF (output_srid IS NULL) AND (st_x(st_centroid(geom_in)) BETWEEN 634030 AND 729730) AND (st_y(st_centroid(geom_in)) BETWEEN 6348260 AND 6484930) THEN -- rough check for Luxembourg
    geom = st_transform(st_centroid(geom_in), 2169);
    IF (st_x(geom) BETWEEN 46000 AND 108000) AND (st_y(geom) BETWEEN 55000 AND 141000) THEN -- exact check for Luxembourg
      output_srid = 2169; -- Gauss-Luxembourg
    END IF;
  END IF;
  IF (output_srid IS NULL) AND (st_x(st_centroid(geom_in)) BETWEEN -257600 AND -210500) AND (st_y(st_centroid(geom_in)) BETWEEN 6271000 AND 6415000) THEN -- rough check for channel islands
    geom = st_transform(st_centroid(geom_in), 23030);
    IF (st_x(geom) BETWEEN 540000 AND 585000) AND (st_y(geom) BETWEEN 5435000 AND 5465000) OR -- exact check for Jersey area
      (st_x(geom) BETWEEN 515000 AND 555000) AND (st_y(geom) BETWEEN 5465000 AND 5490000) OR -- Guernsey area
      (st_x(geom) BETWEEN 530000 AND 565000) AND (st_y(geom) BETWEEN 5495000 AND 5515000) THEN -- Alderney area
      output_srid = 23030; -- Channel Islands utm30ed50
    END IF;
  END IF;
  IF (output_srid IS NULL) THEN
    -- Calculate UTM zone EPSG code.
    geom = st_transform(st_centroid(geom_in), 4326);
    output_srid = 32700-round((45+st_y(geom))/90)*100+round((183+st_x(geom))/6);
  END IF;
  RETURN output_srid;
END;
$BODY$
  LANGUAGE plpgsql STABLE
  COST 100;

CREATE OR REPLACE FUNCTION sref_system_to_srid(sref_system character varying)
  RETURNS integer AS
$BODY$
  BEGIN
    RETURN CASE lower(sref_system)
      WHEN 'osgb' THEN 27700
      WHEN 'osie' THEN 29901
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
  LANGUAGE plpgsql VOLATILE
  COST 100;

CREATE OR REPLACE FUNCTION srid_to_sref_system(
    input_srid integer)
  RETURNS character varying AS
$BODY$
BEGIN
  RETURN CASE input_srid
    WHEN 27700 THEN 'OSGB'
    WHEN 29901 THEN 'OSIE'
    WHEN 4314 THEN 'MTBQQQ'
    WHEN 3108 THEN 'GUERNSEY'
    WHEN 3109 THEN 'JERSEY'
    WHEN 23030 THEN 'UTM30ED50'
    WHEN 32630 THEN 'UTM30WGS84'
    ELSE input_srid :: CHARACTER VARYING
  END;
END;
$BODY$
  LANGUAGE plpgsql STABLE
  COST 100;

CREATE OR REPLACE FUNCTION get_output_system(
    geom_in geometry)
  RETURNS character varying AS
$BODY$
DECLARE output_srid integer;
DECLARE sys varchar;
DECLARE sref_metadata record;
BEGIN
  output_srid = get_output_srid(geom_in);
  RETURN srid_to_sref_system(output_srid);
END;
$BODY$
  LANGUAGE plpgsql STABLE
  COST 100;


CREATE OR REPLACE FUNCTION format_geom_as_latlong(
    geom geometry,
    srid integer,
    accuracy integer)
  RETURNS character varying AS
$BODY$
DECLARE geomInSrid GEOMETRY;
DECLARE decimals INTEGER;
DECLARE x FLOAT;
DECLARE y FLOAT;
BEGIN
  geomInSrid = ST_TRANSFORM(ST_CENTROID(geom), srid);
  -- very approximate way of reducing lat long dispay precision to reflect accuracy
  decimals = 7 - LENGTH(accuracy::varchar);
  x = round(st_x(geomInSrid)::numeric, decimals);
  y = round(st_y(geomInSrid)::numeric, decimals);
  -- Format includes tilde if blurred.
  RETURN CASE WHEN accuracy > 10 THEN '~ ' ELSE '' END || abs(y) || CASE WHEN y>=0 THEN 'N' ELSE 'S' END || ' ' || abs(x) || CASE WHEN x>=0 THEN 'E' ELSE 'W' END;
END
$BODY$
  LANGUAGE plpgsql IMMUTABLE
  COST 100;


CREATE OR REPLACE FUNCTION reduce_precision(
    geom_in geometry,
    confidential boolean,
    reduce_to_precision integer)
  RETURNS geometry AS
$BODY$
DECLARE geom geometry;
DECLARE geomltln geometry;
DECLARE r geometry;
DECLARE precisionM integer;
DECLARE x float;
DECLARE y float;
DECLARE srid integer;
DECLARE sref_metadata record;
BEGIN
  IF confidential = true OR COALESCE(reduce_to_precision, 0)>1 THEN
    precisionM = CASE
      WHEN COALESCE(reduce_to_precision, 0)>1 THEN reduce_to_precision
      ELSE 1000
    END;
    srid = get_output_srid(geom_in);
    IF srid<>900913 THEN
      geom = st_transform(geom_in, srid);
    ELSE
      geom = geom_in;
    END IF;
    -- If already low precision, then can return as it is
    IF sqrt(st_area(geom)) >= precisionM THEN
      r = geom_in;
    ELSE
      geom = st_centroid(geom);
      -- need to reduce this to a square on the grid
      x = floor(st_xmin(geom)::NUMERIC / precisionM) * precisionM;
      y = floor(st_ymin(geom)::NUMERIC / precisionM) * precisionM;
      r = st_geomfromtext('polygon((' || x::varchar || ' ' || y::varchar || ',' || (x + precisionM)::varchar || ' ' || y::varchar || ','
          || (x + precisionM)::varchar || ' ' || (y + precisionM)::varchar || ',' || x::varchar || ' ' || (y + precisionM)::varchar || ','
          || x::varchar || ' ' || y::varchar || '))', srid);
      IF srid<>900913 THEN
        r = st_transform(r, 900913);
      END IF;
    END IF;
  ELSE
    r = geom_in;
  END IF;
RETURN r;
END;
$BODY$
  LANGUAGE plpgsql IMMUTABLE
  COST 100;

CREATE OR REPLACE FUNCTION get_output_sref(
    accuracy integer,
    geom geometry)
  RETURNS character varying AS
$BODY$
DECLARE output_srid INTEGER;
DECLARE centroid_in_srid GEOMETRY;
DECLARE east FLOAT;
DECLARE north FLOAT;
DECLARE usedAccuracy INTEGER;
BEGIN

-- Set a default if accuracy not recorded.
usedAccuracy = COALESCE(accuracy, 10);
-- Round accuracy up to a supported grid square size - no support for DINTY at this point.
usedAccuracy = CASE
   WHEN usedAccuracy>10000 THEN 100000
   WHEN usedAccuracy>1000 THEN 10000
   WHEN usedAccuracy>100 THEN 1000
   WHEN usedAccuracy>10 THEN 100
   ELSE 10
END;
-- Find the best local grid system appropriate to the area on the map
output_srid = get_output_srid(geom);
centroid_in_srid = st_transform(st_centroid(geom), output_srid);
east = st_x(centroid_in_srid);
north = st_y(centroid_in_srid);
-- this currently only supports OSGB and OSIE so will need extending to support other grid systems
IF output_srid=27700 THEN
  RETURN convert_east_north_to_osgb(east, north, usedAccuracy);
ELSEIF output_srid=29901 THEN
  RETURN convert_east_north_to_osie(east, north, usedAccuracy);
ELSEIF output_srid=23030 THEN
  RETURN convert_east_north_to_utm30ed50(east, north, usedAccuracy);
ELSE
  RETURN format_geom_as_latlong(geom, 4326, accuracy);
END IF;

END
$BODY$
  LANGUAGE plpgsql IMMUTABLE
  COST 100;

-- Clean up
DROP FUNCTION get_output_sref(character varying, character varying, integer, geometry);
DROP FUNCTION get_output_system(geometry, character varying, character varying);

-- Leave deprecated versions of this function in place which can be removed at a later date,
-- as used by the Ecobat module which may not be updated at the same point in time.
CREATE OR REPLACE FUNCTION reduce_precision(
    geom_in geometry,
    confidential boolean,
    reduce_to_precision integer,
    sref_system character varying)
  RETURNS geometry AS
$BODY$
BEGIN
  RETURN reduce_precision(geom_in, confidential, reduce_to_precision);
END;
$BODY$
  LANGUAGE plpgsql IMMUTABLE
  COST 100;

COMMENT ON FUNCTION get_output_srid(geometry)
  IS 'Determined the preferred local projection for a geometry. Will either be a UTM zone EPSG code, or a local projection where a preferred map system is known.';
COMMENT ON FUNCTION sref_system_to_srid(character varying)
  IS 'Converts a spatial reference system code (.e.g. OSGB) to an SRID (e.g. 27700). SRIDs returned are the projection EPSG code.';
COMMENT ON FUNCTION srid_to_sref_system(integer)
  IS 'Converts an SRID (e.g. 27700) to a spatial reference system code (.e.g. OSGB). SRIDs accepted are the projection EPSG code.';
COMMENT ON FUNCTION format_geom_as_latlong(geometry, integer, integer)
  IS 'Converts a geometry centroid to a lat long formatted string.';
COMMENT ON FUNCTION reduce_precision(geometry, boolean, integer)
  IS 'Returns a geometry blurred to the precision of a certain grid square size.';