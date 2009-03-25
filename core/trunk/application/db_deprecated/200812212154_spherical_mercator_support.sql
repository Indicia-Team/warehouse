INSERT into spatial_ref_sys (srid, auth_name, auth_srid, srtext, proj4text) values (900913 ,'EPSG',900913,'GEOGCS["WGS 84", DATUM["World Geodetic System 
1984", SPHEROID["WGS 84", 6378137.0, 298.257223563,AUTHORITY["EPSG","7030"]], AUTHORITY["EPSG","6326"]],PRIMEM["Greenwich", 0.0, AUTHORITY["EPSG","8901"]], NIT["degree",0.017453292519943295], AXIS["Longitude", EAST], AXIS["Latitude", NORTH],AUTHORITY["EPSG","4326"]], PROJECTION["Mercator_1SP"],PARAMETER["semi_minor", 6378137.0], 
PARAMETER["latitude_of_origin",0.0], PARAMETER["central_meridian", 0.0], PARAMETER["scale_factor",1.0], PARAMETER["false_easting", 0.0], PARAMETER["false_northing", 0.0],UNIT["m", 1.0], AXIS["x", EAST], AXIS["y", NORTH],AUTHORITY["EPSG","900913"]] |','+proj=merc +a=6378137 +b=6378137 +lat_ts=0.0 +lon_0=0.0 +x_0=0.0 +y_0=0 +k=1.0 +units=m 
+nadgrids=@null +no_defs');



ALTER TABLE locations
	DROP CONSTRAINT enforce_srid_centroid_geom;

ALTER TABLE locations
	DROP CONSTRAINT enforce_srid_boundary_geom;

UPDATE Locations SET centroid_geom=st_transform(st_geomfromtext(astext(centroid_geom),4326), 900913);

UPDATE Locations SET boundary_geom=st_transform(st_geomfromtext(astext(boundary_geom),4326), 900913);

ALTER TABLE locations
  ADD CONSTRAINT enforce_srid_centroid_geom CHECK (srid(centroid_geom) = (900913));

ALTER TABLE locations
  ADD CONSTRAINT enforce_srid_boundary_geom CHECK (srid(boundary_geom) = (900913));

ALTER TABLE samples
	DROP CONSTRAINT enforce_srid_geom;

UPDATE samples SET geom=st_transform(st_geomfromtext(astext(geom),4326), 900913);

ALTER TABLE samples
  ADD CONSTRAINT enforce_srid_geom CHECK (srid(geom) = (900913));