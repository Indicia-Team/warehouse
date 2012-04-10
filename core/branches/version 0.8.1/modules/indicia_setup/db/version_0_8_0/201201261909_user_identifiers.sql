CREATE TABLE user_identifiers
(
  id serial NOT NULL, -- Unique identifier for the user_identifiers table.
  identifier character varying(300) NOT NULL, -- One of the user's identifiers, e.g. their Twitter ID or Open ID URL.
  type_id integer, -- Foreign key to the termlists_terms table. Identifies the term describing the identifier type.
  user_id integer, -- Foreign key to the users table. Identifies the user that owns this identifier.
  created_on timestamp without time zone NOT NULL, -- Date this record was created.
  created_by_id integer NOT NULL, -- Foreign key to the users table (creator).
  updated_on timestamp without time zone NOT NULL, -- Date this record was last updated.
  updated_by_id integer NOT NULL, -- Foreign key to the users table (last updater).
  deleted boolean NOT NULL DEFAULT false, -- Set to true if the record is mark deleted.
  CONSTRAINT pk_user_identifiers PRIMARY KEY (id),
  CONSTRAINT fk_user_identifiers_type_termlists_terms FOREIGN KEY (type_id)
      REFERENCES termlists_terms (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_user_identifiers_creator FOREIGN KEY (created_by_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_user_identifiers_updater FOREIGN KEY (updated_by_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_user_identifiers_user FOREIGN KEY (user_id) 
      REFERENCES users (id)
      ON UPDATE NO ACTION ON DELETE NO ACTION
)
WITH (
  OIDS=FALSE
);

COMMENT ON COLUMN user_identifiers.id IS 'Unique identifier for the user_identifiers table.';
COMMENT ON COLUMN user_identifiers.identifier IS 'One of the user''s identifiers, e.g. their Twitter ID or Open ID URL.';
COMMENT ON COLUMN user_identifiers.type_id IS 'Foreign key to the termlists_terms table. Identifies the term describing the identifier type.';
COMMENT ON COLUMN user_identifiers.user_id IS 'Foreign key to the users table. Identifies the user that owns this identifier.';
COMMENT ON COLUMN user_identifiers.created_on IS 'Date this record was created.';
COMMENT ON COLUMN user_identifiers.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN user_identifiers.updated_on IS 'Date this record was last updated.';
COMMENT ON COLUMN user_identifiers.updated_by_id IS 'Foreign key to the users table (last updater).';
COMMENT ON COLUMN user_identifiers.deleted IS 'Set to true if the record is mark deleted.';


CREATE INDEX fki_user_identifiers_type_termlists_terms
  ON user_identifiers
  USING btree
  (type_id);
  
CREATE INDEX fki_user_identifiers_user ON user_identifiers(user_id);

INSERT INTO termlists (title, description, created_on, created_by_id, updated_on, updated_by_id, external_key)
VALUES ('User Identifier Types', 'Types of user identifier, such as twitter and openid.', now(), 1, now(), 1, 'indicia:user_identifier_types');

SELECT insert_term('openid', 'eng', null, 'indicia:user_identifier_types');
SELECT insert_term('facebook', 'eng', null, 'indicia:user_identifier_types');
SELECT insert_term('twitter', 'eng', null, 'indicia:user_identifier_types');
SELECT insert_term('email', 'eng', null, 'indicia:user_identifier_types');

