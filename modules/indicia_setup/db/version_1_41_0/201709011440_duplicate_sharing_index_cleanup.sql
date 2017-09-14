-- one-off, removes duplication in index_websites_website_agreements caused by
-- https://github.com/Indicia-Team/warehouse/issues/262.
DELETE FROM index_websites_website_agreements
WHERE id IN (
  SELECT id
    FROM (
      SELECT id, ROW_NUMBER() OVER (partition BY from_website_id, to_website_id ORDER BY id) AS rnum
      FROM index_websites_website_agreements
    ) t
  WHERE t.rnum > 1
);

SELECT refresh_index_websites_website_agreements();