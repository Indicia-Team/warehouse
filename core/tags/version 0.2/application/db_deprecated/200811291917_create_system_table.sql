DROP TABLE IF EXISTS "system";

CREATE TABLE "system" (
    id serial NOT NULL,
    "version" character varying(10) DEFAULT ''::character varying NOT NULL,
    "name" character varying(30) DEFAULT ''::character varying NOT NULL,
    repository character varying(150) DEFAULT ''::character varying NOT NULL,
    release_date date,
    CONSTRAINT pk_system PRIMARY KEY (id)
);

COMMENT ON COLUMN system.version IS 'Version number.';
COMMENT ON COLUMN system.name IS 'Version name.';
COMMENT ON COLUMN system.repository IS 'SVN repository path.';
COMMENT ON COLUMN system.release_date IS 'Release date for version.';

INSERT INTO "system" ("id", "version", "name", "repository", "release_date") VALUES (1, '0.1', '', 'http://indicia.googlecode.com/svn/tag/version_0_1', '2009-01-15');
