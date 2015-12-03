ALTER TABLE users_websites ADD COLUMN licence_id integer;
COMMENT ON COLUMN users_websites.licence_id IS 'ID of the licence that is granted for records entered into this website by this user.';

ALTER TABLE users_websites
  ADD CONSTRAINT fk_users_website_licence FOREIGN KEY (licence_id) REFERENCES licences (id)
   ON UPDATE NO ACTION ON DELETE NO ACTION;
COMMENT ON CONSTRAINT fk_users_website_licence ON users_websites
  IS 'Constrains the licence chosen by a user to the available list.';
CREATE INDEX fki_sample_licence
  ON users_websites(licence_id);

CREATE INDEX fki_users_websites_user
  ON users_websites(user_id);
CREATE INDEX fki_users_websites_website
  ON users_websites(website_id);