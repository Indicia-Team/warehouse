ALTER TABLE sample_media
  ADD COLUMN exif character varying;
COMMENT ON COLUMN sample_media.exif IS 'Exif data read from photo file if extracted. This contains information about the original photo such as the date and time it was taken.';


ALTER TABLE occurrence_media
  ADD COLUMN exif character varying;
COMMENT ON COLUMN occurrence_media.exif IS 'Exif data read from photo file if extracted. This contains information about the original photo such as the date and time it was taken.';


ALTER TABLE location_media
  ADD COLUMN exif character varying;
COMMENT ON COLUMN location_media.exif IS 'Exif data read from photo file if extracted. This contains information about the original photo such as the date and time it was taken.';


ALTER TABLE taxon_media
  ADD COLUMN exif character varying;
COMMENT ON COLUMN taxon_media.exif IS 'Exif data read from photo file if extracted. This contains information about the original photo such as the date and time it was taken.';

