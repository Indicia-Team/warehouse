ALTER TABLE users_websites DROP CONSTRAINT fk_users_websites_users;

ALTER TABLE users_websites
  ADD CONSTRAINT fk_users_websites_users FOREIGN KEY (user_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION;