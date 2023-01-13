-- Ensure title/status combination unique per user.
UPDATE verification_templates u
SET title = u.title || ' - ' || u.id::text
FROM (
  SELECT created_by_id, title, template_statuses
  FROM verification_templates
  GROUP BY created_by_id, title, template_statuses
  HAVING count(*)>1
) AS dup
WHERE dup.template_statuses=u.template_statuses
AND dup.created_by_id=u.created_by_id
AND dup.title=u.title;

-- Now we can safely add a unique index.
CREATE UNIQUE INDEX IF NOT EXISTS ix_verificaton_templates_unique
  ON verification_templates(template_statuses, created_by_id, title) WHERE deleted='f';