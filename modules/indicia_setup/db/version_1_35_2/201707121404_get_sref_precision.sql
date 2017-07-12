CREATE OR REPLACE FUNCTION get_sref_precision(
    sref character varying,
    sref_system character varying,
    attr_sref_precision float)
  RETURNS float AS
$BODY$
BEGIN

  RETURN coalesce(attr_sref_precision, case lower(sref_system)
    when '4326' then 50
    when 'osie' then case length(replace(sref, ' ', '')) when 4 then 2000 else pow(10, (11-length(replace(sref, ' ', '')))/2) end
    when 'osgb' then case length(replace(sref, ' ', '')) when 5 then 2000 else pow(10, (12-length(replace(sref, ' ', '')))/2) end
    when 'utm30ed50' then case length(replace(sref, ' ', '')) when 5 then 2000 else pow(10, (12-length(replace(sref, ' ', '')))/2) end
    when 'mtbqqq' then NULL
    else 1
  end);

END
$BODY$
  LANGUAGE plpgsql IMMUTABLE
  COST 100;