CREATE TABLE website_email_notification_settings (
  id serial NOT NULL,
  website_id integer NOT NULL,
  notification_source_type character varying(2) NOT NULL,
  notification_frequency character varying(2) NOT NULL,
  created_on timestamp without time zone NOT NULL,
  created_by_id integer NOT NULL,
  updated_on timestamp without time zone NOT NULL,
  updated_by_id integer NOT NULL,
  deleted boolean NOT NULL DEFAULT false,
  CONSTRAINT pk_website_email_notification_settings PRIMARY KEY (id),
  CONSTRAINT fk_website_email_notification_settings_website FOREIGN KEY (website_id)
        REFERENCES websites (id) MATCH SIMPLE
        ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT chk_notification_source_type
    CHECK (notification_source_type = 'T' OR notification_source_type = 'V'  OR notification_source_type = 'C' OR notification_source_type = 'Q' OR notification_source_type = 'A' OR notification_source_type = 'S' OR notification_source_type = 'VT' OR notification_source_type = 'M' OR notification_source_type = 'PT'),
  CONSTRAINT chk_notification_frequency
    CHECK (notification_frequency = 'IH' OR notification_frequency = 'D' OR notification_frequency = 'W'),
  CONSTRAINT fk_website_email_notification_settings_creator FOREIGN KEY (created_by_id)
        REFERENCES users (id) MATCH SIMPLE
        ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_website_email_notification_settings FOREIGN KEY (updated_by_id)
        REFERENCES users (id) MATCH SIMPLE
        ON UPDATE NO ACTION ON DELETE NO ACTION
);

CREATE UNIQUE INDEX ix_website_email_notification_settings_unique
   ON website_email_notification_settings (website_id, notification_source_type)
   WHERE deleted=false;

COMMENT ON TABLE website_email_notification_settings
  IS 'Default email settings for new users added to a website. Defines settings that will be added to user_email_notification_settings.';
COMMENT ON COLUMN user_email_notification_settings.user_id IS 'Website the frequency setting applies to.';
COMMENT ON COLUMN user_email_notification_settings.notification_source_type IS 'The notification type the setting relates to, as described in the notification Source Type. Value can be T (= trigger), C (= comment), Q (= query), V (= verification), A (= automated record check), S (= species alert), VT (= verifier task), M (= milestone), PT (= pending record task). Needs updating when notification.source_type constraint is altered.';
COMMENT ON COLUMN user_email_notification_settings.notification_frequency IS 'Defines the frequency of the emails. Value can be IH (= immediate/hourly), D (= daily), W (= weekly)';
COMMENT ON COLUMN user_email_notification_settings.created_on IS 'Date this record was created.';
COMMENT ON COLUMN user_email_notification_settings.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN user_email_notification_settings.updated_on IS 'Date this record was last updated.';
COMMENT ON COLUMN user_email_notification_settings.updated_by_id IS 'Foreign key to the users table (last updater).';
COMMENT ON COLUMN user_email_notification_settings.deleted IS 'Has this record been deleted?';