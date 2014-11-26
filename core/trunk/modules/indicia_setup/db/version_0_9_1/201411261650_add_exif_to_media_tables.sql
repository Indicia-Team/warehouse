CREATE OR REPLACE function f_add_ddl (OUT success bool)
    LANGUAGE plpgsql AS
$func$
BEGIN 
  
success := TRUE;

BEGIN
	ALTER TABLE sample_media
    ADD COLUMN exif character varying;

EXCEPTION
    WHEN duplicate_column THEN 
      RAISE NOTICE 'column exists.';
      success := FALSE;
END;

BEGIN
	ALTER TABLE occurrence_media
    ADD COLUMN exif character varying;

EXCEPTION
    WHEN duplicate_column THEN 
      RAISE NOTICE 'column exists.';
      success := FALSE;
END;

BEGIN
	ALTER TABLE location_media
    ADD COLUMN exif character varying;

EXCEPTION
    WHEN duplicate_column THEN 
      RAISE NOTICE 'column exists.';
      success := FALSE;
END;

BEGIN
	ALTER TABLE taxon_media
    ADD COLUMN exif character varying;

EXCEPTION
    WHEN duplicate_column THEN 
      RAISE NOTICE 'column exists.';
      success := FALSE;
END;

BEGIN
	ALTER TABLE survey_media
    ADD COLUMN exif character varying;

EXCEPTION
    WHEN duplicate_column THEN 
      RAISE NOTICE 'column exists.';
      success := FALSE;
END;

END
$func$;

SELECT f_add_ddl();

DROP FUNCTION f_add_ddl();

COMMENT ON COLUMN sample_media.exif IS 'Exif data read from photo file if extracted. This contains information about the original photo such as the date and time it was taken.';
COMMENT ON COLUMN occurrence_media.exif IS 'Exif data read from photo file if extracted. This contains information about the original photo such as the date and time it was taken.';
COMMENT ON COLUMN location_media.exif IS 'Exif data read from photo file if extracted. This contains information about the original photo such as the date and time it was taken.';
COMMENT ON COLUMN taxon_media.exif IS 'Exif data read from photo file if extracted. This contains information about the original photo such as the date and time it was taken.';
COMMENT ON COLUMN survey_media.exif IS 'Exif data read from photo file if extracted. This contains information about the original photo such as the date and time it was taken.';
