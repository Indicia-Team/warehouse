
DROP FUNCTION reduce_precision(geometry, boolean, integer, integer, character varying);
DROP FUNCTION reduce_precision(geometry, boolean, integer, character varying);
/* 
  Function to work out the best spatial reference system for standardising the output of a given
  geometry. For example, if the geom lies over the British National Grid system then OSGB is returned
  unless over Ireland.
*/
CREATE OR REPLACE FUNCTION get_output_system(geom_in geometry, sref_system character varying)
  RETURNS varchar AS 
$BODY$
DECLARE geom geometry;
DECLARE sys varchar;
DECLARE sref_metadata record;
DECLARE 
BEGIN

  -- look for some preferred grids to see if we are in range.
  sys=null;
  IF (sys IS NULL) AND (st_x(st_centroid(geom)) BETWEEN -1196000 AND -599200) AND (st_y(st_centroid(geom)) BETWEEN 6687800 AND 7442470) THEN -- rough check for OSIE
    geom = st_transform(st_centroid(geom_in), 29901); 
    IF (st_x(geom) BETWEEN 10000 AND 367300) AND (st_y(geom) BETWEEN 10000 AND 468100) AND (st_x(geom)<332000 OR st_y(geom)<445900) THEN -- exact check for OSIE. Cut out top right corner.
      sys = 'OSIE'; -- 29901
    END IF;
  END IF;
  IF (sys IS NULL) AND (st_x(st_centroid(geom)) BETWEEN -1081873 AND 422933) AND (st_y(st_centroid(geom)) BETWEEN 6405988 AND 8944478) THEN -- rough check for OSGB
    geom = st_transform(st_centroid(geom_in), 27700);
    IF st_x(geom) BETWEEN 0 AND 700000 AND st_y(geom) BETWEEN 0 AND 14000000 THEN -- exact check for OSGB
      sys = 'OSGB'; -- 27700
    END IF;
  END IF;
  IF (sys IS NULL) AND (st_x(st_centroid(geom)) BETWEEN 634030 AND 729730) AND (st_y(st_centroid(geom)) BETWEEN 6348260 AND 6484930) THEN -- rough check for LUGR
    geom = st_transform(st_centroid(geom_in), 2169);
    IF (st_x(geom) BETWEEN 46000 AND 108000) AND (st_y(geom) BETWEEN 55000 AND 141000) THEN -- exact check for OSGB
      sys = 'LUGR'; -- 2169
    END IF;
  END IF;
  IF sys IS NULL THEN
    SELECT INTO sref_metadata srid, treat_srid_as_x_y_metres FROM spatial_systems WHERE code=lower(sref_system);
    IF COALESCE(sref_metadata.treat_srid_as_x_y_metres, false) THEN
      sys = sref_metadata.srid::varchar;
    ELSE
      sys = '900913';
    END IF;
  END IF;
  RETURN sys;
END;
$BODY$
  LANGUAGE plpgsql VOLATILE
  COST 100;


CREATE OR REPLACE FUNCTION reduce_precision(geom_in geometry, confidential boolean, reduce_to_precision integer, sref_system character varying)
  RETURNS geometry AS
$BODY$
DECLARE geom geometry;
DECLARE geomltln geometry;
DECLARE r geometry;
DECLARE precisionM integer;
DECLARE x float;
DECLARE y float;
DECLARE sys varchar;
DECLARE srid integer;
DECLARE sref_metadata record;
BEGIN
  IF confidential = true OR reduce_to_precision IS NOT NULL THEN
    precisionM = CASE
      WHEN reduce_to_precision IS NOT NULL THEN reduce_to_precision
      ELSE 1000
    END;
    -- If already low precision, then can return as it is
    IF sqrt(st_area(geom)) >= precisionM THEN
      r = geom_in;
    ELSE
      sys = get_output_system(geom_in, sref_system);
      srid = CASE sys 
        WHEN 'OSGB' THEN 27700 
        WHEN 'OSIE' THEN 29901
        WHEN 'LUGR' THEN 2169
        ELSE sys::integer
      END;
      IF srid<>900913 THEN
        SELECT INTO sref_metadata spatial_systems.srid, treat_srid_as_x_y_metres FROM spatial_systems WHERE code=lower(sref_system);
        geom = st_transform(st_centroid(geom_in), sref_metadata.srid);
      ELSE
        geom = st_centroid(geom_in);
      END IF;
      -- need to reduce this to a square on the grid
      x = floor(st_xmin(geom)::NUMERIC / precisionM) * precisionM;
      y = floor(st_ymin(geom)::NUMERIC / precisionM) * precisionM;
      r = st_geomfromtext('polygon((' || x::varchar || ' ' || y::varchar || ',' || (x + precisionM)::varchar || ' ' || y::varchar || ','
          || (x + precisionM)::varchar || ' ' || (y + precisionM)::varchar || ',' || x::varchar || ' ' || (y + precisionM)::varchar || ','
          || x::varchar || ' ' || y::varchar || '))', srid);
      IF srid<>900913 THEN
        r = st_transform(r, 900913);
      END IF;
    END IF;
  ELSE
    r = geom_in;
  END IF;
RETURN r;
END;
$BODY$
  LANGUAGE plpgsql VOLATILE
  COST 100;
