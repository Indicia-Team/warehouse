CREATE OR REPLACE function f_add_user_email_notification_settings (OUT success bool)
    LANGUAGE plpgsql AS
$func$
BEGIN 
  
success := FALSE;

BEGIN
COMMENT ON COLUMN notifications.source_type IS 'Defines the type of source of this notification, as described in the source. Value can be T (= trigger), C (= comment), V (= verification), A (= automated record check), S (= species alert), VT (= verifier task), AC (= achievement), M (= milestone). When constraint is altered, update user_email_notification_settings.notification_source_type to match.';
END;

BEGIN
  CREATE TABLE user_email_notification_settings
  (
  id serial NOT NULL,
  user_id integer NOT NULL,
  notification_source_type character varying(2) NOT NULL,
  notification_frequency character varying(2) NOT NULL,
  created_on timestamp without time zone NOT NULL,
  created_by_id integer NOT NULL, 
  updated_on timestamp without time zone NOT NULL,
  updated_by_id integer NOT NULL,
  deleted boolean NOT NULL DEFAULT false,


  CONSTRAINT pk_user_email_notification_settings PRIMARY KEY (id),
  CONSTRAINT fk_user_email_notification_settings_user FOREIGN KEY (user_id)
        REFERENCES users (id) MATCH SIMPLE
        ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT chk_notification_source_type 
    CHECK (notification_source_type::text = 'T'::bpchar::text OR notification_source_type ::text = 'V'::bpchar::text OR notification_source_type::text = 'C'::bpchar::text OR notification_source_type::text = 'A'::bpchar::text OR notification_source_type::text = 'S'::bpchar::text OR notification_source_type::text = 'VT'::bpchar::text OR notification_source_type::text = 'AC'::bpchar::text OR notification_source_type::text = 'M'::bpchar::text),
  CONSTRAINT chk_notification_frequency 
    CHECK (notification_frequency::text = 'IH'::bpchar::text OR notification_frequency::text = 'D'::bpchar::text OR notification_frequency::text = 'W'::bpchar::text),
  CONSTRAINT fk_user_email_notification_settings_creator FOREIGN KEY (created_by_id)
        REFERENCES users (id) MATCH SIMPLE
        ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_user_email_notification_settings FOREIGN KEY (updated_by_id)
        REFERENCES users (id) MATCH SIMPLE
        ON UPDATE NO ACTION ON DELETE NO ACTION
  );
  success := TRUE;
EXCEPTION
    WHEN duplicate_table THEN RAISE NOTICE 'table exists.';

COMMENT ON TABLE user_email_notification_settings
  IS 'Contains data relating to how often emails are sent to users with information about new notifications';
COMMENT ON COLUMN user_email_notification_settings.user_id IS 'User the frequency setting applies to.';
COMMENT ON COLUMN user_email_notification_settings.notification_source_type IS 'The notification type the setting relates to, as described in the notification Source Type. Value can be T (= trigger), C (= comment), V (= verification), A (= automated record check), S (= species alert), VT (= verifier task), AC (= achievement), M (= milestone). Needs updating when notification.source_type constraint is altered.';
COMMENT ON COLUMN user_email_notification_settings.notification_frequency IS 'Defines the frequency of the emails. Value can be IH (= immediate/hourly), D (= daily), W (= weekly)';
COMMENT ON COLUMN user_email_notification_settings.created_on IS 'Date this record was created.';
COMMENT ON COLUMN user_email_notification_settings.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN user_email_notification_settings.updated_on IS 'Date this record was last updated.';
COMMENT ON COLUMN user_email_notification_settings.updated_by_id IS 'Foreign key to the users table (last updater).';
COMMENT ON COLUMN user_email_notification_settings.deleted IS 'Has this record been deleted?';

END;

BEGIN
  CREATE TABLE user_email_notification_frequency_last_runs
  (
    id serial NOT NULL,
    notification_frequency character varying(2) NOT NULL,
    last_max_notification_id integer NULL,
    last_run_date timestamp without time zone NULL,

    CONSTRAINT pk_user_email_notification_frequency_last_runs PRIMARY KEY (id),
    CONSTRAINT chk_notification_frequency 
      CHECK (notification_frequency::text = 'IH'::bpchar::text OR notification_frequency::text = 'D'::bpchar::text OR notification_frequency::text = 'W'::bpchar::text),
    CONSTRAINT fk_last_max_notification_id FOREIGN KEY (last_max_notification_id)
      REFERENCES notifications (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION
    );
  success := TRUE;
EXCEPTION
    WHEN duplicate_table THEN RAISE NOTICE 'table exists.';

  COMMENT ON TABLE user_email_notification_frequency_last_runs
      IS 'Holds the date the notifications emails were sent out for different settings. This allows us to work out when the emails need sending again';
  COMMENT ON COLUMN user_email_notification_frequency_last_runs.notification_frequency IS 'Defines the frequency of the emails. Value can be IH (= immediate/hourly), D (= daily), W (= weekly)';
  COMMENT ON COLUMN user_email_notification_frequency_last_runs.last_max_notification_id IS 'The latest notification ID that has its emails sent for users with a particular email notification frequency.';
  COMMENT ON COLUMN user_email_notification_frequency_last_runs.last_run_date IS 'Date the emails were last sent out for that frequency.';

END;

BEGIN
  INSERT INTO user_email_notification_frequency_last_runs (notification_frequency)
  values ('IH'),
         ('D'),
         ('W');
END;

BEGIN
  CREATE OR REPLACE VIEW list_user_email_notification_settings AS 
    SELECT u.id, u.user_id, u.notification_source_type,u.notification_frequency
      FROM user_email_notification_settings u
    WHERE u.deleted = false;
END;

END;
$func$;

SELECT f_add_user_email_notification_settings();

DROP FUNCTION f_add_user_email_notification_settings();