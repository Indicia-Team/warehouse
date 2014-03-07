  CREATE INDEX fki_occurrence_images_occurrence
  ON occurrence_images
  USING btree
  (occurrence_id);

  CREATE INDEX fki_occurrence_comments_occurrence
  ON occurrence_comments
  USING btree
  (occurrence_id);

  CREATE INDEX fki_locations_websites_location
  ON locations_websites
  USING btree
  (location_id);

  CREATE INDEX fki_locations_websites_website
  ON locations_websites
  USING btree
  (website_id);

  CREATE INDEX fki_occurrences_website
  ON occurrences
  USING btree
  (website_id);	

  CREATE INDEX ix_samples_geom ON samples USING GIST ( geom );

  CREATE INDEX ix_locations_boundary_geom ON locations USING GIST ( boundary_geom );

  CREATE INDEX ix_locations_centroid_geom ON locations USING GIST ( centroid_geom );