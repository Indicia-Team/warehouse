ALTER TABLE websites
ADD COLUMN staging_urls character varying[];

COMMENT ON COLUMN websites.staging_urls IS 'List of URLS used for development and/or testing which can also link to this website via the REST API.';