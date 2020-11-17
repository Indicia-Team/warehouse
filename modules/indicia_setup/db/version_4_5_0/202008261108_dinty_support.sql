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
DECLARE tetradChar CHAR;
DECLARE xdiff FLOAT;
DECLARE ydiff FLOAT;
DECLARE dintyCharIdx INT;
BEGIN

wantTetrad = CASE accuracy WHEN 2000 THEN true ELSE false END;
IF wantTetrad = true THEN
  accuracy = 10000;
END IF;
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

IF wantTetrad THEN
  xdiff = ((east - (100000 * hundredKmE)) / accuracy) - e;
  ydiff = ((north - (100000 * hundredKmN)) / accuracy) - n;
  dintyCharIdx = FLOOR(xdiff * 5) * 5 + FLOOR(ydiff * 5);
  if (dintyCharIdx > 13) THEN
    -- Skip O
    dintyCharIdx = dintyCharIdx + 1;
  END IF;
  tetradChar = CHR(65 + dintyCharIdx);
ELSE
  tetradChar = '';
END IF;
RETURN firstLetter || secondLetter ||
    LPAD(e::varchar, precision/2, '0') || LPAD(n::varchar, precision/2, '0') || tetradChar;

END
$BODY$;
