-- #slow script#

CREATE INDEX fki_location_media_created_by_id ON location_media(created_by_id);

CREATE INDEX fki_location_media_updated_by_id ON location_media(updated_by_id);

CREATE INDEX fki_location_attribute_values_created_by_id ON location_attribute_values(created_by_id);

CREATE INDEX fki_location_attribute_values_updated_by_id ON location_attribute_values(updated_by_id);

CREATE INDEX fki_locations_created_by_id ON locations(created_by_id);

CREATE INDEX fki_locations_updated_by_id ON locations(updated_by_id);

CREATE INDEX fki_terms_created_by_id ON terms(created_by_id);

CREATE INDEX fki_terms_updated_by_id ON terms(updated_by_id);

CREATE INDEX fki_termlists_terms_created_by_id ON termlists_terms(created_by_id);

CREATE INDEX fki_termlists_terms_updated_by_id ON termlists_terms(updated_by_id);

CREATE INDEX fki_occurrence_media_created_by_id ON occurrence_media(created_by_id);

CREATE INDEX fki_occurrence_media_updated_by_id ON occurrence_media(updated_by_id);

CREATE INDEX fki_occurrence_attribute_values_created_by_id ON occurrence_attribute_values(created_by_id);

CREATE INDEX fki_occurrence_attribute_values_updated_by_id ON occurrence_attribute_values(updated_by_id);

CREATE INDEX fki_occurrences_created_by_id ON occurrences(created_by_id);

CREATE INDEX fki_occurrences_updated_by_id ON occurrences(updated_by_id);

CREATE INDEX fki_sample_media_created_by_id ON sample_media(created_by_id);

CREATE INDEX fki_sample_media_updated_by_id ON sample_media(updated_by_id);

CREATE INDEX fki_sample_attribute_values_created_by_id ON sample_attribute_values(created_by_id);

CREATE INDEX fki_sample_attribute_values_updated_by_id ON sample_attribute_values(updated_by_id);

CREATE INDEX fki_samples_updated_by_id ON samples(updated_by_id);

CREATE INDEX fki_filters_users_created_by_id ON filters_users(created_by_id);

CREATE INDEX fki_filters_users_user_id ON filters_users(user_id);

CREATE INDEX fki_filters_created_by_id ON filters(created_by_id);

CREATE INDEX fki_filters_updated_by_id ON filters(updated_by_id);

CREATE INDEX fki_group_pages_created_by_id ON group_pages(created_by_id);

CREATE INDEX fki_group_pages_updated_by_id ON group_pages(updated_by_id);

CREATE INDEX fki_groups_users_created_by_id ON groups_users(created_by_id);

CREATE INDEX fki_groups_users_updated_by_id ON groups_users(updated_by_id);

CREATE INDEX fki_groups_created_by_id ON groups(created_by_id);

CREATE INDEX fki_groups_updated_by_id ON groups(updated_by_id);