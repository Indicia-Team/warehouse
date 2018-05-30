ALTER TABLE users_websites ADD COLUMN media_licence_id integer;
COMMENT ON COLUMN users_websites.licence_id IS 'ID of the licence that is granted for media added this website by this user.';

ALTER TABLE users_websites
  ADD CONSTRAINT fk_users_website__media_licence FOREIGN KEY (media_licence_id) REFERENCES licences (id)
   ON UPDATE NO ACTION ON DELETE NO ACTION;
COMMENT ON CONSTRAINT fk_users_website__media_licence ON users_websites
  IS 'Constrains the licence chosen by a user for media to the available list.';
CREATE INDEX fki_users_websites_media_licence
  ON users_websites(media_licence_id);