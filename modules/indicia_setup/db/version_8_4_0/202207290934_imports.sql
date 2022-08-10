ALTER TABLE imports
  ADD COLUMN website_id integer,
  ADD CONSTRAINT fk_imports_website FOREIGN KEY (website_id)
      REFERENCES websites (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION;

CREATE INDEX fki_imports_created_by_id ON imports(created_by_id);
CREATE INDEX fki_imports_website_id ON imports(website_id);