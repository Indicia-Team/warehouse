alter table sample_attributes
  add column system_function character varying(30);

comment on column sample_attributes.system_function is 'Machine readable function of this attribute, e.g. email, cms user ID. Defines how the field can be interpreted by the system.';

alter table occurrence_attributes
  add column system_function character varying(30);

comment on column occurrence_attributes.system_function is 'Machine readable function of this attribute, e.g. sex/stage. Defines how the field can be interpreted by the system.';

alter table location_attributes
  add column system_function character varying(30);

comment on column location_attributes.system_function is 'Machine readable function of this attribute. Defines how the field can be interpreted by the system.';

alter table taxa_taxon_list_attributes
  add column system_function character varying(30);

comment on column taxa_taxon_list_attributes.system_function is 'Machine readable function of this attribute. Defines how the field can be interpreted by the system.';