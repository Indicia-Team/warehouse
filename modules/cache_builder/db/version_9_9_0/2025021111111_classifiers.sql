ALTER TABLE cache_occurrences_functional
ADD COLUMN classifier_agreement BOOLEAN;

COMMENT ON COLUMN cache_occurrences_functional.classifier_agreement IS 'True if the current determination matches the one chosen as most likely by an image classifier, '
  'false if a classifier was used but the classifier suggested a different determination as most likely, or null if a classifier was not used';