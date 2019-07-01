-- Function: reduce_precision(geometry, boolean, integer, character varying)

-- DROP FUNCTION reduce_precision(geometry, boolean, integer, character varying);

CREATE OR REPLACE FUNCTION reduce_precision(
    geom_in geometry,
    confidential boolean,
    reduce_to_precision integer,
    sref_system character varying)
  RETURNS geometry AS
$BODY$
DECLARE geom geometry;
DECLARE geomltln geometry;
DECLARE r geometry;
DECLARE precisionM integer;
DECLARE x float;
DECLARE y float;
DECLARE sys varchar;
DECLARE srid integer;
DECLARE sref_metadata record;
BEGIN
  IF confidential = true OR COALESCE(reduce_to_precision, 0)>1 THEN
    precisionM = CASE
      WHEN COALESCE(reduce_to_precision, 0)>1 THEN reduce_to_precision
      ELSE 1000
    END;
    sys = get_output_system(geom_in, sref_system);
    srid = CASE sys
      WHEN 'OSGB' THEN 27700
      WHEN 'OSIE' THEN 29901
      WHEN 'LUGR' THEN 2169
      WHEN 'utm30ed50' THEN 23030
      ELSE sys::integer
    END;
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

CREATE OR REPLACE FUNCTION convert_east_north_to_utm30ed50(east double precision, north double precision, accuracy integer)
  RETURNS character varying AS
$BODY$
DECLARE precision INTEGER;
DECLARE hundredKmE INTEGER;
DECLARE hundredKmN INTEGER;
DECLARE firstLetter CHAR;
DECLARE secondLetter CHAR;
DECLARE idx INTEGER;
DECLARE e INTEGER;
DECLARE n INTEGER;
BEGIN

precision = 12 - LENGTH(accuracy::varchar)*2;

hundredKmE = FLOOR(east / 100000);
hundredKmN = FLOOR(north / 100000);
idx = ASCII('S') + hundredKmE - 1;
firstLetter = CHR(idx);

IF hundredKmN < 55 THEN
  idx = ASCII('U') + hundredKmN - 53;
ELSE
  idx = ASCII('A') + hundredKmN - 55;
END IF;
secondLetter = CHR(idx);

e = FLOOR((east - (100000 * hundredKmE)) / accuracy);
n = FLOOR((north - (100000 * hundredKmN)) / accuracy);
RETURN firstLetter || secondLetter ||
    LPAD(e::varchar, precision/2, '0') || LPAD(n::varchar, precision/2, '0');

END
$BODY$
  LANGUAGE plpgsql IMMUTABLE
  COST 100;

CREATE OR REPLACE FUNCTION get_output_system(geom_in geometry, sref_system character varying, default_system character varying default '900913')
  RETURNS character varying AS
$BODY$
DECLARE geom geometry;
DECLARE sys varchar;
DECLARE sref_metadata record;
DECLARE
BEGIN

  -- look for some preferred grids to see if we are in range.
  sys=null;
  IF (st_x(st_centroid(geom_in)) BETWEEN -1196000 AND -599200) AND (st_y(st_centroid(geom_in)) BETWEEN 6687800 AND 7442470) THEN -- rough check for OSIE
    geom = st_transform(st_centroid(geom_in), 29901);
    IF (st_x(geom) BETWEEN 10000 AND 367300) AND (st_y(geom) BETWEEN 10000 AND 468100) AND (st_x(geom)<332000 OR st_y(geom)<445900) THEN -- exact check for OSIE. Cut out top right corner.
      sys = 'OSIE'; -- 29901
    END IF;
  END IF;
  IF (sys IS NULL) AND (st_x(st_centroid(geom_in)) BETWEEN -1081873 AND 422933) AND (st_y(st_centroid(geom_in)) BETWEEN 6405988 AND 8944478) THEN -- rough check for OSGB
    geom = st_transform(st_centroid(geom_in), 27700);
    IF st_x(geom) BETWEEN 0 AND 700000 AND st_y(geom) BETWEEN 0 AND 14000000 THEN -- exact check for OSGB
      sys = 'OSGB'; -- 27700
    END IF;
  END IF;
  IF (sys IS NULL) AND (st_x(st_centroid(geom_in)) BETWEEN 634030 AND 729730) AND (st_y(st_centroid(geom_in)) BETWEEN 6348260 AND 6484930) THEN -- rough check for LUGR
    geom = st_transform(st_centroid(geom_in), 2169);
    IF (st_x(geom) BETWEEN 46000 AND 108000) AND (st_y(geom) BETWEEN 55000 AND 141000) THEN -- exact check for LUGR
      sys = 'LUGR'; -- 2169
    END IF;
  END IF;
  IF (sys IS NULL) AND (st_x(st_centroid(geom_in)) BETWEEN -257600 AND -210500) AND (st_y(st_centroid(geom_in)) BETWEEN 6271000 AND 6415000) THEN -- rough check for channel islands
    geom = st_transform(st_centroid(geom_in), 23030);
    IF (st_x(geom) BETWEEN 540000 AND 585000) AND (st_y(geom) BETWEEN 5435000 AND 5465000) OR -- exact check for Jersey area
      (st_x(geom) BETWEEN 515000 AND 555000) AND (st_y(geom) BETWEEN 5465000 AND 5490000) OR -- Guernsey area
      (st_x(geom) BETWEEN 530000 AND 565000) AND (st_y(geom) BETWEEN 5495000 AND 5515000) THEN -- Alderney area
      sys = 'utm30ed50'; -- 23030
    END IF;
  END IF;
  IF sys IS NULL THEN
    SELECT INTO sref_metadata srid, treat_srid_as_x_y_metres FROM spatial_systems WHERE code ilike sref_system OR code ilike 'EPSG:' || sref_system;
    IF COALESCE(sref_metadata.treat_srid_as_x_y_metres, false) THEN
      sys = sref_metadata.srid::varchar;
    ELSE
      -- revert to the web-mercator grid (or other default) for unknown parts of the world.
      sys = default_system;
    END IF;
  END IF;
  RETURN sys;
END;
$BODY$
  LANGUAGE plpgsql STABLE
  COST 100;
