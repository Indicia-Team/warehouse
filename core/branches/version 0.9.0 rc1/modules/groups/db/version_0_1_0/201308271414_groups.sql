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
)
;

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
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT uc_groups_users UNIQUE (group_id, user_id)
) WITHOUT OIDS;

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

COMMENT ON CONSTRAINT uc_groups_users ON groups_users IS 'Ensures a user is only a member of a group once.';

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

CREATE UNIQUE INDEX ix_group_invitation_token_unique
   ON group_invitations (token ASC NULLS LAST);

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
