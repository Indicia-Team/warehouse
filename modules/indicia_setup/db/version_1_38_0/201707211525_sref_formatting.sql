-- #slow script#

UPDATE samples
SET entered_sref=UPPER(REPLACE(entered_sref, ' ', ''))
WHERE entered_sref <> UPPER(REPLACE(entered_sref, ' ', ''))
AND UPPER(entered_sref_system) IN ('OSGB', 'OSIE');
