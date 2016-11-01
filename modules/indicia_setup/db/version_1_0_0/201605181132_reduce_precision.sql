-- Function: reduce_precision(geometry, boolean, integer, character varying)

-- DROP FUNCTION reduce_precision(geometry, boolean, integer, character varying);

CREATE OR REPLACE FUNCTION reduce_precision(
    geom_in geometry,
    confidential boolean,
    reduce_to_precision integer,
    sref_system character varying)
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
  IF confidential = true OR COALESCE(reduce_to_precision, 0)>1 THEN
    precisionM = CASE
      WHEN COALESCE(reduce_to_precision, 0)>1 THEN reduce_to_precision
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
        geom = st_transform(st_centroid(geom_in), srid);
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
  LANGUAGE plpgsql IMMUTABLE
  COST 100;