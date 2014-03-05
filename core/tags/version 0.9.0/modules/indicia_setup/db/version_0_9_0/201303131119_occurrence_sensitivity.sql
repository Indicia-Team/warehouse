CREATE OR REPLACE function f_add_ddl (OUT success bool)
    LANGUAGE plpgsql AS
$func$
BEGIN 
  
success := TRUE;

BEGIN
  ALTER TABLE occurrences
    ADD COLUMN sensitivity_precision integer;
EXCEPTION
    WHEN duplicate_column THEN 
      RAISE NOTICE 'column exists.';
      success := FALSE;
END;

BEGIN
  CREATE TABLE spatial_systems
  (
    id serial NOT NULL, -- Unique identifier for the spatial system.
    title character varying NOT NULL, -- Untranslated title of the spatial reference system.
    code character varying (20) NOT NULL, -- Spatial reference system code.
    srid integer NOT NULL, -- Underlying SRID used for the system.
    treat_srid_as_x_y_metres boolean NOT NULL, -- Should the underlying projection be used as an x, y grid system in metres, e.g. when reducing the precision of a sensitive record?
    CONSTRAINT pk_spatial_systems PRIMARY KEY (id )
  )
  WITH (
    OIDS=FALSE
  );
EXCEPTION
    WHEN duplicate_table THEN 
      RAISE NOTICE 'table exists.';
      success := FALSE;
END;

COMMENT ON COLUMN occurrences.training IS 'Flag indicating if this record was created for training purposes and is therefore not considered real.';

END
$func$;

SELECT f_add_ddl();

DROP FUNCTION f_add_ddl();

COMMENT ON COLUMN occurrences.sensitivity_precision IS 'Precision of grid references for public access of records that are sensitive. For example, set to 1000 to limit public access to a 1km grid square. If null then not sensitive.';

COMMENT ON COLUMN occurrences.confidential IS 'Flag set to true if this record is confidential. Deprecated, use sensitivity_precision instead.';

CREATE OR REPLACE VIEW detail_occurrences AS 
 SELECT o.id, o.confidential, o.comment, o.taxa_taxon_list_id, ttl.taxon_meaning_id, o.record_status, o.determiner_id, t.taxon, 
 s.entered_sref, s.entered_sref_system, s.geom, st_astext(s.geom) AS wkt, s.location_name, s.survey_id, s.date_start, s.date_end, s.date_type, s.location_id, l.name AS location, 
 l.code AS location_code, s.recorder_names, (d.first_name::text || ' '::text) || d.surname::text AS determiner, o.website_id, o.external_key, 
 o.created_by_id, c.username AS created_by, o.created_on, o.updated_by_id, u.username AS updated_by, o.updated_on, o.downloaded_flag, o.sample_id, o.deleted, 
 o.zero_abundance, t.external_key AS taxon_external_key, ttl.taxon_list_id, o.sensitivity_precision
   FROM occurrences o
   JOIN samples s ON s.id = o.sample_id AND s.deleted = false
   LEFT JOIN people d ON d.id = o.determiner_id AND d.deleted = false
   LEFT JOIN locations l ON l.id = s.location_id AND l.deleted = false
   LEFT JOIN taxa_taxon_lists ttl ON ttl.id = o.taxa_taxon_list_id AND ttl.deleted = false
   LEFT JOIN taxa t ON t.id = ttl.taxon_id AND t.deleted = false
   LEFT JOIN surveys su ON s.survey_id = su.id AND su.deleted = false
   JOIN users c ON c.id = o.created_by_id
   JOIN users u ON u.id = o.updated_by_id
  WHERE o.deleted = false;


COMMENT ON TABLE spatial_systems
  IS 'A list of the spatial reference systems supported by plugins in the Indicia warehouse. This table is automatically populated during the upgrade process.';
COMMENT ON COLUMN spatial_systems.id IS 'Unique identifier for the spatial system.';
COMMENT ON COLUMN spatial_systems.title IS 'Untranslated title of the spatial reference system.';
COMMENT ON COLUMN spatial_systems.code IS 'Spatial reference system code.';
COMMENT ON COLUMN spatial_systems.srid IS 'Underlying SRID used for the system. ';
COMMENT ON COLUMN spatial_systems.treat_srid_as_x_y_metres IS 'Should the underlying projection be used as an x, y grid system in metres, e.g. when reducing the precision of a sensitive record?';

DROP FUNCTION IF EXISTS reduce_precision(geometry, boolean, integer, varchar (20));

CREATE OR REPLACE FUNCTION reduce_precision(geom_in geometry, confidential boolean, sensitivity_precision integer, sref_system varchar (20))
  RETURNS geometry AS
$BODY$
DECLARE geom geometry;
DECLARE r geometry;
DECLARE precisionM integer;
DECLARE x float;
DECLARE y float;
DECLARE sref_metadata record;
DECLARE current_srid integer;
BEGIN
  -- Copy geom_in as values cannot be assigned to parameters in postgres <= 8.4
  geom = geom_in;
  IF confidential = true OR sensitivity_precision IS NOT NULL THEN
    precisionM = CASE
      WHEN sensitivity_precision IS NOT NULL THEN sensitivity_precision
      ELSE 1000
    END;
    -- If already low precision, then can return as it is
    IF sqrt(st_area(geom)) >= sensitivity_precision THEN
      r = geom;
    ELSE
      SELECT INTO sref_metadata srid, treat_srid_as_x_y_metres FROM spatial_systems WHERE code=lower(sref_system);
      IF COALESCE(sref_metadata.treat_srid_as_x_y_metres, false) THEN
        geom = st_transform(geom, sref_metadata.srid);
        current_srid = sref_metadata.srid;
      ELSE
        current_srid = 900913;
      END IF;
      -- need to reduce this to a square on the grid
      x = floor(st_xmin(geom)::NUMERIC / precisionM) * precisionM;
      y = floor(st_ymin(geom)::NUMERIC / precisionM) * precisionM;
      r = st_geomfromtext('polygon((' || x::varchar || ' ' || y::varchar || ',' || (x + precisionM)::varchar || ' ' || y::varchar || ','
       || (x + precisionM)::varchar || ' ' || (y + precisionM)::varchar || ',' || x::varchar || ' ' || (y + precisionM)::varchar || ','
       || x::varchar || ' ' || y::varchar || '))', current_srid);
      IF COALESCE(sref_metadata.treat_srid_as_x_y_metres, false) THEN
        r = st_transform(r, 900913);
      END IF;
    END IF;
  ELSE
    r = geom;
  END IF;
RETURN r;
END;
$BODY$
  LANGUAGE plpgsql VOLATILE
  COST 100;