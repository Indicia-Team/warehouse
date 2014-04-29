CREATE OR REPLACE function f_add_species_alerts (OUT success bool)
    LANGUAGE plpgsql AS
$func$
BEGIN 
  
success := FALSE;

BEGIN
  CREATE TABLE species_alerts
  (
  id serial NOT NULL,
  user_id integer NOT NULL, 
  alert_on_entry boolean NOT NULL DEFAULT false,
  alert_on_verify boolean NOT NULL DEFAULT false, 
  location_id integer NOT NULL,
  website_id integer NOT NULL,
  external_key character varying(50),
  taxon_meaning_id integer,
  created_on timestamp without time zone NOT NULL,
  created_by_id integer NOT NULL, 
  updated_on timestamp without time zone NOT NULL,
  updated_by_id integer NOT NULL,
  deleted boolean NOT NULL DEFAULT false,

  CONSTRAINT pk_species_alerts PRIMARY KEY (id),
  CONSTRAINT fk_species_alerts_user FOREIGN KEY (user_id)
        REFERENCES users (id) MATCH SIMPLE
        ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_species_alerts_location FOREIGN KEY (location_id)
        REFERENCES locations (id) MATCH SIMPLE
        ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_species_alerts_website FOREIGN KEY (website_id)
        REFERENCES websites (id) MATCH SIMPLE
        ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_species_alerts_creator FOREIGN KEY (created_by_id)
        REFERENCES users (id) MATCH SIMPLE
        ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_species_alerts_updater FOREIGN KEY (updated_by_id)
        REFERENCES users (id) MATCH SIMPLE
        ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT taxon_meaning_or_external_key 
	CHECK (taxon_meaning_id IS NOT NULL or external_key IS NOT NULL)
  );
  success := TRUE;
EXCEPTION
    WHEN duplicate_table THEN RAISE NOTICE 'table exists.';

COMMENT ON TABLE species_alerts
  IS 'Holds the data required for the system to know what species alerts should be created for new or verified records e.g. location_id holds a link to the boundary to limit the alerts to, taxon_meaning_id or external_key limits the alerts per taxon.';
COMMENT ON COLUMN species_alerts.user_id IS 'User for whom this alert type applies.';
COMMENT ON COLUMN species_alerts.alert_on_entry IS 'Is the alert generated when the record is created?.';
COMMENT ON COLUMN species_alerts.alert_on_verify IS 'Is the alert generated when the record is verified?.';
COMMENT ON COLUMN species_alerts.location_id IS 'The location associated with this species_alert. Foreign key to the locations table.';
COMMENT ON COLUMN species_alerts.website_id IS 'The website associated with this species_alert. Foreign key to the websites table.';
COMMENT ON COLUMN species_alerts.external_key IS 'The taxon external_key to issue species alerts for.';
COMMENT ON COLUMN species_alerts.taxon_meaning_id IS 'The taxa meaning to issue species alerts for.';
COMMENT ON COLUMN species_alerts.created_on IS 'Date this record was created.';
COMMENT ON COLUMN species_alerts.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN species_alerts.updated_on IS 'Date this record was last updated.';
COMMENT ON COLUMN species_alerts.updated_by_id IS 'Foreign key to the users table (last updater).';
COMMENT ON COLUMN species_alerts.deleted IS 'Has this record been deleted?';

ALTER TABLE notifications DROP CONSTRAINT chk_notification_source_type;

ALTER TABLE notifications
ADD CONSTRAINT chk_notification_source_type CHECK (source_type::text = 'T'::bpchar::text OR source_type::text = 'V'::bpchar::text OR source_type::text = 'C'::bpchar::text OR source_type::text = 'S'::bpchar::text);

COMMENT ON COLUMN notifications.source_type IS 'Defines the type of source of this notification, as described in the source. Value can be T (= trigger), C (= comment), V (= verification), S (= species alert).';
END;

END;
$func$;

SELECT f_add_species_alerts();

DROP FUNCTION f_add_species_alerts();