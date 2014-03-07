
CREATE TABLE website_agreements
(
  id serial NOT NULL, -- Unique identifier for website_agreements table.
  title character varying(100) NOT NULL, -- Title of the website agreement.
  description character varying, -- Optional description of the website agreement.
  public boolean NOT NULL DEFAULT false, -- Is the agreement available for any website to sign up to? If not only the website agreement creator and administrators can add websites to the agreement.
  provide_for_reporting character(1) NOT NULL DEFAULT 'D'::bpchar, -- Requirements of a signed up website with regards to receiving data from other websites for moderation. Possible values are D = disallowed, O=optional, A=optional, but only available to warehouse admins or the agreement creator, R=required.
  receive_for_reporting character(1) NOT NULL DEFAULT 'D'::bpchar,
  provide_for_peer_review character(1) NOT NULL DEFAULT 'D'::bpchar,
  receive_for_peer_review character(1) NOT NULL DEFAULT 'D'::bpchar,
  provide_for_verification character(1) NOT NULL DEFAULT 'D'::bpchar,
  receive_for_verification character(1) NOT NULL DEFAULT 'D'::bpchar,
  provide_for_data_flow character(1) NOT NULL DEFAULT 'D'::bpchar,
  receive_for_data_flow character(1) NOT NULL DEFAULT 'D'::bpchar,
  provide_for_moderation character(1) NOT NULL DEFAULT 'D'::bpchar,
  receive_for_moderation character(1) NOT NULL DEFAULT 'D'::bpchar,
  created_on timestamp without time zone NOT NULL, -- Date this record was created.
  created_by_id integer NOT NULL, -- Foreign key to the users table (creator).
  updated_on timestamp without time zone NOT NULL, -- Date this record was last updated.
  updated_by_id integer NOT NULL, -- Foreign key to the users table (last updater).
  deleted boolean NOT NULL DEFAULT false, -- Has this record been deleted?
  CONSTRAINT fk_website_agreenent_creator FOREIGN KEY (created_by_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_website_agreement_updater FOREIGN KEY (updated_by_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT pk_website_agreements PRIMARY KEY (id),
  CONSTRAINT chk_website_agreement_options CHECK ((provide_for_reporting = ANY (ARRAY['D'::bpchar, 'O'::bpchar, 'A'::bpchar, 'R'::bpchar])) AND (receive_for_reporting = ANY (ARRAY['D'::bpchar, 'O'::bpchar, 'A'::bpchar, 'R'::bpchar])) AND (provide_for_peer_review = ANY (ARRAY['D'::bpchar, 'O'::bpchar, 'A'::bpchar, 'R'::bpchar])) AND (receive_for_peer_review = ANY (ARRAY['D'::bpchar, 'O'::bpchar, 'A'::bpchar, 'R'::bpchar])) AND (provide_for_verification = ANY (ARRAY['D'::bpchar, 'O'::bpchar, 'A'::bpchar, 'R'::bpchar])) AND (receive_for_verification = ANY (ARRAY['D'::bpchar, 'O'::bpchar, 'A'::bpchar, 'R'::bpchar])) AND (provide_for_data_flow = ANY (ARRAY['D'::bpchar, 'O'::bpchar, 'A'::bpchar, 'R'::bpchar])) AND (receive_for_data_flow = ANY (ARRAY['D'::bpchar, 'O'::bpchar, 'A'::bpchar, 'R'::bpchar])) AND (provide_for_moderation = ANY (ARRAY['D'::bpchar, 'O'::bpchar, 'A'::bpchar, 'R'::bpchar])) AND (receive_for_moderation = ANY (ARRAY['D'::bpchar, 'O'::bpchar, 'A'::bpchar, 'R'::bpchar]))) -- Checks that the various options for website agreements all have one of the possible settings.
)
WITH (
  OIDS=FALSE
);

COMMENT ON TABLE website_agreements IS 'A list of agreements which websites can sign up to for sharing of data for reporting, verification and other purposes.';
COMMENT ON COLUMN website_agreements.id IS 'Unique identifier for website_agreements table.';
COMMENT ON COLUMN website_agreements.title IS 'Title of the website agreement.';
COMMENT ON COLUMN website_agreements.description IS 'Optional description of the website agreement.';
COMMENT ON COLUMN website_agreements.public IS 'Is the agreement available for any website to sign up to? If not only the website agreement creator and administrators can add websites to the agreement.';
COMMENT ON COLUMN website_agreements.provide_for_reporting IS 'Requirements of a signed up website with regards to providing data to other websites for reporting. Possible values are D = disallowed, O=optional, A=optional, but only available to warehouse admins or the agreement creator, R=required. ';
COMMENT ON COLUMN website_agreements.receive_for_reporting IS 'Requirements of a signed up website with regards to receiving data from other websites for reporting. Possible values are D = disallowed, O=optional, A=optional, but only available to warehouse admins or the agreement creator, R=required. ';
COMMENT ON COLUMN website_agreements.provide_for_peer_review IS 'Requirements of a signed up website with regards to providing data to other websites for peer review. Possible values are D = disallowed, O=optional, A=optional, but only available to warehouse admins or the agreement creator, R=required. ';
COMMENT ON COLUMN website_agreements.receive_for_peer_review IS 'Requirements of a signed up website with regards to receiving data from other websites for peer review. Possible values are D = disallowed, O=optional, A=optional, but only available to warehouse admins or the agreement creator, R=required. ';
COMMENT ON COLUMN website_agreements.provide_for_verification IS 'Requirements of a signed up website with regards to providing data to other websites for verfication. Possible values are D = disallowed, O=optional, A=optional, but only available to warehouse admins or the agreement creator, R=required. ';
COMMENT ON COLUMN website_agreements.receive_for_verification IS 'Requirements of a signed up website with regards to receiving data from other websites for verification. Possible values are D = disallowed, O=optional, A=optional, but only available to warehouse admins or the agreement creator, R=required. ';
COMMENT ON COLUMN website_agreements.provide_for_data_flow IS 'Requirements of a signed up website with regards to providing data to other websites for data flow. Possible values are D = disallowed, O=optional, A=optional, but only available to warehouse admins or the agreement creator, R=required. ';
COMMENT ON COLUMN website_agreements.receive_for_data_flow IS 'Requirements of a signed up website with regards to receiving data from other websites for data flow. Possible values are D = disallowed, O=optional, A=optional, but only available to warehouse admins or the agreement creator, R=required. ';
COMMENT ON COLUMN website_agreements.provide_for_moderation IS 'Requirements of a signed up website with regards to providing data to other websites for moderation. Possible values are D = disallowed, O=optional, A=optional, but only available to warehouse admins or the agreement creator, R=required. ';
COMMENT ON COLUMN website_agreements.receive_for_moderation IS 'Requirements of a signed up website with regards to receiving data from other websites for moderation. Possible values are D = disallowed, O=optional, A=optional, but only available to warehouse admins or the agreement creator, R=required. ';
COMMENT ON COLUMN website_agreements.created_on IS 'Date this record was created.';
COMMENT ON COLUMN website_agreements.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN website_agreements.updated_on IS 'Date this record was last updated.';
COMMENT ON COLUMN website_agreements.updated_by_id IS 'Foreign key to the users table (last updater).';
COMMENT ON COLUMN website_agreements.deleted IS 'Has this record been deleted?';

COMMENT ON CONSTRAINT chk_website_agreement_options ON website_agreements IS 'Checks that the various options for website agreements all have one of the possible settings.';

