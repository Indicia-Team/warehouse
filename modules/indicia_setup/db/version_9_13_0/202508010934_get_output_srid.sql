CREATE OR REPLACE FUNCTION get_output_srid(
    geom_in geometry)
  RETURNS integer AS
$BODY$
DECLARE geom GEOMETRY;
DECLARE origCentroid GEOMETRY;
DECLARE output_srid varchar;
DECLARE origSridX float;
DECLARE origSridY float;
DECLARE newSridX float;
DECLARE newSridY float;
BEGIN
  output_srid = null;
  origCentroid = st_centroid(geom_in);
  origSridX = st_x(origCentroid);
  origSridY = st_y(origCentroid);

  -- Look for some preferred projections to see if we are in range.
  -- First, rough check for OSIE
  IF (origSridX BETWEEN -1196000 AND -599200) AND (origSridY BETWEEN 6687800 AND 7442470) THEN
    geom = st_transform(origCentroid, 29903);
    newSridX = st_x(geom);
    newSridY = st_y(geom);
    -- Exact check for OSIE. Cut out top right corner.
    IF (newSridX BETWEEN 10000 AND 367300) AND (newSridY BETWEEN 10000 AND 468100)
      AND (newSridX<332000 OR newSridY<445900) THEN
      output_srid = 29903; -- Irish Grid
    END IF;
  END IF;
  -- Rough check for OSGB.
  IF (output_srid IS NULL) AND (origSridX BETWEEN -1081873 AND 422933) AND (origSridY BETWEEN 6405988 AND 8944478) THEN
    geom = st_transform(origCentroid, 27700);
    -- Exact check for OSGB
    IF st_x(geom) BETWEEN 0 AND 700000 AND st_y(geom) BETWEEN 0 AND 14000000 THEN
      output_srid = 27700; -- British National Grid
    END IF;
  END IF;
  -- Rough check for Luxembourg
  IF (output_srid IS NULL) AND (origSridX BETWEEN 634030 AND 729730) AND (origSridY BETWEEN 6348260 AND 6484930) THEN
    geom = st_transform(origCentroid, 2169);
    -- Exact check for Luxembourg
    IF (st_x(geom) BETWEEN 46000 AND 108000) AND (st_y(geom) BETWEEN 55000 AND 141000) THEN
      output_srid = 2169; -- Gauss-Luxembourg
    END IF;
  END IF;
  -- Rough check for Channel Islands
  IF (output_srid IS NULL) AND (origSridX BETWEEN -337000 AND -203000) AND (origSridY BETWEEN 6253000 AND 6437000) THEN
    geom = st_transform(origCentroid, 23030);
    newSridX = st_x(geom);
    newSridY = st_y(geom);
    -- Exact checkes for each Island
    IF (newSridX BETWEEN 540000 AND 585000) AND (newSridY BETWEEN 5435000 AND 5465000) OR -- exact check for Jersey area
      (newSridX BETWEEN 515000 AND 555000) AND (newSridY BETWEEN 5465000 AND 5490000) OR -- Guernsey area
      (newSridX BETWEEN 530000 AND 565000) AND (newSridY BETWEEN 5495000 AND 5515000) THEN -- Alderney area
      output_srid = 23030; -- Channel Islands utm30ed50
    END IF;
  END IF;
  IF (output_srid IS NULL) THEN
    -- Calculate UTM zone EPSG code.
    geom = st_transform(origCentroid, 4326);
    output_srid = 32700-round((45+st_y(geom))/90)*100+round((183+st_x(geom))/6);
  END IF;
  RETURN output_srid;
END;
$BODY$
  LANGUAGE plpgsql IMMUTABLE
  COST 90;