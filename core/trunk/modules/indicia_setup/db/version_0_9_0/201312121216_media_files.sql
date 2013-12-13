alter table occurrence_images rename to occurrence_media;
alter table sample_images rename to sample_media;
alter table location_images rename to location_media;
alter table taxon_images rename to taxon_media;

insert into termlists (title, description, created_on, created_by_id, updated_on, updated_by_id, external_key)
values ('Media types', 'Types of media supported by the occurrence, sample and location media tables', now(), 1, now(), 1, 'indicia:media_types');

select insert_term('Audio', 'eng', null, 'indicia:media_types');
select insert_term('Audio:SoundCloud', 'eng', null, 'indicia:media_types');
select insert_term('Image', 'eng', null, 'indicia:media_types');
select insert_term('Image:Flickr', 'eng', null, 'indicia:media_types');
select insert_term('Image:Instagram', 'eng', null, 'indicia:media_types');
select insert_term('Image:Local', 'eng', null, 'indicia:media_types');
select insert_term('Image:Twitpic', 'eng', null, 'indicia:media_types');
select insert_term('Social', 'eng', null, 'indicia:media_types');
select insert_term('Social:Facebook', 'eng', null, 'indicia:media_types');
select insert_term('Social:Twitter', 'eng', null, 'indicia:media_types');
select insert_term('Video', 'eng', null, 'indicia:media_types');
select insert_term('Video:Youtube', 'eng', null, 'indicia:media_types');
select insert_term('Video:Vimeo', 'eng', null, 'indicia:media_types');

update termlists_terms c
set parent_id=p.id
from terms tc, termlists_terms p
join terms tp on tp.id=p.term_id
join termlists tl on tl.id=p.termlist_id and tl.external_key='indicia:media_types'
where c.termlist_id=p.termlist_id
and tc.term like tp.term || ':%'
and tc.id=c.term_id;

alter table occurrence_media add column media_type_id int;
alter table sample_media add column media_type_id int;
alter table taxon_media add column media_type_id int;
alter table location_media add column media_type_id int;

-- dynamically set default for the media type to the local image term
create function applyDefault() returns integer AS $$
declare
  t_id integer;
begin
  t_id := id from list_termlists_terms WHERE term='Image:Local' and termlist_external_key='indicia:media_types';
  execute 'alter table occurrence_media alter column media_type_id set default ' || t_id;
  execute 'alter table sample_media alter column media_type_id set default ' || t_id;
  execute 'alter table taxon_media alter column media_type_id set default ' || t_id;
  execute 'alter table location_media alter column media_type_id set default ' || t_id;
  return 1;
end
$$ language plpgsql;

select applyDefault();
drop function applyDefault();


comment on column occurrence_media.media_type_id is 'Foreign key to the termlists_terms table. Identifies the term which describes the type of media this record refers to,';
comment on column sample_media.media_type_id is 'Foreign key to the termlists_terms table. Identifies the term which describes the type of media this record refers to,';
comment on column taxon_media.media_type_id is 'Foreign key to the termlists_terms table. Identifies the term which describes the type of media this record refers to,';
comment on column location_media.media_type_id is 'Foreign key to the termlists_terms table. Identifies the term which describes the type of media this record refers to,';


alter table occurrence_media
  add constraint fk_occurrence_media_type FOREIGN KEY (media_type_id)
      REFERENCES termlists_terms (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION;

alter table sample_media
  add constraint fk_sample_media_type FOREIGN KEY (media_type_id)
      REFERENCES termlists_terms (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION;

alter table taxon_media
  add constraint fk_taxon_media_type FOREIGN KEY (media_type_id)
      REFERENCES termlists_terms (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION;

alter table location_media
  add constraint fk_location_media_type FOREIGN KEY (media_type_id)
      REFERENCES termlists_terms (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION;

update occurrence_media set media_type_id=(select id from detail_termlists_terms where term='Image:Local' and termlist_external_key='indicia:media_types');
update sample_media set media_type_id=(select id from detail_termlists_terms where term='Image:Local' and termlist_external_key='indicia:media_types');
update taxon_media set media_type_id=(select id from detail_termlists_terms where term='Image:Local' and termlist_external_key='indicia:media_types');
update location_media set media_type_id=(select id from detail_termlists_terms where term='Image:Local' and termlist_external_key='indicia:media_types');

ALTER TABLE occurrence_media
   ALTER COLUMN media_type_id SET NOT NULL;
ALTER TABLE sample_media
   ALTER COLUMN media_type_id SET NOT NULL;
ALTER TABLE taxon_media
   ALTER COLUMN media_type_id SET NOT NULL;
ALTER TABLE location_media
   ALTER COLUMN media_type_id SET NOT NULL;

DROP VIEW list_occurrence_images;

CREATE OR REPLACE VIEW list_occurrence_media AS 
 SELECT om.id, om.occurrence_id, om.path, om.caption, om.created_on, om.created_by_id, om.updated_on, om.updated_by_id, om.deleted, 
    om.external_details, o.website_id, om.media_type_id, ctt.term as media_type
   FROM occurrence_media om
   JOIN cache_termlists_terms ctt on ctt.id=om.media_type_id
   JOIN occurrences o ON o.id = om.occurrence_id AND o.deleted = false
  WHERE om.deleted = false;

DROP VIEW gv_occurrence_images;

CREATE OR REPLACE VIEW gv_occurrence_media AS 
 SELECT om.id, om.path, om.caption, om.deleted, om.occurrence_id, ctt.term as media_type
   FROM occurrence_media om
   JOIN cache_termlists_terms ctt on ctt.id=om.media_type_id
  WHERE om.deleted = false;

DROP VIEW list_sample_images;

CREATE OR REPLACE VIEW list_sample_media AS 
 SELECT sm.id, sm.sample_id, sm.path, sm.caption, sm.created_on, sm.created_by_id, sm.updated_on, sm.updated_by_id, sm.deleted, 
    su.website_id, sm.media_type_id, ctt.term as media_type
   FROM sample_media sm
   JOIN cache_termlists_terms ctt on ctt.id=sm.media_type_id
   JOIN samples s ON s.id = sm.sample_id AND s.deleted = false
   JOIN surveys su ON su.id = s.survey_id AND su.deleted = false
  WHERE sm.deleted = false;

DROP VIEW gv_sample_images;

CREATE OR REPLACE VIEW gv_sample_media AS 
 SELECT sm.id, sm.path, sm.caption, sm.deleted, sm.sample_id, ctt.term as media_type
   FROM sample_media sm
   JOIN cache_termlists_terms ctt on ctt.id=sm.media_type_id
  WHERE deleted = false;

DROP VIEW list_taxon_images;

CREATE OR REPLACE VIEW list_taxon_media AS 
 SELECT tm.id, tm.path, tm.caption, tm.deleted, tm.taxon_meaning_id, tm.media_type_id, ctt.term as media_type
   FROM taxon_media tm
   JOIN cache_termlists_terms ctt on ctt.id=tm.media_type_id 
  WHERE tm.deleted = false;

DROP VIEW gv_taxon_images;

CREATE OR REPLACE VIEW gv_taxon_media AS 
 SELECT tm.id, tm.path, tm.caption, tm.deleted, tm.taxon_meaning_id, ctt.term as media_type
   FROM taxon_media tm
   JOIN cache_termlists_terms ctt on ctt.id=tm.media_type_id
  WHERE tm.deleted = false;

DROP VIEW detail_taxon_images;

CREATE OR REPLACE VIEW detail_taxon_media AS 
 SELECT tm.id, tm.path, tm.caption, tm.deleted, tm.taxon_meaning_id, 
     tm.created_by_id, c.username AS created_by, tm.updated_by_id, u.username AS updated_by,
     tm.media_type_id, ctt.term as media_type
   FROM taxon_media tm
   JOIN cache_termlists_terms ctt on ctt.id=tm.media_type_id
   JOIN users c ON c.id = tm.created_by_id
   JOIN users u ON u.id = tm.updated_by_id
  WHERE tm.deleted = false;

DROP VIEW list_location_images;

CREATE OR REPLACE VIEW list_location_media AS 
 SELECT lm.id, lm.location_id, lm.path, lm.caption, lm.created_on, lm.created_by_id, 
     lm.updated_on, lm.updated_by_id, lm.deleted, lw.website_id,
     lm.media_type_id, ctt.term as media_type
   FROM location_media lm
   JOIN cache_termlists_terms ctt on ctt.id=lm.media_type_id
   JOIN locations l ON l.id = lm.location_id AND l.deleted = false
   LEFT JOIN locations_websites lw ON l.id = lw.location_id AND lw.deleted = false
  WHERE lm.deleted = false
  ORDER BY lm.id;

DROP VIEW gv_location_images;

CREATE OR REPLACE VIEW gv_location_media AS 
 SELECT lm.id, lm.path, lm.caption, lm.deleted, lm.location_id, ctt.term as media_type
   FROM location_media lm
   JOIN cache_termlists_terms ctt on ctt.id=lm.media_type_id
  WHERE lm.deleted = false;

-- Views to provide proxy access to the media tables for legacy code that refers to the images tables.
CREATE OR REPLACE VIEW occurrence_images AS 
 SELECT id, occurrence_id, path, caption, created_on, created_by_id, updated_on, updated_by_id, deleted, external_details, media_type_id
   FROM occurrence_media;

CREATE OR REPLACE VIEW sample_images AS 
 SELECT id, sample_id, path, caption, created_on, created_by_id, updated_on, updated_by_id, deleted, media_type_id
   FROM sample_media;

CREATE OR REPLACE VIEW taxon_images AS 
 SELECT id, taxon_meaning_id, path, caption, created_on, created_by_id, updated_on, updated_by_id, deleted, media_type_id
   FROM taxon_media;

CREATE OR REPLACE VIEW location_images AS 
 SELECT id, location_id, path, caption, created_on, created_by_id, updated_on, updated_by_id, deleted, media_type_id
   FROM location_media;