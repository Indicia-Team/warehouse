ALTER TABLE core_roles
ADD COLUMN created_on timestamp NOT NULL, --Date this core_role was created
ADD COLUMN created_by_id integer NOT NULL, --Foreign key to the users table (creator)
ADD COLUMN updated_on timestamp NOT NULL, --Date this core_role was last updated
ADD COLUMN updated_by_id integer NOT NULL, --Foreign key to the users table (last updater)
ADD CONSTRAINT fk_core_role_creator FOREIGN KEY (created_by_id)
	REFERENCES users (id) MATCH SIMPLE
	ON UPDATE NO ACTION ON DELETE NO ACTION,
ADD CONSTRAINT fk_core_role_updater FOREIGN KEY (updated_by_id)
	REFERENCES users (id) MATCH SIMPLE
	ON UPDATE NO ACTION ON DELETE NO ACTION;

COMMENT ON COLUMN core_roles.created_on IS 'Date this record was created.';
COMMENT ON COLUMN core_roles.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN core_roles.updated_on IS 'Date this record was last updated.';
COMMENT ON COLUMN core_roles.updated_by_id IS 'Foreign key to the users table (last updater).';


ALTER TABLE languages
ADD COLUMN created_on timestamp NOT NULL, --Date this language was created
ADD COLUMN created_by_id integer NOT NULL, --Foreign key to the users table (creator)
ADD COLUMN updated_on timestamp NOT NULL, --Date this language was last updated
ADD COLUMN updated_by_id integer NOT NULL, --Foreign key to the users table (last updater)
ADD CONSTRAINT fk_language_creator FOREIGN KEY (created_by_id)
	REFERENCES users (id) MATCH SIMPLE
	ON UPDATE NO ACTION ON DELETE NO ACTION,
ADD CONSTRAINT fk_language_updater FOREIGN KEY (updated_by_id)
	REFERENCES users (id) MATCH SIMPLE
	ON UPDATE NO ACTION ON DELETE NO ACTION;

COMMENT ON COLUMN languages.created_on IS 'Date this record was created.';
COMMENT ON COLUMN languages.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN languages.updated_on IS 'Date this record was last updated.';
COMMENT ON COLUMN languages.updated_by_id IS 'Foreign key to the users table (last updater).';


ALTER TABLE location_attribute_values
ADD COLUMN created_on timestamp NOT NULL, --Date this location_attribute_value was created
ADD COLUMN created_by_id integer NOT NULL, --Foreign key to the users table (creator)
ADD COLUMN updated_on timestamp NOT NULL, --Date this location_attribute_value was last updated
ADD COLUMN updated_by_id integer NOT NULL, --Foreign key to the users table (last updater)
ADD CONSTRAINT fk_location_attribute_value_creator FOREIGN KEY (created_by_id)
	REFERENCES users (id) MATCH SIMPLE
	ON UPDATE NO ACTION ON DELETE NO ACTION,
ADD CONSTRAINT fk_location_attribute_value_updater FOREIGN KEY (updated_by_id)
	REFERENCES users (id) MATCH SIMPLE
	ON UPDATE NO ACTION ON DELETE NO ACTION;

COMMENT ON COLUMN location_attribute_values.created_on IS 'Date this record was created.';
COMMENT ON COLUMN location_attribute_values.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN location_attribute_values.updated_on IS 'Date this record was last updated.';
COMMENT ON COLUMN location_attribute_values.updated_by_id IS 'Foreign key to the users table (last updater).';



ALTER TABLE location_attributes
ADD COLUMN created_on timestamp NOT NULL, --Date this location_attribute was created
ADD COLUMN created_by_id integer NOT NULL, --Foreign key to the users table (creator)
ADD COLUMN updated_on timestamp NOT NULL, --Date this location_attribute was last updated
ADD COLUMN updated_by_id integer NOT NULL, --Foreign key to the users table (last updater)
ADD CONSTRAINT fk_location_attribute_creator FOREIGN KEY (created_by_id)
	REFERENCES users (id) MATCH SIMPLE
	ON UPDATE NO ACTION ON DELETE NO ACTION,
ADD CONSTRAINT fk_location_attribute_updater FOREIGN KEY (updated_by_id)
	REFERENCES users (id) MATCH SIMPLE
	ON UPDATE NO ACTION ON DELETE NO ACTION;

COMMENT ON COLUMN location_attributes.created_on IS 'Date this record was created.';
COMMENT ON COLUMN location_attributes.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN location_attributes.updated_on IS 'Date this record was last updated.';
COMMENT ON COLUMN location_attributes.updated_by_id IS 'Foreign key to the users table (last updater).';


ALTER TABLE location_attributes_websites
ADD COLUMN created_on timestamp NOT NULL, --Date this location_attributes_website was created
ADD COLUMN created_by_id integer NOT NULL, --Foreign key to the users table (creator)
ADD CONSTRAINT fk_location_attributes_website_creator FOREIGN KEY (created_by_id)
	REFERENCES users (id) MATCH SIMPLE
	ON UPDATE NO ACTION ON DELETE NO ACTION;

COMMENT ON COLUMN location_attributes_websites.created_on IS 'Date this record was created.';
COMMENT ON COLUMN location_attributes_websites.created_by_id IS 'Foreign key to the users table (creator).';



ALTER TABLE locations
ADD COLUMN created_on timestamp NOT NULL, --Date this location was created
ADD COLUMN created_by_id integer NOT NULL, --Foreign key to the users table (creator)
ADD COLUMN updated_on timestamp NOT NULL, --Date this location was last updated
ADD COLUMN updated_by_id integer NOT NULL, --Foreign key to the users table (last updater)
ADD CONSTRAINT fk_location_creator FOREIGN KEY (created_by_id)
	REFERENCES users (id) MATCH SIMPLE
	ON UPDATE NO ACTION ON DELETE NO ACTION,
ADD CONSTRAINT fk_location_updater FOREIGN KEY (updated_by_id)
	REFERENCES users (id) MATCH SIMPLE
	ON UPDATE NO ACTION ON DELETE NO ACTION;

COMMENT ON COLUMN locations.created_on IS 'Date this record was created.';
COMMENT ON COLUMN locations.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN locations.updated_on IS 'Date this record was last updated.';
COMMENT ON COLUMN locations.updated_by_id IS 'Foreign key to the users table (last updater).';



ALTER TABLE locations_websites
ADD COLUMN created_on timestamp NOT NULL, --Date this locations_website was created
ADD COLUMN created_by_id integer NOT NULL, --Foreign key to the users table (creator)
ADD CONSTRAINT fk_locations_website_creator FOREIGN KEY (created_by_id)
	REFERENCES users (id) MATCH SIMPLE
	ON UPDATE NO ACTION ON DELETE NO ACTION;

COMMENT ON COLUMN locations_websites.created_on IS 'Date this record was created.';
COMMENT ON COLUMN locations_websites.created_by_id IS 'Foreign key to the users table (creator).';




ALTER TABLE occurrence_attribute_values
ADD COLUMN created_on timestamp NOT NULL, --Date this occurrence_attribute_value was created
ADD COLUMN created_by_id integer NOT NULL, --Foreign key to the users table (creator)
ADD COLUMN updated_on timestamp NOT NULL, --Date this occurrence_attribute_value was last updated
ADD COLUMN updated_by_id integer NOT NULL, --Foreign key to the users table (last updater)
ADD CONSTRAINT fk_occurrence_attribute_value_creator FOREIGN KEY (created_by_id)
	REFERENCES users (id) MATCH SIMPLE
	ON UPDATE NO ACTION ON DELETE NO ACTION,
ADD CONSTRAINT fk_occurrence_attribute_value_updater FOREIGN KEY (updated_by_id)
	REFERENCES users (id) MATCH SIMPLE
	ON UPDATE NO ACTION ON DELETE NO ACTION;

COMMENT ON COLUMN occurrence_attribute_values.created_on IS 'Date this record was created.';
COMMENT ON COLUMN occurrence_attribute_values.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN occurrence_attribute_values.updated_on IS 'Date this record was last updated.';
COMMENT ON COLUMN occurrence_attribute_values.updated_by_id IS 'Foreign key to the users table (last updater).';




ALTER TABLE occurrence_attributes
ADD COLUMN created_on timestamp NOT NULL, --Date this occurrence_attribute was created
ADD COLUMN created_by_id integer NOT NULL, --Foreign key to the users table (creator)
ADD COLUMN updated_on timestamp NOT NULL, --Date this occurrence_attribute was last updated
ADD COLUMN updated_by_id integer NOT NULL, --Foreign key to the users table (last updater)
ADD CONSTRAINT fk_occurrence_attribute_creator FOREIGN KEY (created_by_id)
	REFERENCES users (id) MATCH SIMPLE
	ON UPDATE NO ACTION ON DELETE NO ACTION,
ADD CONSTRAINT fk_occurrence_attribute_updater FOREIGN KEY (updated_by_id)
	REFERENCES users (id) MATCH SIMPLE
	ON UPDATE NO ACTION ON DELETE NO ACTION;

COMMENT ON COLUMN occurrence_attributes.created_on IS 'Date this record was created.';
COMMENT ON COLUMN occurrence_attributes.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN occurrence_attributes.updated_on IS 'Date this record was last updated.';
COMMENT ON COLUMN occurrence_attributes.updated_by_id IS 'Foreign key to the users table (last updater).';




ALTER TABLE occurrence_attributes_websites
ADD COLUMN created_on timestamp NOT NULL, --Date this occurrence_attributes_website was created
ADD COLUMN created_by_id integer NOT NULL, --Foreign key to the users table (creator)
ADD CONSTRAINT fk_occurrence_attributes_website_creator FOREIGN KEY (created_by_id)
	REFERENCES users (id) MATCH SIMPLE
	ON UPDATE NO ACTION ON DELETE NO ACTION;

COMMENT ON COLUMN occurrence_attributes_websites.created_on IS 'Date this record was created.';
COMMENT ON COLUMN occurrence_attributes_websites.created_by_id IS 'Foreign key to the users table (creator).';



ALTER TABLE occurrence_images
ADD COLUMN created_on timestamp NOT NULL, --Date this occurrence_image was created
ADD COLUMN created_by_id integer NOT NULL, --Foreign key to the users table (creator)
ADD COLUMN updated_on timestamp NOT NULL, --Date this occurrence_image was last updated
ADD COLUMN updated_by_id integer NOT NULL, --Foreign key to the users table (last updater)
ADD CONSTRAINT fk_occurrence_image_creator FOREIGN KEY (created_by_id)
	REFERENCES users (id) MATCH SIMPLE
	ON UPDATE NO ACTION ON DELETE NO ACTION,
ADD CONSTRAINT fk_occurrence_image_updater FOREIGN KEY (updated_by_id)
	REFERENCES users (id) MATCH SIMPLE
	ON UPDATE NO ACTION ON DELETE NO ACTION;

COMMENT ON COLUMN occurrence_images.created_on IS 'Date this record was created.';
COMMENT ON COLUMN occurrence_images.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN occurrence_images.updated_on IS 'Date this record was last updated.';
COMMENT ON COLUMN occurrence_images.updated_by_id IS 'Foreign key to the users table (last updater).';



ALTER TABLE occurrences
ADD COLUMN created_on timestamp NOT NULL, --Date this occurrence was created
ADD COLUMN created_by_id integer NOT NULL, --Foreign key to the users table (creator)
ADD COLUMN updated_on timestamp NOT NULL, --Date this occurrence was last updated
ADD COLUMN updated_by_id integer NOT NULL, --Foreign key to the users table (last updater)
ADD CONSTRAINT fk_occurrence_creator FOREIGN KEY (created_by_id)
	REFERENCES users (id) MATCH SIMPLE
	ON UPDATE NO ACTION ON DELETE NO ACTION,
ADD CONSTRAINT fk_occurrence_updater FOREIGN KEY (updated_by_id)
	REFERENCES users (id) MATCH SIMPLE
	ON UPDATE NO ACTION ON DELETE NO ACTION;

COMMENT ON COLUMN occurrences.created_on IS 'Date this record was created.';
COMMENT ON COLUMN occurrences.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN occurrences.updated_on IS 'Date this record was last updated.';
COMMENT ON COLUMN occurrences.updated_by_id IS 'Foreign key to the users table (last updater).';



ALTER TABLE people
ADD COLUMN created_on timestamp NOT NULL, --Date this person was created
ADD COLUMN created_by_id integer NOT NULL, --Foreign key to the users table (creator)
ADD COLUMN updated_on timestamp NOT NULL, --Date this person was last updated
ADD COLUMN updated_by_id integer NOT NULL, --Foreign key to the users table (last updater)
ADD CONSTRAINT fk_person_creator FOREIGN KEY (created_by_id)
	REFERENCES users (id) MATCH SIMPLE
	ON UPDATE NO ACTION ON DELETE NO ACTION,
ADD CONSTRAINT fk_person_updater FOREIGN KEY (updated_by_id)
	REFERENCES users (id) MATCH SIMPLE
	ON UPDATE NO ACTION ON DELETE NO ACTION;

COMMENT ON COLUMN people.created_on IS 'Date this record was created.';
COMMENT ON COLUMN people.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN people.updated_on IS 'Date this record was last updated.';
COMMENT ON COLUMN people.updated_by_id IS 'Foreign key to the users table (last updater).';




ALTER TABLE sample_attribute_values
ADD COLUMN created_on timestamp NOT NULL, --Date this sample_attribute_value was created
ADD COLUMN created_by_id integer NOT NULL, --Foreign key to the users table (creator)
ADD COLUMN updated_on timestamp NOT NULL, --Date this sample_attribute_value was last updated
ADD COLUMN updated_by_id integer NOT NULL, --Foreign key to the users table (last updater)
ADD CONSTRAINT fk_sample_attribute_value_creator FOREIGN KEY (created_by_id)
	REFERENCES users (id) MATCH SIMPLE
	ON UPDATE NO ACTION ON DELETE NO ACTION,
ADD CONSTRAINT fk_sample_attribute_value_updater FOREIGN KEY (updated_by_id)
	REFERENCES users (id) MATCH SIMPLE
	ON UPDATE NO ACTION ON DELETE NO ACTION;

COMMENT ON COLUMN sample_attribute_values.created_on IS 'Date this record was created.';
COMMENT ON COLUMN sample_attribute_values.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN sample_attribute_values.updated_on IS 'Date this record was last updated.';
COMMENT ON COLUMN sample_attribute_values.updated_by_id IS 'Foreign key to the users table (last updater).';



ALTER TABLE sample_attributes
ADD COLUMN created_on timestamp NOT NULL, --Date this sample_attribute was created
ADD COLUMN created_by_id integer NOT NULL, --Foreign key to the users table (creator)
ADD COLUMN updated_on timestamp NOT NULL, --Date this sample_attribute was last updated
ADD COLUMN updated_by_id integer NOT NULL, --Foreign key to the users table (last updater)
ADD CONSTRAINT fk_sample_attribute_creator FOREIGN KEY (created_by_id)
	REFERENCES users (id) MATCH SIMPLE
	ON UPDATE NO ACTION ON DELETE NO ACTION,
ADD CONSTRAINT fk_sample_attribute_updater FOREIGN KEY (updated_by_id)
	REFERENCES users (id) MATCH SIMPLE
	ON UPDATE NO ACTION ON DELETE NO ACTION;

COMMENT ON COLUMN sample_attributes.created_on IS 'Date this record was created.';
COMMENT ON COLUMN sample_attributes.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN sample_attributes.updated_on IS 'Date this record was last updated.';
COMMENT ON COLUMN sample_attributes.updated_by_id IS 'Foreign key to the users table (last updater).';



ALTER TABLE sample_attributes_websites
ADD COLUMN created_on timestamp NOT NULL, --Date this sample_attributes_website was created
ADD COLUMN created_by_id integer NOT NULL, --Foreign key to the users table (creator)
ADD CONSTRAINT fk_sample_attributes_website_creator FOREIGN KEY (created_by_id)
	REFERENCES users (id) MATCH SIMPLE
	ON UPDATE NO ACTION ON DELETE NO ACTION;

COMMENT ON COLUMN sample_attributes_websites.created_on IS 'Date this record was created.';
COMMENT ON COLUMN sample_attributes_websites.created_by_id IS 'Foreign key to the users table (creator).';



ALTER TABLE samples
ADD COLUMN created_on timestamp NOT NULL, --Date this sample was created
ADD COLUMN created_by_id integer NOT NULL, --Foreign key to the users table (creator)
ADD COLUMN updated_on timestamp NOT NULL, --Date this sample was last updated
ADD COLUMN updated_by_id integer NOT NULL, --Foreign key to the users table (last updater)
ADD CONSTRAINT fk_sample_creator FOREIGN KEY (created_by_id)
	REFERENCES users (id) MATCH SIMPLE
	ON UPDATE NO ACTION ON DELETE NO ACTION,
ADD CONSTRAINT fk_sample_updater FOREIGN KEY (updated_by_id)
	REFERENCES users (id) MATCH SIMPLE
	ON UPDATE NO ACTION ON DELETE NO ACTION;

COMMENT ON COLUMN samples.created_on IS 'Date this record was created.';
COMMENT ON COLUMN samples.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN samples.updated_on IS 'Date this record was last updated.';
COMMENT ON COLUMN samples.updated_by_id IS 'Foreign key to the users table (last updater).';



ALTER TABLE site_roles
ADD COLUMN created_on timestamp NOT NULL, --Date this site_role was created
ADD COLUMN created_by_id integer NOT NULL, --Foreign key to the users table (creator)
ADD COLUMN updated_on timestamp NOT NULL, --Date this site_role was last updated
ADD COLUMN updated_by_id integer NOT NULL, --Foreign key to the users table (last updater)
ADD CONSTRAINT fk_site_role_creator FOREIGN KEY (created_by_id)
	REFERENCES users (id) MATCH SIMPLE
	ON UPDATE NO ACTION ON DELETE NO ACTION,
ADD CONSTRAINT fk_site_role_updater FOREIGN KEY (updated_by_id)
	REFERENCES users (id) MATCH SIMPLE
	ON UPDATE NO ACTION ON DELETE NO ACTION;

COMMENT ON COLUMN site_roles.created_on IS 'Date this record was created.';
COMMENT ON COLUMN site_roles.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN site_roles.updated_on IS 'Date this record was last updated.';
COMMENT ON COLUMN site_roles.updated_by_id IS 'Foreign key to the users table (last updater).';



ALTER TABLE surveys
ADD COLUMN created_on timestamp NOT NULL, --Date this survey was created
ADD COLUMN created_by_id integer NOT NULL, --Foreign key to the users table (creator)
ADD COLUMN updated_on timestamp NOT NULL, --Date this survey was last updated
ADD COLUMN updated_by_id integer NOT NULL, --Foreign key to the users table (last updater)
ADD CONSTRAINT fk_survey_creator FOREIGN KEY (created_by_id)
	REFERENCES users (id) MATCH SIMPLE
	ON UPDATE NO ACTION ON DELETE NO ACTION,
ADD CONSTRAINT fk_survey_updater FOREIGN KEY (updated_by_id)
	REFERENCES users (id) MATCH SIMPLE
	ON UPDATE NO ACTION ON DELETE NO ACTION;

COMMENT ON COLUMN surveys.created_on IS 'Date this record was created.';
COMMENT ON COLUMN surveys.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN surveys.updated_on IS 'Date this record was last updated.';
COMMENT ON COLUMN surveys.updated_by_id IS 'Foreign key to the users table (last updater).';



ALTER TABLE taxa
ADD COLUMN created_on timestamp NOT NULL, --Date this taxon was created
ADD COLUMN created_by_id integer NOT NULL, --Foreign key to the users table (creator)
ADD COLUMN updated_on timestamp NOT NULL, --Date this taxon was last updated
ADD COLUMN updated_by_id integer NOT NULL, --Foreign key to the users table (last updater)
ADD CONSTRAINT fk_taxon_creator FOREIGN KEY (created_by_id)
	REFERENCES users (id) MATCH SIMPLE
	ON UPDATE NO ACTION ON DELETE NO ACTION,
ADD CONSTRAINT fk_taxon_updater FOREIGN KEY (updated_by_id)
	REFERENCES users (id) MATCH SIMPLE
	ON UPDATE NO ACTION ON DELETE NO ACTION;

COMMENT ON COLUMN taxa.created_on IS 'Date this record was created.';
COMMENT ON COLUMN taxa.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN taxa.updated_on IS 'Date this record was last updated.';
COMMENT ON COLUMN taxa.updated_by_id IS 'Foreign key to the users table (last updater).';



ALTER TABLE taxa_taxon_lists
ADD COLUMN created_on timestamp NOT NULL, --Date this taxa_taxon_list was created
ADD COLUMN created_by_id integer NOT NULL, --Foreign key to the users table (creator)
ADD CONSTRAINT fk_taxa_taxon_list_creator FOREIGN KEY (created_by_id)
	REFERENCES users (id) MATCH SIMPLE
	ON UPDATE NO ACTION ON DELETE NO ACTION;

COMMENT ON COLUMN taxa_taxon_lists.created_on IS 'Date this record was created.';
COMMENT ON COLUMN taxa_taxon_lists.created_by_id IS 'Foreign key to the users table (creator).';



ALTER TABLE taxon_groups
ADD COLUMN created_on timestamp NOT NULL, --Date this taxon_group was created
ADD COLUMN created_by_id integer NOT NULL, --Foreign key to the users table (creator)
ADD COLUMN updated_on timestamp NOT NULL, --Date this taxon_group was last updated
ADD COLUMN updated_by_id integer NOT NULL, --Foreign key to the users table (last updater)
ADD CONSTRAINT fk_taxon_group_creator FOREIGN KEY (created_by_id)
	REFERENCES users (id) MATCH SIMPLE
	ON UPDATE NO ACTION ON DELETE NO ACTION,
ADD CONSTRAINT fk_taxon_group_updater FOREIGN KEY (updated_by_id)
	REFERENCES users (id) MATCH SIMPLE
	ON UPDATE NO ACTION ON DELETE NO ACTION;

COMMENT ON COLUMN taxon_groups.created_on IS 'Date this record was created.';
COMMENT ON COLUMN taxon_groups.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN taxon_groups.updated_on IS 'Date this record was last updated.';
COMMENT ON COLUMN taxon_groups.updated_by_id IS 'Foreign key to the users table (last updater).';



ALTER TABLE taxon_lists
ADD COLUMN created_on timestamp NOT NULL, --Date this taxon_list was created
ADD COLUMN created_by_id integer NOT NULL, --Foreign key to the users table (creator)
ADD COLUMN updated_on timestamp NOT NULL, --Date this taxon_list was last updated
ADD COLUMN updated_by_id integer NOT NULL, --Foreign key to the users table (last updater)
ADD CONSTRAINT fk_taxon_list_creator FOREIGN KEY (created_by_id)
	REFERENCES users (id) MATCH SIMPLE
	ON UPDATE NO ACTION ON DELETE NO ACTION,
ADD CONSTRAINT fk_taxon_list_updater FOREIGN KEY (updated_by_id)
	REFERENCES users (id) MATCH SIMPLE
	ON UPDATE NO ACTION ON DELETE NO ACTION;

COMMENT ON COLUMN taxon_lists.created_on IS 'Date this record was created.';
COMMENT ON COLUMN taxon_lists.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN taxon_lists.updated_on IS 'Date this record was last updated.';
COMMENT ON COLUMN taxon_lists.updated_by_id IS 'Foreign key to the users table (last updater).';




ALTER TABLE termlists
ADD COLUMN created_on timestamp NOT NULL, --Date this termlist was created
ADD COLUMN created_by_id integer NOT NULL, --Foreign key to the users table (creator)
ADD COLUMN updated_on timestamp NOT NULL, --Date this termlist was last updated
ADD COLUMN updated_by_id integer NOT NULL, --Foreign key to the users table (last updater)
ADD CONSTRAINT fk_termlist_creator FOREIGN KEY (created_by_id)
	REFERENCES users (id) MATCH SIMPLE
	ON UPDATE NO ACTION ON DELETE NO ACTION,
ADD CONSTRAINT fk_termlist_updater FOREIGN KEY (updated_by_id)
	REFERENCES users (id) MATCH SIMPLE
	ON UPDATE NO ACTION ON DELETE NO ACTION;

COMMENT ON COLUMN termlists.created_on IS 'Date this record was created.';
COMMENT ON COLUMN termlists.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN termlists.updated_on IS 'Date this record was last updated.';
COMMENT ON COLUMN termlists.updated_by_id IS 'Foreign key to the users table (last updater).';




ALTER TABLE termlists_terms
ADD COLUMN created_on timestamp NOT NULL, --Date this termlists_term was created
ADD COLUMN created_by_id integer NOT NULL, --Foreign key to the users table (creator)
ADD COLUMN updated_on timestamp NOT NULL, --Date this termlists_term was last updated
ADD COLUMN updated_by_id integer NOT NULL, --Foreign key to the users table (last updater)
ADD CONSTRAINT fk_termlists_term_creator FOREIGN KEY (created_by_id)
	REFERENCES users (id) MATCH SIMPLE
	ON UPDATE NO ACTION ON DELETE NO ACTION,
ADD CONSTRAINT fk_termlists_term_updater FOREIGN KEY (updated_by_id)
	REFERENCES users (id) MATCH SIMPLE
	ON UPDATE NO ACTION ON DELETE NO ACTION;

COMMENT ON COLUMN termlists_terms.created_on IS 'Date this record was created.';
COMMENT ON COLUMN termlists_terms.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN termlists_terms.updated_on IS 'Date this record was last updated.';
COMMENT ON COLUMN termlists_terms.updated_by_id IS 'Foreign key to the users table (last updater).';



ALTER TABLE terms
ADD COLUMN created_on timestamp NOT NULL, --Date this term was created
ADD COLUMN created_by_id integer NOT NULL, --Foreign key to the users table (creator)
ADD COLUMN updated_on timestamp NOT NULL, --Date this term was last updated
ADD COLUMN updated_by_id integer NOT NULL, --Foreign key to the users table (last updater)
ADD CONSTRAINT fk_term_creator FOREIGN KEY (created_by_id)
	REFERENCES users (id) MATCH SIMPLE
	ON UPDATE NO ACTION ON DELETE NO ACTION,
ADD CONSTRAINT fk_term_updater FOREIGN KEY (updated_by_id)
	REFERENCES users (id) MATCH SIMPLE
	ON UPDATE NO ACTION ON DELETE NO ACTION;

COMMENT ON COLUMN terms.created_on IS 'Date this record was created.';
COMMENT ON COLUMN terms.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN terms.updated_on IS 'Date this record was last updated.';
COMMENT ON COLUMN terms.updated_by_id IS 'Foreign key to the users table (last updater).';



ALTER TABLE users
ADD COLUMN created_on timestamp NOT NULL, --Date this user was created
ADD COLUMN created_by_id integer NOT NULL, --Foreign key to the users table (creator)
ADD COLUMN updated_on timestamp NOT NULL, --Date this user was last updated
ADD COLUMN updated_by_id integer NOT NULL, --Foreign key to the users table (last updater)
ADD CONSTRAINT fk_user_creator FOREIGN KEY (created_by_id)
	REFERENCES users (id) MATCH SIMPLE
	ON UPDATE NO ACTION ON DELETE NO ACTION,
ADD CONSTRAINT fk_user_updater FOREIGN KEY (updated_by_id)
	REFERENCES users (id) MATCH SIMPLE
	ON UPDATE NO ACTION ON DELETE NO ACTION;

COMMENT ON COLUMN users.created_on IS 'Date this record was created.';
COMMENT ON COLUMN users.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN users.updated_on IS 'Date this record was last updated.';
COMMENT ON COLUMN users.updated_by_id IS 'Foreign key to the users table (last updater).';



ALTER TABLE users_websites
ADD COLUMN created_on timestamp NOT NULL, --Date this users_website was created
ADD COLUMN created_by_id integer NOT NULL, --Foreign key to the users table (creator)
ADD COLUMN updated_on timestamp NOT NULL, --Date this users_website was last updated
ADD COLUMN updated_by_id integer NOT NULL, --Foreign key to the users table (last updater)
ADD CONSTRAINT fk_users_website_creator FOREIGN KEY (created_by_id)
	REFERENCES users (id) MATCH SIMPLE
	ON UPDATE NO ACTION ON DELETE NO ACTION,
ADD CONSTRAINT fk_users_website_updater FOREIGN KEY (updated_by_id)
	REFERENCES users (id) MATCH SIMPLE
	ON UPDATE NO ACTION ON DELETE NO ACTION;

COMMENT ON COLUMN users_websites.created_on IS 'Date this record was created.';
COMMENT ON COLUMN users_websites.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN users_websites.updated_on IS 'Date this record was last updated.';
COMMENT ON COLUMN users_websites.updated_by_id IS 'Foreign key to the users table (last updater).';



ALTER TABLE websites
ADD COLUMN created_on timestamp NOT NULL, --Date this website was created
ADD COLUMN created_by_id integer NOT NULL, --Foreign key to the users table (creator)
ADD COLUMN updated_on timestamp NOT NULL, --Date this website was last updated
ADD COLUMN updated_by_id integer NOT NULL, --Foreign key to the users table (last updater)
ADD CONSTRAINT fk_website_creator FOREIGN KEY (created_by_id)
	REFERENCES users (id) MATCH SIMPLE
	ON UPDATE NO ACTION ON DELETE NO ACTION,
ADD CONSTRAINT fk_website_updater FOREIGN KEY (updated_by_id)
	REFERENCES users (id) MATCH SIMPLE
	ON UPDATE NO ACTION ON DELETE NO ACTION;

COMMENT ON COLUMN websites.created_on IS 'Date this record was created.';
COMMENT ON COLUMN websites.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN websites.updated_on IS 'Date this record was last updated.';
COMMENT ON COLUMN websites.updated_by_id IS 'Foreign key to the users table (last updater).';
