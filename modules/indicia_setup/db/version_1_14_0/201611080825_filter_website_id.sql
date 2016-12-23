ALTER TABLE filters ADD COLUMN website_id integer;
COMMENT ON COLUMN filters.website_id IS 'Foreign key to the websites table. Optionally limits the filter to being available on this website.';

ALTER TABLE filters
  ADD CONSTRAINT fk_filter_website FOREIGN KEY (website_id)
      REFERENCES websites (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION;