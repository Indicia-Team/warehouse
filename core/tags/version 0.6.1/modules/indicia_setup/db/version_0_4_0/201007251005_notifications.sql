-- Table: notifications

-- DROP TABLE notifications;

CREATE TABLE notifications
(
  id serial NOT NULL,
  source character varying(100) NOT NULL, -- Source of the notification, for example the name of the trigger that generated it.
  source_type "char" NOT NULL DEFAULT 'T'::"char", -- Defines the type of source of this notification, as described in the source. Always T (= trigger) currently.
  data character varying NOT NULL, -- Notifiable data. For a trigger, this contains a JSON structure defining content of the notifiable record as output by the trigger report file.
  acknowledged boolean NOT NULL DEFAULT false, -- Has the notification been acknowledged by the user?
  user_id integer NOT NULL, -- Notified user's id. Foreign key to the users table.
  triggered_on timestamp with time zone NOT NULL DEFAULT now(),
  digest_mode character(1), -- Specifies the digest behaviour of this notification. Options are null (use the user's default), N (no email), I (immediate), D (daily), W (weekly).
  CONSTRAINT pk_notifications PRIMARY KEY (id),
  CONSTRAINT fk_notification_user FOREIGN KEY (user_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT chk_notification_digest_mode CHECK (digest_mode IS NULL OR (digest_mode = ANY (ARRAY['N'::bpchar, 'D'::bpchar, 'W'::bpchar, 'I'::bpchar]))),
  CONSTRAINT chk_notification_source_type CHECK (source_type = 'T'::bpchar)
)
WITH (
  OIDS=FALSE
);
COMMENT ON COLUMN notifications.source IS 'Source of the notification, for example the name of the trigger that generated it.';
COMMENT ON COLUMN notifications.source_type IS 'Defines the type of source of this notification, as described in the source. Always T (= trigger) currently.';
COMMENT ON COLUMN notifications.data IS 'Notifiable data. For a trigger, this contains a JSON structure defining content of the notifiable record as output by the trigger report file.';
COMMENT ON COLUMN notifications.acknowledged IS 'Has the notification been acknowledged by the user?';
COMMENT ON COLUMN notifications.user_id IS 'Notified user''s id. Foreign key to the users table.';
COMMENT ON COLUMN notifications.digest_mode IS 'Specifies the digest behaviour of this notification. Options are null (use the user''s default), N (no email), I (immediate), D (daily), W (weekly).';


-- Index: fki_notification_user

-- DROP INDEX fki_notification_user;

CREATE INDEX fki_notification_user
  ON notifications
  USING btree
  (user_id);


ALTER TABLE users
	ADD COLUMN default_digest_mode character(1) NOT NULL DEFAULT 'D';

COMMENT ON COLUMN users.default_digest_mode IS 'Specifies the default digest behaviour of notifications for this user. Options are null N (no email), I (immediate), D (daily), W (weekly).';