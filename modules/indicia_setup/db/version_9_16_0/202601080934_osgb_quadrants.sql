CREATE OR REPLACE FUNCTION convert_east_north_to_osgb(
	east double precision,
	north double precision,
	accuracy integer)
    RETURNS character varying
    LANGUAGE 'plpgsql'

    COST 100
    IMMUTABLE

AS $BODY$
DECLARE precision INTEGER;
DECLARE hundredKmE INTEGER;
DECLARE hundredKmN INTEGER;
DECLARE firstLetter CHAR;
DECLARE secondLetter CHAR;
DECLARE idx INTEGER;
DECLARE e INTEGER;
DECLARE n INTEGER;
DECLARE wantTetrad BOOLEAN;
DECLARE wantQuadrant BOOLEAN;
-- DINTY or Quadrant suffix characters.
DECLARE suffix TEXT;
DECLARE xdiff FLOAT;
DECLARE ydiff FLOAT;
DECLARE dintyCharIdx INT;
BEGIN

wantTetrad = CASE accuracy WHEN 2000 THEN true ELSE false END;
wantQuadrant = CASE accuracy WHEN 5000 THEN true ELSE false END;
IF wantTetrad OR wantQuadrant THEN
  -- Tetrads and Quadrants are based on 10km squares with a suffix.
  accuracy = 10000;
END IF;
precision = 12 - LENGTH(accuracy::varchar)*2;
hundredKmE = FLOOR(east / 100000);
hundredKmN = FLOOR(north / 100000);

-- Work out the first letter in the grid square notation.
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

-- Work out the second letter.
idx = 65 + ((4 - (hundredKmN % 5)) * 5) + (hundredKmE % 5);
-- Shift index along if letter is greater than I, since I is skipped.
if (idx >= 73) THEN
  idx = idx + 1;
END IF;
secondLetter = CHR(idx);

e = FLOOR((east - (100000 * hundredKmE)) / accuracy);
n = FLOOR((north - (100000 * hundredKmN)) / accuracy);
suffix = '';
IF wantTetrad THEN
  xdiff = ((east - (100000 * hundredKmE)) / accuracy) - e;
  ydiff = ((north - (100000 * hundredKmN)) / accuracy) - n;
  dintyCharIdx = FLOOR(xdiff * 5) * 5 + FLOOR(ydiff * 5);
  if (dintyCharIdx > 13) THEN
    -- O skipped in DINTY.
    dintyCharIdx = dintyCharIdx + 1;
  END IF;
  suffix = CHR(65 + dintyCharIdx);
ELSEIF wantQuadrant THEN
  xdiff = ((east - (100000 * hundredKmE)) / accuracy) - e;
  ydiff = ((north - (100000 * hundredKmN)) / accuracy) - n;
  IF xdiff >= 0.5 AND ydiff >= 0.5 THEN
    suffix = 'NE';
  ELSIF xdiff < 0.5 AND ydiff >= 0.5 THEN
    suffix = 'NW';
  ELSIF xdiff < 0.5 AND ydiff < 0.5 THEN
    suffix = 'SW';
  ELSE
    suffix = 'SE';
  END IF;
END IF;
RETURN firstLetter || secondLetter ||
    LPAD(e::varchar, precision/2, '0') || LPAD(n::varchar, precision/2, '0') || suffix;

END
$BODY$;


-- Convert an OSGB grid ref notation to a Web Mercator geometry.
CREATE OR REPLACE FUNCTION get_geom_from_osgb_grid_ref(IN grid_ref character varying)
    RETURNS public.geometry
    LANGUAGE 'plpgsql'

AS $BODY$
DECLARE
  char1 char;
  char2ord integer;
  dintyLetterOrd integer;
  north integer;
  east integer;
  sqEast integer;
  sqNorth integer;
  sqSize integer;
  coordLen integer;
  westEdge integer;
  eastEdge integer;
  southEdge integer;
  northEdge integer;
  wkt text;
BEGIN

  char1 = SUBSTRING(grid_ref FROM 1 FOR 1);
  north = CASE char1
    WHEN 'H' THEN 1000000
    WHEN 'N' THEN 500000
    WHEN 'O' THEN 500000
    ELSE 0
  END;
  east = CASE char1
    WHEN 'O' THEN 500000
    WHEN 'T' THEN 500000
    ELSE 0
  END;

  char2ord = ASCII(SUBSTRING(grid_ref FROM 2 FOR 1));
  if char2ord > 73 THEN
    -- Adjust for no I.
    char2ord = char2ord - 1;
  END IF;
  east = east + ((char2ord - 65) % 5) * 100000;
  north = north + (4 - floor((char2ord - 65) / 5)) * 100000;

  IF length(grid_ref) = 5 THEN
    -- Assume DINTY Tetrad format 2km squares.
    sqSize = 2000;
    -- Extract the easting and northing.
    sqEast = SUBSTRING(grid_ref FROM 3 FOR 1) * 10000;
    sqNorth = SUBSTRING(grid_ref FROM 4 FOR 1) * 10000;
    dintyLetterOrd = ascii(SUBSTRING(grid_ref FROM 5 FOR 1));
    if dintyLetterOrd > 79 THEN
      -- Adjust for no O.
      dintyLetterOrd = dintyLetterOrd - 1;
    END IF;
    sqEast = sqEast + floor((dintyLetterOrd - 65) / 5) * 2000;
    sqNorth = sqNorth + ((dintyLetterOrd - 65) % 5) * 2000;
  ELSEIF length(grid_ref) = 6 AND SUBSTRING(grid_ref FROM 5 FOR 2) IN ('SW', 'SE', 'NW', 'NE') THEN
    -- 5km Quadrant squares.
    sqSize = 5000;
    -- Extract the easting and northing.
    sqEast = SUBSTRING(grid_ref FROM 3 FOR 1) * 10000;
    sqNorth = SUBSTRING(grid_ref FROM 4 FOR 1) * 10000;
    IF SUBSTRING(grid_ref FROM 5 FOR 1) = 'E' THEN
      sqEast = sqEast + 5000;
    END IF;
    IF SUBSTRING(grid_ref FROM 6 FOR 1) = 'S' THEN
      sqNorth = sqNorth + 5000;
    END IF;
  ELSE
    -- Normal Numeric Format.
    coordLen = (length(grid_ref) - 2) / 2;
    -- Extract the easting and northing.
    sqEast  = CASE WHEN length(grid_ref) > 2 THEN SUBSTRING(grid_ref FROM 3 FOR coordLen)::integer ELSE 0 END;
    sqNorth = CASE WHEN length(grid_ref) > 2 THEN SUBSTRING(grid_ref FROM 3 + coordLen)::integer ELSE 0 END;
    -- If < 10 figure the easting and northing need to be multiplied up to the power of 10.
    sqSize = pow(10, 5 - coordLen);
    sqEast = sqEast * sqSize;
    sqNorth = sqNorth * sqSize;
  END IF;
  westEdge = sqEast + east;
  southEdge = sqNorth + north;
  eastEdge = westEdge + sqSize;
  northEdge = southEdge + sqSize;
  wkt = 'POLYGON((' ||
  westEdge::text || ' ' || southEdge::text || ',' ||
  eastEdge::text || ' ' || southEdge::text || ',' ||
  eastEdge::text || ' ' || northEdge::text || ',' ||
  westEdge::text || ' ' || northEdge::text || ',' ||
  westEdge::text || ' ' || southEdge::text || '))';
  RETURN st_transform(st_geomfromtext(wkt, 27700), 900913);
END
$BODY$;