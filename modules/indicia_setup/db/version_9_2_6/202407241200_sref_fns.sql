-- A couple of potentially useful functions.

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

  if length(grid_ref) = 5 THEN
  -- Assume DINTY Tetrad format 2km squares
    -- extract the easting and northing.
    sqEast = SUBSTRING(grid_ref FROM 3 FOR 1);
    sqNorth = SUBSTRING(grid_ref FROM 4 FOR 1);
    dintyLetterOrd = ascii(SUBSTRING(grid_ref FROM 5 FOR 1));
    if dintyLetterOrd > 79 THEN
      -- Adjust for no O.
      dintyLetterOrd = dintyLetterOrd - 1;
    END IF;
    sqSize = 2000;
    sqEast = sqEast * 10000 + floor((dintyLetterOrd - 65) / 5) * 2000;
    sqNorth = sqNorth * 10000 + ((dintyLetterOrd - 65) % 5) * 2000;
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

-- Convert an Irish grid ref notation to a Web Mercator geometry.
CREATE OR REPLACE FUNCTION get_geom_from_osie_grid_ref(IN grid_ref character varying)
    RETURNS public.geometry
    LANGUAGE 'plpgsql'
AS $BODY$
DECLARE
  char1ord integer;
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

  char1ord = ASCII(SUBSTRING(grid_ref FROM 1 FOR 1));
  if char1ord > 73 THEN
    -- Adjust for no I.
    char1ord = char1ord - 1;
  END IF;
  east = ((char1ord - 65) % 5) * 100000;
  north = (4 - floor((char1ord - 65) / 5)) * 100000;

  if length(grid_ref) = 4 THEN
  -- Assume DINTY Tetrad format 2km squares
    -- extract the easting and northing.
    sqEast = SUBSTRING(grid_ref FROM 2 FOR 1);
    sqNorth = SUBSTRING(grid_ref FROM 1 FOR 1);
    dintyLetterOrd = ascii(SUBSTRING(grid_ref FROM 4 FOR 1));
    if dintyLetterOrd > 79 THEN
      -- Adjust for no O.
      dintyLetterOrd = dintyLetterOrd - 1;
    END IF;
    sqSize = 2000;
    sqEast = sqEast * 10000 + floor((dintyLetterOrd - 65) / 5) * 2000;
    sqNorth = sqNorth * 10000 + ((dintyLetterOrd - 65) % 5) * 2000;
  ELSE
    -- Normal Numeric Format.
    coordLen = (length(grid_ref) - 1) / 2;
    -- Extract the easting and northing.
    sqEast  = CASE WHEN length(grid_ref) > 1 THEN SUBSTRING(grid_ref FROM 2 FOR coordLen)::integer ELSE 0 END;
    sqNorth = CASE WHEN length(grid_ref) > 1 THEN SUBSTRING(grid_ref FROM 2 + coordLen)::integer ELSE 0 END;
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
  RETURN st_transform(st_geomfromtext(wkt, 29903), 900913);
END
$BODY$;