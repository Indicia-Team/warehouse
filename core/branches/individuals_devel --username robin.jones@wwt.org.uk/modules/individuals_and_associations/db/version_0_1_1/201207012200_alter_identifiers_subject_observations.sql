-- Table: identifiers_subject_observations add column for website_id as needed for custom attributes

alter table identifiers_subject_observations
  add column website_id integer;

update identifiers_subject_observations
set website_id = (select website_id from subject_observations 
where subject_observations.id = identifiers_subject_observations.subject_observation_id);

alter table identifiers_subject_observations
  alter column website_id set NOT NULL;

comment on column identifiers_subject_observations.website_id is 'Foreign key to the websites table. Website that this identifiers_subject_observations record is linked to.';

