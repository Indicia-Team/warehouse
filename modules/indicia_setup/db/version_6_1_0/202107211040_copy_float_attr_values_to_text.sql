-- #slow script#

-- Updates all existing float attribute values so that the text_value is
-- populated with the text of the float_value or the float_value range if
-- there is an upper_value.

-- This is to enable the entered float values to be stored and recovered
-- without truncation of trailing zeroes or loss of accuracy.

UPDATE location_attribute_values
SET text_value = float_value::text || 
CASE
  WHEN upper_value IS NULL THEN ''
  ELSE ' - ' || upper_value::text
END
WHERE
  float_value IS NOT NULL AND
  text_value IS NULL;

UPDATE occurrence_attribute_values
SET text_value = float_value::text || 
CASE
  WHEN upper_value IS NULL THEN ''
  ELSE ' - ' || upper_value::text
END
WHERE
  float_value IS NOT NULL AND
  text_value IS NULL;

UPDATE person_attribute_values
SET text_value = float_value::text || 
CASE
  WHEN upper_value IS NULL THEN ''
  ELSE ' - ' || upper_value::text
END
WHERE
  float_value IS NOT NULL AND
  text_value IS NULL;

UPDATE sample_attribute_values
SET text_value = float_value::text || 
CASE
  WHEN upper_value IS NULL THEN ''
  ELSE ' - ' || upper_value::text
END
WHERE
  float_value IS NOT NULL AND
  text_value IS NULL;

UPDATE survey_attribute_values
SET text_value = float_value::text || 
CASE
  WHEN upper_value IS NULL THEN ''
  ELSE ' - ' || upper_value::text
END
WHERE
  float_value IS NOT NULL AND
  text_value IS NULL;

UPDATE taxa_taxon_list_attribute_values
SET text_value = float_value::text || 
CASE
  WHEN upper_value IS NULL THEN ''
  ELSE ' - ' || upper_value::text
END
WHERE
  float_value IS NOT NULL AND
  text_value IS NULL;
