CREATE OR REPLACE FUNCTION get_geom_from_utm30ed50_grid_ref(IN grid_ref character varying)
	RETURNS public.geometry
	LANGUAGE 'plpgsql'
AS $BODY$
DECLARE
	char1ord integer;
	dintyLetterOrd integer;
	sq_north integer;
	sq_east integer;
	char2 text;
	north integer;
	east integer;
	sq_code_letter_ord integer;
	char2ord integer;
	sq_size integer;
	coordLen integer;
	westEdge integer;
	southEdge integer;
	eastEdge integer;
	northEdge integer;
	wkt text;
BEGIN
	sq_east = 100000;
	char1ord = ASCII(SUBSTRING(grid_ref FROM 1 FOR 1));
	sq_east = sq_east + (char1ord - ASCII('S')) * 100000;
	char2 = SUBSTRING(grid_ref FROM 2 FOR 1);
	IF (char2 = 'U') THEN
		sq_north = 5300000;
	ELSEIF (char2 = 'V') THEN
		sq_north = 5400000;
	ELSE
		char2ord = ASCII(char2);
		sq_north = 5500000 + ((char2ord - ASCII('A')) * 100000);
	END IF;
	IF (LENGTH(grid_ref)=5) THEN
		-- Assume DINTY Tetrad format 2km squares
		-- extract the easting and northing
		east = SUBSTRING(grid_ref FROM 3 FOR 1);
		north = SUBSTRING(grid_ref FROM 4 FOR 1);
		sq_code_letter_ord = ASCII(SUBSTRING(grid_ref FROM 5 FOR 1));
		IF (sq_code_letter_ord > 79) THEN
			-- Adjust for no O
			sq_code_letter_ord = sq_code_letter_ord - 1;
		END IF;
		sq_size = 2000;
		east = east * 10000 + floor((sq_code_letter_ord - 65) / 5) * 2000;
		north = north * 10000 + ((sq_code_letter_ord - 65) % 5) * 2000;
	ELSE
		-- Normal Numeric Format
		coordLen = (LENGTH(grid_ref)-2)/2;
		-- extract the easting and northing
		RAISE NOTICE '%', grid_ref;
		east  = SUBSTRING(grid_ref FROM 3 FOR coordLen);
		north = SUBSTRING(grid_ref FROM 3 + coordLen FOR coordLen);
		-- if < 10 figure the easting and northing need to be multiplied up to the power of 10
		sq_size = pow(10, 5 - coordLen);
		east = east * sq_size;
		north = north * sq_size;
	END IF;
	westEdge = east + sq_east;
	southEdge = north + sq_north;
	eastEdge = westEdge + sq_size;
	northEdge = southEdge + sq_size;
	wkt = 'POLYGON((' ||
		westEdge::text || ' ' || southEdge::text || ',' ||
		eastEdge::text || ' ' || southEdge::text || ',' ||
		eastEdge::text || ' ' || northEdge::text || ',' ||
		westEdge::text || ' ' || northEdge::text || ',' ||
		westEdge::text || ' ' || southEdge::text || '))';
	RETURN st_transform(st_geomfromtext(wkt, 23030), 900913);
END
$BODY$;