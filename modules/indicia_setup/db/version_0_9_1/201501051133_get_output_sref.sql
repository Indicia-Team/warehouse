CREATE OR REPLACE FUNCTION sref_system_to_srid(IN sref_system CHARACTER VARYING)
  RETURNS INTEGER AS
  $BODY$
  BEGIN
    RETURN CASE lower(sref_system)
           WHEN 'osgb' THEN 27700
           WHEN 'osie' THEN 29901
           WHEN 'lugr' THEN 2169
           WHEN 'mtb' THEN 4745
           WHEN 'guernsey' THEN 3108
           WHEN 'jersey' THEN 3109
           ELSE sref_system :: INTEGER
           END;
  END;
  $BODY$
LANGUAGE plpgsql VOLATILE;


CREATE OR REPLACE FUNCTION convert_east_north_to_osgb(IN east FLOAT, IN north FLOAT, IN accuracy INTEGER)
RETURNS CHARACTER VARYING AS
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
LANGUAGE plpgsql VOLATILE;


CREATE OR REPLACE FUNCTION convert_east_north_to_osie(IN east FLOAT, IN north FLOAT, IN accuracy INTEGER)
RETURNS CHARACTER VARYING AS
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
LANGUAGE plpgsql VOLATILE;


CREATE OR REPLACE FUNCTION format_geom_as_latlong(IN geom GEOMETRY, IN srid INTEGER, accuracy INTEGER)
RETURNS CHARACTER VARYING AS
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
LANGUAGE plpgsql VOLATILE;