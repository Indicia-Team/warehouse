CREATE OR REPLACE FUNCTION reduce_precision(
    geom_in geometry,
    confidential boolean,
    reduce_to_precision integer)
  RETURNS geometry AS
$BODY$
DECLARE geom geometry;
DECLARE geomltln geometry;
DECLARE r geometry;
DECLARE precisionM integer;
DECLARE x float;
DECLARE y float;
DECLARE srid integer;
DECLARE sref_metadata record;
BEGIN
  IF confidential = true OR COALESCE(reduce_to_precision, 0)>1 THEN
    precisionM = CASE
      WHEN COALESCE(reduce_to_precision, 0)>1 THEN reduce_to_precision
      ELSE 1000
    END;
    srid = get_output_srid(geom_in);
    IF srid<>900913 THEN
      geom = st_transform(geom_in, srid);
    ELSE
      geom = geom_in;
    END IF;
    -- If lower precision than requested, then can return as it is.
    -- If same precision allowing for rounding errors, then re-calculate to ensure
    -- all square geoms are consistent.
    IF floor(sqrt(st_area(geom))) > precisionM THEN
      r = geom_in;
    ELSE
      geom = st_centroid(geom);
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