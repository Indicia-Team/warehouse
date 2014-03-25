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
