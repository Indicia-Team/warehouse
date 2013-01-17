
CREATE TABLE websites_website_agreements
(
  id serial NOT NULL, -- Unique identifier for website_agreements table.
  website_id integer NOT NULL,
  website_agreement_id integer NOT NULL,
  provide_for_reporting boolean NOT NULL DEFAULT false::boolean, 
  receive_for_reporting boolean NOT NULL DEFAULT false::boolean,
  provide_for_peer_review boolean NOT NULL DEFAULT false::boolean,
  receive_for_peer_review boolean NOT NULL DEFAULT false::boolean,
  provide_for_verification boolean NOT NULL DEFAULT false::boolean,
  receive_for_verification boolean NOT NULL DEFAULT false::boolean,
  provide_for_data_flow boolean NOT NULL DEFAULT false::boolean,
  receive_for_data_flow boolean NOT NULL DEFAULT false::boolean,
  provide_for_moderation boolean NOT NULL DEFAULT false::boolean,
  receive_for_moderation boolean NOT NULL DEFAULT false::boolean,
  created_on timestamp without time zone NOT NULL, -- Date this record was created.
  created_by_id integer NOT NULL, -- Foreign key to the users table (creator).
  updated_on timestamp without time zone NOT NULL, -- Date this record was last updated.
  updated_by_id integer NOT NULL, -- Foreign key to the users table (last updater).
  deleted boolean NOT NULL DEFAULT false, -- Has this record been deleted?
  CONSTRAINT fk_website_website_agreement_website FOREIGN KEY (website_id)
      REFERENCES websites (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_website_website_agreement_website_agreement FOREIGN KEY (website_agreement_id)
      REFERENCES website_agreements (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_website_website_agreement_creator FOREIGN KEY (created_by_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_website_website_agreement_updater FOREIGN KEY (updated_by_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT pk_websites_website_agreements PRIMARY KEY (id)
)
WITH (
  OIDS=FALSE
);

COMMENT ON TABLE websites_website_agreements IS 'Agreements that a website participates in, including information on the nature of their participation.';
COMMENT ON COLUMN websites_website_agreements.id IS 'Unique identifier for websites_website_agreements table.';
COMMENT ON COLUMN websites_website_agreements.website_id IS 'Identifies the website participating in the agreement. Foreign key to the websites table..';
COMMENT ON COLUMN websites_website_agreements.website_agreement_id IS 'Identifies the website agreement being participated in. Foreign key to the website agreements table.';
COMMENT ON COLUMN websites_website_agreements.provide_for_reporting IS 'Does the website provide data for reporting by other agreement participants?';
COMMENT ON COLUMN websites_website_agreements.receive_for_reporting IS 'Does the website receive data for reporting from other agreement participants?';
COMMENT ON COLUMN websites_website_agreements.provide_for_peer_review IS 'Does the website provide data for peer review by other agreement participants?';
COMMENT ON COLUMN websites_website_agreements.receive_for_peer_review IS 'Does the website receive data for peer review from other agreement participants?';
COMMENT ON COLUMN websites_website_agreements.provide_for_verification IS 'Does the website provide data for verification by other agreement participants?';
COMMENT ON COLUMN websites_website_agreements.receive_for_verification IS 'Does the website receive data for verification from other agreement participants?';
COMMENT ON COLUMN websites_website_agreements.provide_for_data_flow IS 'Does the website provide data for data flow by other agreement participants?';
COMMENT ON COLUMN websites_website_agreements.receive_for_data_flow IS 'Does the website receive data for data flow from other agreement participants?';
COMMENT ON COLUMN websites_website_agreements.provide_for_moderation IS 'Does the website provide data for moderation by other agreement participants?';
COMMENT ON COLUMN websites_website_agreements.receive_for_moderation IS 'Does the website receive data for moderation from other agreement participants?';
COMMENT ON COLUMN websites_website_agreements.created_on IS 'Date this record was created.';
COMMENT ON COLUMN websites_website_agreements.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN websites_website_agreements.updated_on IS 'Date this record was last updated.';
COMMENT ON COLUMN websites_website_agreements.updated_by_id IS 'Foreign key to the users table (last updater).';
COMMENT ON COLUMN websites_website_agreements.deleted IS 'Has this record been deleted?';
