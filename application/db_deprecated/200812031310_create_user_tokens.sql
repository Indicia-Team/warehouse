DROP TABLE IF EXISTS user_tokens;
CREATE TABLE user_tokens (
  id serial NOT NULL,
  user_id integer NOT NULL,
  expires timestamp without time zone NOT NULL,
  created timestamp without time zone NOT NULL,
  user_agent character varying NOT NULL,
  token character varying NOT NULL,
  CONSTRAINT pk_user_tokens PRIMARY KEY (id),
  CONSTRAINT fk_user_tokens_user FOREIGN KEY (user_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION
)
WITH (OIDS=FALSE);

ALTER TABLE user_tokens OWNER TO postgres;

COMMENT ON COLUMN user_tokens.user_id IS 'User who to whom this token belongs. Foreign key to the users table';
COMMENT ON COLUMN user_tokens.expires IS 'Date and time this token was expires.';
COMMENT ON COLUMN user_tokens.created IS 'Date and time this token was created.';
COMMENT ON COLUMN user_tokens.user_agent IS 'Hash of User agent details';
COMMENT ON COLUMN user_tokens.token IS 'Value of token stored in cookie';