-- #slow script#
 
UPDATE samples
SET entered_sref=UPPER(REPLACE(entered_sref, ' ', ''))
WHERE entered_sref <> UPPER(REPLACE(entered_sref, ' ', ''))
AND UPPER(entered_sref_system) IN ('OSGB', 'OSIE');

UPDATE cache_samples_nonfunctional
SET public_entered_sref=UPPER(REPLACE(public_entered_sref, ' ', ''))
WHERE public_entered_sref <> UPPER(REPLACE(public_entered_sref, ' ', ''))
AND UPPER(entered_sref_system) IN ('OSGB', 'OSIE');

UPDATE cache_occurrences_nonfunctional
SET output_sref=UPPER(REPLACE(output_sref, ' ', ''))
WHERE output_sref <> UPPER(REPLACE(output_sref, ' ', ''))
AND UPPER(output_sref_system) IN ('OSGB', 'OSIE');