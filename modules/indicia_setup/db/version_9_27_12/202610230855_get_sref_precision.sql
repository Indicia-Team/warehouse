CREATE OR REPLACE FUNCTION get_sref_precision(
    sref character varying,
    sref_system character varying,
    attr_sref_precision float)
  RETURNS float AS
$BODY$
BEGIN

  RETURN coalesce(attr_sref_precision, CASE lower(sref_system)
    WHEN '4326' THEN 50
    WHEN 'osie' THEN
      CASE
        WHEN sref ~ '^[A-Z]\d\d[A-Z]$' THEN 2000
        WHEN sref ~ '^[A-Z]\d\d[NS][EW]$' THEN 5000
        ELSE pow(10, (11-length(replace(sref, ' ', '')))/2)
      END
    WHEN 'osgb' THEN
      CASE
        WHEN sref ~ '^[A-Z][A-Z]\d\d[A-Z]$' THEN 2000
        WHEN sref ~ '^[A-Z][A-Z]\d\d[NS][EW]$' THEN 5000
        ELSE pow(10, (12-length(replace(sref, ' ', '')))/2)
      END
    WHEN 'utm30ed50' THEN CASE LENGTH(replace(sref, ' ', '')) WHEN 5 THEN 2000 ELSE pow(10, (12-length(replace(sref, ' ', '')))/2) END
    WHEN 'mtbqqq' THEN NULL
    ELSE 1
  END);

END
$BODY$
  LANGUAGE plpgsql IMMUTABLE
  COST 100;