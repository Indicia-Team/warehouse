CREATE OR REPLACE FUNCTION convert_east_north_to_osgb(east double precision, north double precision, accuracy integer)
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

-- work out the first letter in the grid square notation
IF hundredKmN < 5 THEN
  IF hundredKmE < 5 THEN
    firstLetter = 'S';
  ELSE
    firstLetter = 'T';
  END IF;
ELSEIF hundredKmN < 10 THEN
  IF (hundredKmE < 5) THEN
    firstLetter = 'N';
  ELSE
    firstLetter = 'O';
  END IF;
ELSE
  firstLetter = 'H';
END IF;

-- work out the second letter
idx = 65 + ((4 - (hundredKmN % 5)) * 5) + (hundredKmE % 5);
-- Shift index along if letter is greater than I, since I is skipped
if (idx >= 73) THEN
  idx = idx + 1;
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



CREATE OR REPLACE FUNCTION convert_east_north_to_osie(east double precision, north double precision, accuracy integer)
  RETURNS character varying AS
$BODY$
DECLARE precision INTEGER;
DECLARE hundredKmE INTEGER;
DECLARE hundredKmN INTEGER;
DECLARE firstLetter CHAR;
DECLARE idx INTEGER;
DECLARE e INTEGER;
DECLARE n INTEGER;
BEGIN
  precision = 12 - LENGTH(accuracy::varchar)*2;
  hundredKmE = FLOOR(east / 100000);
	hundredKmN = FLOOR(north / 100000);

	idx = 65 + ((4 - (hundredKmN % 5)) * 5) + (hundredKmE % 5);
  -- Shift index along if letter is greater than I, since I is skipped
  if idx >= 73 THEN
    idx = idx + 1;
  END IF;
  firstLetter = CHR(idx);
  e = FLOOR((east - (100000 * hundredKmE)) / accuracy);
  n = FLOOR((north - (100000 * hundredKmN)) / accuracy);
  RETURN firstLetter ||
      LPAD(e::varchar, precision/2, '0') || LPAD(n::varchar, precision/2, '0');
END
$BODY$
  LANGUAGE plpgsql IMMUTABLE
  COST 100;

CREATE OR REPLACE FUNCTION format_geom_as_latlong(geom geometry, srid integer, accuracy integer)
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
  RETURN abs(y) || CASE WHEN y>=0 THEN 'N' ELSE 'S' END || ' ' || abs(x) || CASE WHEN x>=0 THEN 'E' ELSE 'W' END;
END
$BODY$
  LANGUAGE plpgsql IMMUTABLE
  COST 100;


  
CREATE OR REPLACE FUNCTION get_output_sref(sref character varying, sref_system character varying, accuracy integer, geom geometry)
  RETURNS character varying AS
$BODY$
DECLARE output_system CHARACTER VARYING;
DECLARE output_srid INTEGER;
DECLARE centroid_in_srid GEOMETRY;
DECLARE east FLOAT;
DECLARE north FLOAT;
DECLARE usedAccuracy INTEGER;
BEGIN

-- set a default if accuracy not recorded.
usedAccuracy = COALESCE(accuracy, 10);
-- no support for DINTY at this point
IF usedAccuracy=2000 THEN
  usedAccuracy=10000;
END IF;
-- Find the best local grid system appropriate to the area on the map
output_system = get_output_system(geom, sref_system, '4326');
output_srid = sref_system_to_srid(output_system);
centroid_in_srid = st_transform(st_centroid(geom), output_srid);
east = st_x(centroid_in_srid);
north = st_y(centroid_in_srid);
-- this currently only supports OSGB and OSIE so will need extending to support other grid systems
IF output_system='OSGB' THEN
  RETURN convert_east_north_to_osgb(east, north, usedAccuracy);
ELSEIF output_system='OSIE' THEN
  RETURN convert_east_north_to_osie(east, north, usedAccuracy);
ELSE
  RETURN format_geom_as_latlong(geom, 4326, accuracy);
END IF;

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
    IF (st_x(geom) BETWEEN 46000 AND 108000) AND (st_y(geom) BETWEEN 55000 AND 141000) THEN -- exact check for OSGB
      sys = 'LUGR'; -- 2169
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



CREATE OR REPLACE FUNCTION quality_check(quality character varying, record_status character, certainty character)
  RETURNS boolean AS
$BODY$
DECLARE r boolean;
BEGIN
r=
  -- always include verified
  ((record_status='V') OR
  -- include certain data if requested, unless expert marked as dubious
  (certainty IS NOT NULL AND quality='C' AND certainty='C' AND record_status <> 'D') OR
  -- include certain or likely data if requested, unless expert marked as dubious. Certainty not indicated treated as likely
  (quality='L' AND (certainty in ('C', 'L') OR (certainty IS NULL)) AND record_status <> 'D') OR
  -- include anything not dubious or worse, if requested
  (quality='!D' AND record_status NOT IN ('D')) OR
  -- or just include anything not rejected
  (quality='!R')) AND 
  -- always exclude rejected, in progress and test records
  record_status NOT IN ('R', 'I', 'T');
RETURN r;
END;
$BODY$
  LANGUAGE plpgsql IMMUTABLE
  COST 100;



CREATE OR REPLACE FUNCTION reduce_precision(geom_in geometry, confidential boolean, reduce_to_precision integer, sref_system character varying)
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
  IF confidential = true OR reduce_to_precision IS NOT NULL THEN
    precisionM = CASE
      WHEN reduce_to_precision IS NOT NULL THEN reduce_to_precision
      ELSE 1000
    END;
    -- If already low precision, then can return as it is
    IF sqrt(st_area(geom)) >= precisionM THEN
      r = geom_in;
    ELSE
      sys = get_output_system(geom_in, sref_system);
      srid = CASE sys 
        WHEN 'OSGB' THEN 27700 
        WHEN 'OSIE' THEN 29901
        WHEN 'LUGR' THEN 2169
        ELSE sys::integer
      END;
      IF srid<>900913 THEN
        geom = st_transform(st_centroid(geom_in), srid);
      ELSE
        geom = st_centroid(geom_in);
      END IF;
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

CREATE OR REPLACE FUNCTION sref_system_to_srid(sref_system character varying)
  RETURNS integer AS
$BODY$
  BEGIN
    RETURN CASE lower(sref_system)
           WHEN 'osgb' THEN 27700
           WHEN 'osie' THEN 29901
           WHEN 'lugr' THEN 2169
           WHEN 'mtbqqq' THEN 4745
           WHEN 'guernsey' THEN 3108
           WHEN 'jersey' THEN 3109
           ELSE sref_system :: INTEGER
           END;
  END;
  $BODY$
  LANGUAGE plpgsql IMMUTABLE
  COST 100;



CREATE OR REPLACE FUNCTION taxon_abbreviation(taxon character varying)
  RETURNS character varying AS
$BODY$
DECLARE 
simplified character varying;
BEGIN
-- remove the subgenera in brackets if they exist
simplified = regexp_replace(taxon, E'\\(.+\\) ', '', 'g');
RETURN lower(substring(split_part(simplified, ' ', 1) from 1 for 2) || substring(split_part(simplified, ' ', 2) from 1 for 3));
END;
$BODY$
  LANGUAGE plpgsql IMMUTABLE
  COST 100;