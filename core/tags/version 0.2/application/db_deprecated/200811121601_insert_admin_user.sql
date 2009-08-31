ALTER TABLE users DROP CONSTRAINT fk_user_person;

INSERT INTO users (person_id, created_on, created_by_id, updated_on, updated_by_id)
VALUES (1, now(), 1, now(), 1);

INSERT INTO people (surname, first_name, created_on, created_by_id, updated_on, updated_by_id)
VALUES ('admin', 'core', now(), 1, now(), 1);

ALTER TABLE users
  ADD CONSTRAINT fk_user_person FOREIGN KEY (person_id)
      REFERENCES people (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION;
