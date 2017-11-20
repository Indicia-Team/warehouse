ALTER TABLE occurrence_comments ADD COLUMN correspondence_data text;
COMMENT ON COLUMN occurrence_comments.correspondence_data IS
  'Stores correspondence data related to the comment in JSON format. Typically this will be the sender, recipient and body of an email or the URL of a social media post.';

ALTER TABLE occurrence_comments ADD COLUMN reference text;
COMMENT ON COLUMN occurrence_comments.reference IS
  'Description of reference used, link to web address, journal or book name etc.';

