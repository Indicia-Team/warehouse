ALTER TABLE website_agreements 
  ADD COLUMN provide_for_editing character(1) DEFAULT 'D'::bpchar,
  ADD COLUMN receive_for_editing character(1) DEFAULT 'D'::bpchar;
UPDATE website_agreements SET provide_for_editing='D', receive_for_editing='D';
ALTER TABLE website_agreements 
  ALTER COLUMN provide_for_editing SET NOT NULL,
  ALTER COLUMN receive_for_editing SET NOT NULL;
COMMENT ON COLUMN website_agreements.provide_for_editing IS 
  'Requirements of a signed up website with regards to providing data to other websites for editing. Possible values are D = disallowed, O=optional, A=optional, but only available to warehouse admins or the agreement creator, R=required. ';
COMMENT ON COLUMN website_agreements.receive_for_editing IS 
  'Requirements of a signed up website with regards to receiving data from other websites for editing. Possible values are D = disallowed, O=optional, A=optional, but only available to warehouse admins or the agreement creator, R=required. ';

ALTER TABLE websites_website_agreements 
  ADD COLUMN provide_for_editing boolean DEFAULT false,
  ADD COLUMN receive_for_editing boolean DEFAULT false;
UPDATE websites_website_agreements SET provide_for_editing=false, receive_for_editing=false;
ALTER TABLE websites_website_agreements 
  ALTER COLUMN provide_for_editing SET NOT NULL,
  ALTER COLUMN receive_for_editing SET NOT NULL;
COMMENT ON COLUMN websites_website_agreements.provide_for_editing IS 
  'Does the website provide data for editing by other agreement participants?';
COMMENT ON COLUMN websites_website_agreements.receive_for_editing IS 
  'Does the website receive data for editing from other agreement participants?';

ALTER TABLE index_websites_website_agreements
  ADD COLUMN provide_for_editing boolean,
  ADD COLUMN receive_for_editing boolean;
UPDATE index_websites_website_agreements SET provide_for_editing=false, receive_for_editing=false;
ALTER TABLE index_websites_website_agreements 
  ALTER COLUMN provide_for_editing SET NOT NULL,
  ALTER COLUMN receive_for_editing SET NOT NULL;
COMMENT ON COLUMN index_websites_website_agreements.provide_for_editing IS 
  'Does the participating website provide data that can be edited on the other?';
COMMENT ON COLUMN index_websites_website_agreements.receive_for_editing IS 
  'Does the participating website received data that can be edited from the other?';