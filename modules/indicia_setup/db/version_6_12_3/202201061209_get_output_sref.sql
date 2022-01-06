CREATE OR REPLACE FUNCTION get_output_sref(
	accuracy integer,
	geom geometry)
    RETURNS character varying
    LANGUAGE 'plpgsql'
    COST 100
    IMMUTABLE PARALLEL UNSAFE
AS $BODY$
DECLARE output_srid INTEGER;
DECLARE centroid_in_srid GEOMETRY;
DECLARE east FLOAT;
DECLARE north FLOAT;
DECLARE usedAccuracy INTEGER;
DECLARE prefix VARCHAR;
BEGIN

-- Set a default if accuracy not recorded.
usedAccuracy = COALESCE(accuracy, 10);
-- Prefix for imprecise data that can't be reflected in a grid notation.
prefix = CASE
  WHEN usedAccuracy>100000 then '~ '
  ELSE ''
END;
-- Find the best local grid system appropriate to the area on the map
output_srid = get_output_srid(geom);
-- Round accuracy up to a supported grid square size.
usedAccuracy = CASE
   WHEN usedAccuracy>10000 THEN 100000
   WHEN usedAccuracy>2000 THEN 10000
   -- Only support Tetrads in OSGB currently.
   WHEN usedAccuracy>1000 THEN CASE output_srid WHEN 27700 THEN 2000 ELSE 10000 END
   WHEN usedAccuracy>100 THEN 1000
   WHEN usedAccuracy>10 THEN 100
   ELSE 10
END;

centroid_in_srid = st_transform(st_centroid(geom), output_srid);
east = st_x(centroid_in_srid);
north = st_y(centroid_in_srid);
-- this currently only supports OSGB and OSIE so will need extending to support other grid systems
IF output_srid=27700 THEN
  RETURN prefix || convert_east_north_to_osgb(east, north, usedAccuracy);
ELSEIF output_srid=29901 THEN
  RETURN prefix || convert_east_north_to_osie(east, north, usedAccuracy);
ELSEIF output_srid=23030 THEN
  RETURN prefix || convert_east_north_to_utm30ed50(east, north, usedAccuracy);
ELSE
  RETURN prefix || format_geom_as_latlong(geom, 4326, accuracy);
END IF;

END
$BODY$;