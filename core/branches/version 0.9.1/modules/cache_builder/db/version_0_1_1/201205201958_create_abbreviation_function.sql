
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
  LANGUAGE plpgsql VOLATILE
  COST 100;

COMMENT ON FUNCTION taxon_abbreviation(taxon character varying) IS 
  'Takes a latin taxon name and returns the 2 plus 3 character abbreviation often used to look it up. For example "Andrena fulva" becomes anful.';