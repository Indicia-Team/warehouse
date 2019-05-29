-- Remove extraneous hyphen.
COMMENT ON COLUMN licences.code IS 'Abbreviation/code for the licence, e.g. CC BY';
UPDATE licences SET code=replace(code, 'CC-', 'CC ')
WHERE code LIKE 'CC-%';