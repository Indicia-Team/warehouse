ALTER TABLE taxa_taxon_lists
ADD COLUMN allow_data_entry boolean DEFAULT true NOT NULL;

CREATE SEQUENCE taxon_relation_types_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;

CREATE TABLE taxon_relation_types (
    id integer DEFAULT nextval('taxon_relation_types_id_seq'::regclass) NOT NULL,
    caption character varying(100) NOT NULL,
    forward_term character varying(100) NOT NULL,
    reverse_term character varying(100) NOT NULL,
    relation_code integer NOT NULL,
    special character(1),
    created_on timestamp without time zone NOT NULL,
    created_by_id integer NOT NULL,
    updated_on timestamp without time zone NOT NULL,
    updated_by_id integer NOT NULL,
    deleted boolean DEFAULT false NOT NULL
);

ALTER TABLE ONLY taxon_relation_types 
    ADD CONSTRAINT pk_taxon_relation_types PRIMARY KEY (id);
ALTER TABLE ONLY taxon_relation_types 
    ADD CONSTRAINT fk_taxon_relation_type_creator FOREIGN KEY (created_by_id) REFERENCES users(id);
ALTER TABLE ONLY taxon_relation_types 
    ADD CONSTRAINT fk_taxon_relation_type_updater FOREIGN KEY (updated_by_id) REFERENCES users(id);
ALTER TABLE taxon_relation_types 
    ADD CONSTRAINT taxon_relation_type_code_check CHECK (relation_code = ANY (ARRAY[0, 1, 3, 7]));

CREATE SEQUENCE taxon_relations_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;

CREATE TABLE taxon_relations (
    id integer DEFAULT nextval('taxon_relations_id_seq'::regclass) NOT NULL,
    from_taxon_meaning_id integer NOT NULL,
    to_taxon_meaning_id integer NOT NULL,
    taxon_relation_type_id integer NOT NULL,
    created_on timestamp without time zone NOT NULL,
    created_by_id integer NOT NULL,
    updated_on timestamp without time zone NOT NULL,
    updated_by_id integer NOT NULL,
    deleted boolean DEFAULT false NOT NULL
);

ALTER TABLE ONLY taxon_relations
    ADD CONSTRAINT pk_taxon_relations PRIMARY KEY (id);
ALTER TABLE ONLY taxon_relations
    ADD CONSTRAINT fk_taxon_relation_creator FOREIGN KEY (created_by_id) REFERENCES users(id);
ALTER TABLE ONLY taxon_relations
    ADD CONSTRAINT fk_taxon_relation_updater FOREIGN KEY (updated_by_id) REFERENCES users(id);
ALTER TABLE ONLY taxon_relations
    ADD CONSTRAINT fk_taxon_relation_from FOREIGN KEY (from_taxon_meaning_id) REFERENCES taxon_meanings(id);
ALTER TABLE ONLY taxon_relations
    ADD CONSTRAINT fk_taxon_relation_to FOREIGN KEY (to_taxon_meaning_id) REFERENCES taxon_meanings(id);
ALTER TABLE ONLY taxon_relations
    ADD CONSTRAINT fk_taxon_relation_relation_type FOREIGN KEY (taxon_relation_type_id) REFERENCES taxon_relation_types(id);

INSERT INTO taxon_relation_types (caption,forward_term,reverse_term,relation_code,special,created_on,created_by_id,
	updated_on,updated_by_id) values
	('Lump', 'was created by lumping', 'was lumped into', 3, 'L'::bpchar, now(), 1, now(), 1);
INSERT INTO taxon_relation_types (caption,forward_term,reverse_term,relation_code,special,created_on,created_by_id,
	updated_on,updated_by_id) values
	('Split', 'was split into', 'was created by splitting', 3, 'R'::bpchar, now(), 1, now(), 1);

DROP VIEW list_taxa_taxon_lists;

CREATE OR REPLACE VIEW list_taxa_taxon_lists AS 
 SELECT ttl.id, ttl.taxon_id, t.taxon, t.authority, tc.taxon AS common, ttl.taxon_list_id, ttl.preferred, tl.title AS taxon_list, tl.website_id, t.external_key, ttl.allow_data_entry
   FROM taxa_taxon_lists ttl
   JOIN taxon_lists tl ON tl.id = ttl.taxon_list_id
   JOIN taxa t ON t.id = ttl.taxon_id
   LEFT JOIN taxa tc ON tc.id = ttl.common_taxon_id
  WHERE ttl.deleted = false;

DROP VIEW detail_taxa_taxon_lists;

CREATE OR REPLACE VIEW detail_taxa_taxon_lists AS 
 SELECT ttl.id, ttl.taxon_id, t.taxon, t.authority, l.iso AS language_iso, tc.taxon AS common, ttl.taxon_list_id, tl.title AS taxon_list, ttl.taxon_meaning_id, 
     ttl.preferred, ttl.taxonomic_sort_order, ttl.description AS description_in_list, t.description AS general_description, ttl.parent_id, tp.taxon AS parent, 
     ti.path AS image_path, ti.caption AS image_caption, ttl.created_by_id, c.username AS created_by, ttl.updated_by_id, u.username AS updated_by, t.external_key, ttl.allow_data_entry
   FROM taxa_taxon_lists ttl
   JOIN taxon_lists tl ON tl.id = ttl.taxon_list_id
   JOIN taxa t ON t.id = ttl.taxon_id AND t.deleted = false
   JOIN languages l ON l.id = t.language_id
   LEFT JOIN taxa tc ON tc.id = ttl.common_taxon_id AND tc.deleted = false
   JOIN users c ON c.id = ttl.created_by_id
   JOIN users u ON u.id = ttl.updated_by_id
   LEFT JOIN taxa_taxon_lists ttlp ON ttlp.id = ttl.parent_id AND ttlp.deleted = false
   LEFT JOIN taxa tp ON tp.id = ttlp.taxon_id AND tp.deleted = false
   LEFT JOIN taxon_images ti ON ti.taxon_meaning_id = ttl.taxon_meaning_id AND ti.deleted = false
  WHERE ttl.deleted = false;

DROP VIEW IF EXISTS gv_taxon_relations;
CREATE OR REPLACE VIEW gv_taxon_relations AS
 SELECT tr.id, tr.from_taxon_meaning_id as my_taxon_meaning_id, t1.taxon as my_taxon, tr.to_taxon_meaning_id as other_taxon_meaning_id, t2.taxon as other_taxon, tr.taxon_relation_type_id, trt.forward_term as term, tr.deleted
   FROM taxon_relations tr
   JOIN taxon_relation_types trt ON tr.taxon_relation_type_id= trt.id AND trt.deleted = FALSE
   JOIN taxa_taxon_lists ttl1 ON ttl1.taxon_meaning_id = tr.from_taxon_meaning_id AND ttl1.preferred = TRUE AND ttl1.deleted = FALSE
   JOIN taxa t1 ON t1.id = ttl1.taxon_id AND t1.deleted = FALSE
   JOIN taxa_taxon_lists ttl2 ON ttl2.taxon_meaning_id = tr.to_taxon_meaning_id AND ttl2.preferred = TRUE AND ttl2.deleted = FALSE
   JOIN taxa t2 ON t2.id = ttl2.taxon_id AND t2.deleted = FALSE
UNION ALL
 SELECT tr.id, tr.to_taxon_meaning_id as my_taxon_meaning_id, t2.taxon as my_taxon, tr.from_taxon_meaning_id as other_taxon_meaning_id, t1.taxon as other_taxon, tr.taxon_relation_type_id, trt.reverse_term as term, tr.deleted
   FROM taxon_relations tr
   JOIN taxon_relation_types trt ON tr.taxon_relation_type_id= trt.id AND trt.deleted = FALSE
   JOIN taxa_taxon_lists ttl1 ON ttl1.taxon_meaning_id = tr.from_taxon_meaning_id AND ttl1.preferred = TRUE AND ttl1.deleted = FALSE
   JOIN taxa t1 ON t1.id = ttl1.taxon_id AND t1.deleted = FALSE
   JOIN taxa_taxon_lists ttl2 ON ttl2.taxon_meaning_id = tr.to_taxon_meaning_id AND ttl2.preferred = TRUE AND ttl2.deleted = FALSE
   JOIN taxa t2 ON t2.id = ttl2.taxon_id AND t2.deleted = FALSE;
;