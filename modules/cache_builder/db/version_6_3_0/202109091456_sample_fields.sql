ALTER TABLE cache_samples_functional
  ADD COLUMN external_key character varying,
  ADD COLUMN sensitive boolean,
  ADD COLUMN private boolean;

ALTER TABLE cache_samples_nonfunctional
  ADD COLUMN output_sref character varying,
  ADD COLUMN output_sref_system character varying,
  ADD COLUMN verifier character varying;

COMMENT ON COLUMN cache_samples_functional.external_key IS
    'For samples imported from an external system, provides a field to store the external system''s primary key for the record allowing re-synchronisation.';

COMMENT ON COLUMN cache_samples_functional.sensitive IS
    'Set to true if the sample is blurred because one of the contained occurrences is sensitive.';

COMMENT ON COLUMN cache_samples_functional.private IS
    'Set to true if the sample has a privacy_precision value set, indicating the data are blurred for site privacy reasons (e.g. private gardens).';

COMMENT ON COLUMN cache_samples_nonfunctional.output_sref IS
    'A display spatial reference created for all samples, using the most appropriate local grid system where possible.';

COMMENT ON COLUMN cache_samples_nonfunctional.output_sref_system IS
    'Spatial reference system used for the output_sref field.';

COMMENT ON COLUMN cache_samples_nonfunctional.verifier IS
    'Name of the person who verified the sample, if any.';