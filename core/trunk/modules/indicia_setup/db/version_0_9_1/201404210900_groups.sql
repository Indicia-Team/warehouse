CREATE OR REPLACE function f_add_ddl (OUT success bool)
    LANGUAGE plpgsql AS
$func$
BEGIN 
  
success := TRUE;

BEGIN
  CREATE TABLE groups
  (
    id serial NOT NULL, 
    title character varying NOT NULL,
    description character varying, 
    filter_id integer,
    joining_method char NOT NULL,
    website_id integer NOT NULL,
    created_on timestamp without time zone NOT NULL, -- Date this record was created.
    created_by_id integer NOT NULL, -- Foreign key to the users table (creator).
    updated_on timestamp without time zone NOT NULL, -- Date this record was last updated.
    updated_by_id integer NOT NULL, -- Foreign key to the users table (last updater).
    deleted boolean NOT NULL DEFAULT false, -- Has this record been deleted?
    CONSTRAINT pk_groups PRIMARY KEY (id),
    CONSTRAINT fk_group_creator FOREIGN KEY (created_by_id)
        REFERENCES users (id) MATCH SIMPLE
        ON UPDATE NO ACTION ON DELETE NO ACTION,
    CONSTRAINT fk_group_updater FOREIGN KEY (updated_by_id)
        REFERENCES users (id) MATCH SIMPLE
        ON UPDATE NO ACTION ON DELETE NO ACTION,
    CONSTRAINT fk_group_filter FOREIGN KEY (filter_id)
        REFERENCES filters (id) MATCH SIMPLE
        ON UPDATE NO ACTION ON DELETE NO ACTION,
    CONSTRAINT fk_group_website FOREIGN KEY (website_id)
        REFERENCES websites (id) MATCH SIMPLE
        ON UPDATE NO ACTION ON DELETE NO ACTION
  ) 
  WITH (
    OIDS = FALSE
  );

  ALTER TABLE groups
    ADD CONSTRAINT groups_joining_method_check CHECK (joining_method = ANY (ARRAY['P'::bpchar, 'R'::bpchar, 'I'::bpchar, 'A'::bpchar]));
  
EXCEPTION
    WHEN duplicate_table THEN 
      RAISE NOTICE 'table exists.';
      success := FALSE;
END;

BEGIN
  CREATE TABLE groups_users (
      id serial,
      group_id integer,
      user_id integer,
      administrator boolean DEFAULT false NOT NULL,
      created_on timestamp without time zone NOT NULL,
      created_by_id integer NOT NULL,
      updated_on timestamp without time zone NOT NULL,
      updated_by_id integer NOT NULL,
      deleted boolean DEFAULT false NOT NULL,
    CONSTRAINT pk_groups_users PRIMARY KEY (id),
    CONSTRAINT fk_groups_users_group FOREIGN KEY (group_id)
        REFERENCES groups (id) MATCH SIMPLE
        ON UPDATE NO ACTION ON DELETE NO ACTION,
    CONSTRAINT fk_groups_users_user FOREIGN KEY (user_id)
        REFERENCES users (id) MATCH SIMPLE
        ON UPDATE NO ACTION ON DELETE NO ACTION,
    CONSTRAINT fk_groups_users_creator FOREIGN KEY (created_by_id)
        REFERENCES users (id) MATCH SIMPLE
        ON UPDATE NO ACTION ON DELETE NO ACTION,
    CONSTRAINT fk_groups_users_updater FOREIGN KEY (updated_by_id)
        REFERENCES users (id) MATCH SIMPLE
        ON UPDATE NO ACTION ON DELETE NO ACTION
  ) WITHOUT OIDS;
EXCEPTION
    WHEN duplicate_table THEN 
      RAISE NOTICE 'table exists.';
      success := FALSE;
END;

BEGIN
  CREATE TABLE group_invitations (
      id serial NOT NULL,
      group_id integer NOT NULL,
      email character varying NOT NULL,
      token character varying NOT NULL,
      created_on timestamp without time zone NOT NULL,
      created_by_id integer NOT NULL,
      updated_on timestamp without time zone NOT NULL,
      updated_by_id integer NOT NULL,
      deleted boolean DEFAULT false NOT NULL,
    CONSTRAINT pk_group_invitations PRIMARY KEY (id),
    CONSTRAINT fk_group_invitations_group FOREIGN KEY (group_id)
        REFERENCES groups (id) MATCH SIMPLE
        ON UPDATE NO ACTION ON DELETE NO ACTION,
    CONSTRAINT fk_group_invitations_creator FOREIGN KEY (created_by_id)
        REFERENCES users (id) MATCH SIMPLE
        ON UPDATE NO ACTION ON DELETE NO ACTION,
    CONSTRAINT fk_group_invitations_updater FOREIGN KEY (updated_by_id)
        REFERENCES users (id) MATCH SIMPLE
        ON UPDATE NO ACTION ON DELETE NO ACTION

  ) WITHOUT OIDS;
EXCEPTION
    WHEN duplicate_table THEN 
      RAISE NOTICE 'table exists.';
      success := FALSE;
END;

/* 
Column additions
The following columns must be added separately to the initial table create, as this script tidies up a messy upgrade
*/

BEGIN
  ALTER TABLE groups ADD COLUMN code character varying(20);
EXCEPTION
    WHEN duplicate_column THEN 
      RAISE NOTICE 'column exists.';
      success := FALSE;
END;

BEGIN
  ALTER TABLE groups ADD COLUMN group_type_id integer;
    ALTER TABLE groups
  ADD CONSTRAINT fk_group_type FOREIGN KEY (group_type_id)
      REFERENCES termlists_terms (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION;
EXCEPTION
    WHEN duplicate_column THEN 
      RAISE NOTICE 'column exists.';
      success := FALSE;
END;   

BEGIN
  ALTER TABLE groups ADD COLUMN from_date date;
EXCEPTION
    WHEN duplicate_column THEN 
      RAISE NOTICE 'column exists.';
      success := FALSE;
END;

BEGIN
  ALTER TABLE groups ADD COLUMN to_date date;
EXCEPTION
    WHEN duplicate_column THEN 
      RAISE NOTICE 'column exists.';
      success := FALSE;
END;

BEGIN
  ALTER TABLE groups ADD COLUMN private_records boolean default false;
EXCEPTION
    WHEN duplicate_column THEN 
      RAISE NOTICE 'column exists.';
      success := FALSE;
END;

BEGIN
  ALTER TABLE groups ADD COLUMN implicit_record_inclusion boolean NOT NULL default false;
EXCEPTION
    WHEN duplicate_column THEN 
      RAISE NOTICE 'column exists.';
      success := FALSE;
END;

BEGIN
  ALTER TABLE groups ADD COLUMN view_full_precision boolean NOT NULL default false;
EXCEPTION
    WHEN duplicate_column THEN 
      RAISE NOTICE 'column exists.';
      success := FALSE;
END;

BEGIN
  ALTER TABLE groups_users ADD pending BOOLEAN DEFAULT FALSE;
EXCEPTION
    WHEN duplicate_column THEN 
      RAISE NOTICE 'column exists.';
      success := FALSE;
END;

END
$func$;

SELECT f_add_ddl();

DROP FUNCTION f_add_ddl();

DROP INDEX IF EXISTS ix_group_invitation_token_unique;
CREATE UNIQUE INDEX ix_group_invitation_token_unique
   ON group_invitations (token ASC NULLS LAST);
   
DROP INDEX IF EXISTS idx_groups_users_unique;
CREATE UNIQUE INDEX idx_groups_users_unique
ON groups_users(group_id , user_id)
WHERE deleted=false;

COMMENT ON TABLE groups IS 'A list of entities which involve groupings of people, such as recording groups, projects or organisations.';
COMMENT ON COLUMN groups.id IS 'Primary key and unique identifier for the table';
COMMENT ON COLUMN groups.title IS 'Title for the group';
COMMENT ON COLUMN groups.description IS 'Description of the group';
COMMENT ON COLUMN groups.filter_id IS 'Foreign key to the filters table. Identifies the scope of the group.';
COMMENT ON COLUMN groups.joining_method IS 'Defines how a user can join a group. Options are P (public, anyone can join), R (by request or invite, group admins approve members), I (invite only, group admins send invites).';
COMMENT ON COLUMN groups.website_id IS 'Foreign key to the websites table, identifies the website that hosts this group.';
COMMENT ON COLUMN groups.created_on IS 'Date this record was created.';
COMMENT ON COLUMN groups.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN groups.updated_on IS 'Date this record was last updated.';
COMMENT ON COLUMN groups.updated_by_id IS 'Foreign key to the users table (last updater).';
COMMENT ON COLUMN groups.deleted IS 'Has this record been deleted?';
COMMENT ON COLUMN groups.code IS 'A code or abbreviation identifying the group.';
COMMENT ON COLUMN groups.group_type_id IS 'Foreign key to the termlists_terms table. Identifies the type of group, e.g. recording group, project, organisation.';
COMMENT ON COLUMN groups.from_date IS 'Date the group''s activities commenced if relevent, e.g. a project start date.';
COMMENT ON COLUMN groups.to_date IS 'Date the group''s activities ceased if relevent, e.g. a project finish date.';
COMMENT ON COLUMN groups.private_records IS 
    'Set to true to indicate that the records input which are directly linked to the group should be witheld from uses outside the group. Relies on reporting queries to respect this.'; 
COMMENT ON COLUMN groups.implicit_record_inclusion IS
    'If true, then records are included in this group''s content if they are posted by a group member and meet the groups filter criteria. If false, then records must be explicitly posted into the group.';
COMMENT ON COLUMN groups.view_full_precision IS 'Allow group members to view records explicitly posted into the at full precision.';    

COMMENT ON TABLE groups_users IS 'Identifies the users that belong to a group.';
COMMENT ON COLUMN groups_users.id IS 'Unique identifier and primary key for the table.';
COMMENT ON COLUMN groups_users.group_id  IS 'Foreign key to the groups table. Identifies the group of which the user is a member.';
COMMENT ON COLUMN groups_users.user_id  IS 'Foreign key to the users table. Identifies the user who is a member of the group.';
COMMENT ON COLUMN groups_users.administrator  IS 'Is this user an administrator of this group.';
COMMENT ON COLUMN groups_users.created_on IS 'Date this record was created.';
COMMENT ON COLUMN groups_users.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN groups_users.updated_on IS 'Date this record was last updated.';
COMMENT ON COLUMN groups_users.updated_by_id IS 'Foreign key to the users table (last updater).';
COMMENT ON COLUMN groups_users.deleted IS 'Has this record been deleted?';
COMMENT ON COLUMN groups_users.pending is 'Is the membership pending approval by the group admin?';

COMMENT ON TABLE group_invitations IS 'List of outstanding invitations to group. Accepted invitations are dropped from this table.';
COMMENT ON COLUMN group_invitations.id IS 'Unique identifier and primary key for the table.';
COMMENT ON COLUMN group_invitations.group_id  IS 'Foreign key to the groups table. Identifies the group to which someone is being invited.';
COMMENT ON COLUMN group_invitations.email  IS 'The email address to which the invitation has been sent. May or may not be the email of a registered user.';
COMMENT ON COLUMN group_invitations.token  IS 'A unique and cryptic token sent with the invitation.';
COMMENT ON COLUMN group_invitations.created_on IS 'Date this record was created.';
COMMENT ON COLUMN group_invitations.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN group_invitations.updated_on IS 'Date this record was last updated.';
COMMENT ON COLUMN group_invitations.updated_by_id IS 'Foreign key to the users table (last updater).';
COMMENT ON COLUMN group_invitations.deleted IS 'Has this record been deleted?';

CREATE OR REPLACE VIEW list_group_invitations AS 
 SELECT i.id, i.group_id, i.email, i.token, g.website_id, g.title as group_title
   FROM group_invitations i
   JOIN groups g on g.id=i.group_id AND g.deleted=false
  WHERE i.deleted = false;
