ALTER TABLE occurrence_comments ADD COLUMN correspondance_data text;
COMMENT ON COLUMN occurrence_comments.correspondance_data IS
  'Stores correspondance data related to the comment in JSON format. Typically this will be the sender, recipient and body of an email or the URL of a social media post.';

ALTER TABLE occurrence_comments ADD COLUMN reference text;
COMMENT ON COLUMN occurrence_comments.reference IS
  'Description of reference used, link to web address, journal or book name etc.';

