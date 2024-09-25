ALTER TABLE groups
  ADD COLUMN post_blog_permission character,
  ADD CONSTRAINT groups_blog_post_permisison_check CHECK (post_blog_permission::text = ANY (ARRAY[NULL::bpchar, 'A'::bpchar, 'M'::bpchar]::text[]));

COMMENT ON COLUMN groups.post_blog_permission IS 'Requirements for posting blog entries for the group. Can be "A" - group admins can post, "M" - any group member can post, or NULL if blogs are disabled.';