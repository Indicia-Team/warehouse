WITH duplicates AS (
  SELECT website_id, regexp_replace(replace(lower(title), ' ', '-'), '[^a-z0-9-]', '') AS title_url_path
  FROM groups
  GROUP BY 1, 2
  HAVING count(*) > 1
), ranked_titles AS (
  SELECT
    g.id,
	g.website_id,
    g.title,
    ROW_NUMBER() OVER (PARTITION BY g.website_id, regexp_replace(replace(lower(g.title), ' ', '-'), '[^a-z0-9-]', '', 'g') ORDER BY id) AS rn
  FROM groups g
  JOIN duplicates d ON d.website_id=g.website_id
  AND d.title_url_path=regexp_replace(replace(lower(g.title), ' ', '-'), '[^a-z0-9-]', '', 'g')
),
updated_titles AS (
  SELECT
    id,
    title || ' (' || rn || ')' AS new_title
  FROM ranked_titles
)
UPDATE groups AS mt
SET title = ut.new_title
FROM updated_titles AS ut
WHERE mt.id = ut.id AND mt.title <> ut.new_title;

ALTER TABLE groups
ADD COLUMN title_slug TEXT GENERATED ALWAYS AS (
  regexp_replace(replace(lower(title), ' ', '-'), '[^a-z0-9-]', '', 'g')
) STORED;

ALTER TABLE groups
  ADD CONSTRAINT unique_group_title UNIQUE (website_id, title_slug);