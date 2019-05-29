-- #slow script#
-- Remove extraneous hyphen.
UPDATE cache_occurrences_nonfunctional
SET licence_code=replace(licence_code, 'CC-', 'CC ')
WHERE licence_code LIKE 'CC-%';