ALTER TABLE samples ADD parent_id INT;

COMMENT ON COLUMN samples.parent_id IS 'In cases where sampling data is gathered in a hierarchical fashion, this allows samples to be linked to a parent sample. For example, a sample linear transect may have several quadrat samples taken along it''s length.';