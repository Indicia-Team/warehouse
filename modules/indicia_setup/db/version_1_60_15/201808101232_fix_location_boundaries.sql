-- #slow script#
UPDATE locations SET boundary_geom=
  CASE
    WHEN ST_GeometryType(ST_CollectionHomogenize(ST_MakeValid(boundary_geom))) = 'ST_GeometryCollection' THEN ST_Buffer(ST_MakeValid(boundary_geom), 0.00001, 'quad_segs=2')
    ELSE ST_CollectionHomogenize(ST_MakeValid(boundary_geom))
  END
WHERE ST_GeometryType(boundary_geom)='ST_GeometryCollection';