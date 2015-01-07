DROP FUNCTION get_output_system(geometry, character varying);

CREATE OR REPLACE FUNCTION get_output_system(
    geom_in geometry,
    sref_system character varying,
    default_system character varying default '900913')
  RETURNS character varying AS
$BODY$
DECLARE geom geometry;
DECLARE sys varchar;
DECLARE sref_metadata record;
DECLARE
BEGIN

  -- look for some preferred grids to see if we are in range.
  sys=null;
  IF (st_x(st_centroid(geom_in)) BETWEEN -1196000 AND -599200) AND (st_y(st_centroid(geom_in)) BETWEEN 6687800 AND 7442470) THEN -- rough check for OSIE
    geom = st_transform(st_centroid(geom_in), 29901);
    IF (st_x(geom) BETWEEN 10000 AND 367300) AND (st_y(geom) BETWEEN 10000 AND 468100) AND (st_x(geom)<332000 OR st_y(geom)<445900) THEN -- exact check for OSIE. Cut out top right corner.
      sys = 'OSIE'; -- 29901
    END IF;
  END IF;
  IF (sys IS NULL) AND (st_x(st_centroid(geom_in)) BETWEEN -1081873 AND 422933) AND (st_y(st_centroid(geom_in)) BETWEEN 6405988 AND 8944478) THEN -- rough check for OSGB
    geom = st_transform(st_centroid(geom_in), 27700);
    IF st_x(geom) BETWEEN 0 AND 700000 AND st_y(geom) BETWEEN 0 AND 14000000 THEN -- exact check for OSGB
      sys = 'OSGB'; -- 27700
    END IF;
  END IF;
  IF (sys IS NULL) AND (st_x(st_centroid(geom_in)) BETWEEN 634030 AND 729730) AND (st_y(st_centroid(geom_in)) BETWEEN 6348260 AND 6484930) THEN -- rough check for LUGR
    geom = st_transform(st_centroid(geom_in), 2169);
    IF (st_x(geom) BETWEEN 46000 AND 108000) AND (st_y(geom) BETWEEN 55000 AND 141000) THEN -- exact check for OSGB
      sys = 'LUGR'; -- 2169
    END IF;
  END IF;
  IF sys IS NULL THEN
    SELECT INTO sref_metadata srid, treat_srid_as_x_y_metres FROM spatial_systems WHERE code ilike sref_system OR code ilike 'EPSG:' || sref_system;
    IF COALESCE(sref_metadata.treat_srid_as_x_y_metres, false) THEN
      sys = sref_metadata.srid::varchar;
    ELSE
      -- revert to the web-mercator grid (or other default) for unknown parts of the world.
      sys = default_system;
    END IF;
  END IF;
  RETURN sys;
END;
$BODY$
  LANGUAGE plpgsql VOLATILE
  COST 100;
