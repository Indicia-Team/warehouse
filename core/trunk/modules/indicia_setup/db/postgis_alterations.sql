--Add support for spherical mercator (900913) to the spatial references in PostGIS, so that common online
--mapping layers can be supported
INSERT into spatial_ref_sys (srid, auth_name, auth_srid, srtext, proj4text)
SELECT 900913 ,'EPSG',900913,'GEOGCS["WGS 84", DATUM["World Geodetic System
1984", SPHEROID["WGS 84", 6378137.0, 298.257223563,AUTHORITY["EPSG","7030"]], AUTHORITY["EPSG","6326"]],PRIMEM["Greenwich", 0.0, AUTHORITY["EPSG","8901"]], NIT["degree",0.017453292519943295], AXIS["Longitude", EAST], AXIS["Latitude", NORTH],AUTHORITY["EPSG","4326"]], PROJECTION["Mercator_1SP"],PARAMETER["semi_minor", 6378137.0],
PARAMETER["latitude_of_origin",0.0], PARAMETER["central_meridian", 0.0], PARAMETER["scale_factor",1.0], PARAMETER["false_easting", 0.0], PARAMETER["false_northing", 0.0],UNIT["m", 1.0], AXIS["x", EAST], AXIS["y", NORTH],AUTHORITY["EPSG","900913"]] |','+proj=merc +a=6378137 +b=6378137 +lat_ts=0.0 +lon_0=0.0 +x_0=0.0 +y_0=0 +k=1.0 +units=m
+nadgrids=@null +no_defs'
WHERE NOT EXISTS(SELECT srid FROM spatial_ref_sys WHERE srid=900913);

-- Update the OSGB systems (easting/northing and lat long OSGB36) so they correctly use the OSGB36 datum
UPDATE spatial_ref_sys SET proj4text = '+proj=longlat +ellps=airy +datum=OSGB36 +no_defs' WHERE srid=4277;
UPDATE spatial_ref_sys SET proj4text = '+proj=tmerc +lat_0=49 +lon_0=-2 +k=0.999601 +x_0=400000 +y_0=-100000 +ellps=airy +units=m +no_defs +datum=OSGB36' WHERE srid=27700;