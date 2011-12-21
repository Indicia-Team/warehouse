
INSERT INTO termlists (title, description, created_on, created_by_id, updated_on, updated_by_id, external_key)
VALUES ('Indentification difficulty', 'Lookup list of identification difficulties, used by the verification check rules.', now(), 1, now(), 1, 'indicia:identification_difficulty');

SELECT insert_term('Can be identified at sight in the field.', 'eng', 1, null, 'indicia:identification_difficulty');
SELECT insert_term('Can be identified in the field with care and experience.', 'eng', 2, null, 'indicia:identification_difficulty');
SELECT insert_term('Needs confirmation from vice county recorder.', 'eng', 3, null, 'indicia:identification_difficulty');
SELECT insert_term('Needs confirmation from national expert.', 'eng', 4, null, 'indicia:identification_difficulty');
SELECT insert_term('Voucher specimen required to be examined by national expert.', 'eng', 5, null, 'indicia:identification_difficulty');

insert into taxa_taxon_list_attributes (caption, data_type, termlist_id, created_on, created_by_id, updated_on, updated_by_id, multi_value, public, for_verification_check)
values ('Identification difficulty', 'L', (select id from termlists where external_key='indicia:identification_difficulty'), now(), 1, now(), 1, false, true, true);