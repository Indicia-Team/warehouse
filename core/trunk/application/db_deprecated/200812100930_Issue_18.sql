DROP TABLE IF EXISTS titles;
CREATE TABLE titles (
    id integer NOT NULL,
    title character varying(10) NOT NULL,
	created_on timestamp NOT NULL, --Date this core_role was created
	created_by_id integer NOT NULL, --Foreign key to the users table (creator)
	updated_on timestamp NOT NULL, --Date this core_role was last updated
	updated_by_id integer NOT NULL --Foreign key to the users table (last updater)
);

ALTER TABLE ONLY titles
    ADD CONSTRAINT pk_titles PRIMARY KEY (id),
    ADD CONSTRAINT fk_title_creator FOREIGN KEY (created_by_id)
		REFERENCES users (id) MATCH SIMPLE
		ON UPDATE NO ACTION ON DELETE NO ACTION,
	ADD CONSTRAINT fk_title_updater FOREIGN KEY (updated_by_id)
		REFERENCES users (id) MATCH SIMPLE
		ON UPDATE NO ACTION ON DELETE NO ACTION
    ;

Insert into titles (id, title, created_on, created_by_id, updated_on, updated_by_id) VALUES (1, 'Mr', now(), 1, now(), 1);
Insert into titles (id, title, created_on, created_by_id, updated_on, updated_by_id) VALUES (2, 'Mrs', now(), 1, now(), 1);
Insert into titles (id, title, created_on, created_by_id, updated_on, updated_by_id) VALUES (3, 'Miss', now(), 1, now(), 1);
Insert into titles (id, title, created_on, created_by_id, updated_on, updated_by_id) VALUES (4, 'Ms', now(), 1, now(), 1);
Insert into titles (id, title, created_on, created_by_id, updated_on, updated_by_id) VALUES (5, 'Master', now(), 1, now(), 1);
Insert into titles (id, title, created_on, created_by_id, updated_on, updated_by_id) VALUES (6, 'Dr', now(), 1, now(), 1);

ALTER TABLE people
	ADD COLUMN title_id integer, --Optional Foreign key to the titles table
	ADD COLUMN address character varying(200), --Optional address
	ADD CONSTRAINT fk_person_title FOREIGN KEY (title_id) REFERENCES titles(id);

COMMENT ON COLUMN titles.title IS 'Persons title';
COMMENT ON COLUMN titles.created_on IS 'Date this record was created.';
COMMENT ON COLUMN titles.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN titles.updated_on IS 'Date this record was last updated.';
COMMENT ON COLUMN titles.updated_by_id IS 'Foreign key to the users table (last updater).';
COMMENT ON COLUMN people.title_id IS 'Foreign key to the titles table.';
COMMENT ON COLUMN people.created_by_id IS 'Optional persons address.';

CREATE SEQUENCE titles_id_seq
    START WITH 10
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;
ALTER SEQUENCE titles_id_seq OWNED BY titles.id;
SELECT pg_catalog.setval('titles_id_seq', 10, false);
