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
           WHEN 'utm30ed50' THEN 23030
           WHEN 'utm30wgs84' THEN 32630
           ELSE sref_system :: INTEGER
           END;
  END;
  $BODY$
  LANGUAGE plpgsql VOLATILE
  COST 100;