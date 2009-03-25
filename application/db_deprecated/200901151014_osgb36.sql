-- Update the OSGB systems (easting/northing and lat long) so they correctly use the OSGB36 datum
UPDATE spatial_ref_sys SET proj4text = '+proj=longlat +ellps=airy +datum=OSGB36 +no_defs' WHERE srid=4277;
UPDATE spatial_ref_sys SET proj4text = '+proj=tmerc +lat_0=49 +lon_0=-2 +k=0.999601 +x_0=400000 +y_0=-100000 +ellps=airy +units=m +no_defs +datum=OSGB36' WHERE srid=27700;

