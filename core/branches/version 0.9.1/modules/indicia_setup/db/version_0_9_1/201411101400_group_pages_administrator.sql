ALTER TABLE group_pages 
ALTER administrator DROP NOT NULL;

COMMENT ON COLUMN group_pages.administrator IS 'Set to true for pages that require group admin rights to be able to see them, false for pages that require normal membership and null for pages that are accessible to all.';