alter table groups_users
	add pending boolean default false;

comment on column groups_users.pending is 'Is the membership pending approval by the group admin?';