DROP SEQUENCE IF EXISTS titles_id_seq;
DROP SEQUENCE IF EXISTS user_tokens_id_seq;
DROP SEQUENCE IF EXISTS occurrence_comments_id_seq;
DROP SEQUENCE IF EXISTS system_id_seq;
DROP SEQUENCE IF EXISTS users_websites_id_seq;
DROP SEQUENCE IF EXISTS users_id_seq;
DROP SEQUENCE IF EXISTS taxon_meanings_id_seq;
DROP SEQUENCE IF EXISTS taxon_groups_id_seq;
DROP SEQUENCE IF EXISTS taxa_taxon_lists_id_seq;
DROP SEQUENCE IF EXISTS surveys_id_seq;
DROP SEQUENCE IF EXISTS site_roles_id_seq;
DROP SEQUENCE IF EXISTS samples_id_seq;
DROP SEQUENCE IF EXISTS sample_attributes_websites_id_seq;
DROP SEQUENCE IF EXISTS sample_attributes_id_seq;
DROP SEQUENCE IF EXISTS sample_attribute_values_id_seq;
DROP SEQUENCE IF EXISTS roles_id_seq;
DROP SEQUENCE IF EXISTS people_id_seq;
DROP SEQUENCE IF EXISTS occurrences_id_seq;
DROP SEQUENCE IF EXISTS occurrence_images_id_seq;
DROP SEQUENCE IF EXISTS occurrence_attributes_websites_id_seq;
DROP SEQUENCE IF EXISTS occurrence_attributes_id_seq;
DROP SEQUENCE IF EXISTS occurrence_attribute_values_id_seq;
DROP SEQUENCE IF EXISTS locations_websites_id_seq;
DROP SEQUENCE IF EXISTS locations_id_seq;
DROP SEQUENCE IF EXISTS location_attributes_websites_id_seq;
DROP SEQUENCE IF EXISTS location_attributes_id_seq;
DROP SEQUENCE IF EXISTS location_attribute_values_id_seq;
DROP SEQUENCE IF EXISTS websites_id_seq;
DROP SEQUENCE IF EXISTS terms_id_seq;
DROP SEQUENCE IF EXISTS termlists_terms_id_seq;
DROP SEQUENCE IF EXISTS termlists_id_seq;
DROP SEQUENCE IF EXISTS taxon_lists_id_seq;
DROP SEQUENCE IF EXISTS taxa_id_seq;
DROP SEQUENCE IF EXISTS meanings_id_seq;
DROP SEQUENCE IF EXISTS languages_id_seq;
SET check_function_bodies = false;
--
-- Definition for sequence languages_id_seq (OID = 117419) :
--
CREATE SEQUENCE languages_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;
--
-- Definition for sequence meanings_id_seq (OID = 117455) :
--
CREATE SEQUENCE meanings_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;
--
-- Definition for sequence taxa_id_seq (OID = 117526) :
--
CREATE SEQUENCE taxa_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;
--
-- Definition for sequence taxon_lists_id_seq (OID = 117539) :
--
CREATE SEQUENCE taxon_lists_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;
--
-- Definition for sequence termlists_id_seq (OID = 117551) :
--
CREATE SEQUENCE termlists_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;
--
-- Definition for sequence termlists_terms_id_seq (OID = 117561) :
--
CREATE SEQUENCE termlists_terms_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;
--
-- Definition for sequence terms_id_seq (OID = 117567) :
--
CREATE SEQUENCE terms_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;
--
-- Definition for sequence websites_id_seq (OID = 117591) :
--
CREATE SEQUENCE websites_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;
--
-- Definition for sequence location_attribute_values_id_seq (OID = 118325) :
--
CREATE SEQUENCE location_attribute_values_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;
--
-- Definition for sequence location_attributes_id_seq (OID = 118327) :
--
CREATE SEQUENCE location_attributes_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;
--
-- Definition for sequence location_attributes_websites_id_seq (OID = 118329) :
--
CREATE SEQUENCE location_attributes_websites_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;
--
-- Definition for sequence locations_id_seq (OID = 118331) :
--
CREATE SEQUENCE locations_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;
--
-- Definition for sequence locations_websites_id_seq (OID = 118333) :
--
CREATE SEQUENCE locations_websites_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;
--
-- Definition for sequence occurrence_attribute_values_id_seq (OID = 118335) :
--
CREATE SEQUENCE occurrence_attribute_values_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;
--
-- Definition for sequence occurrence_attributes_id_seq (OID = 118337) :
--
CREATE SEQUENCE occurrence_attributes_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;
--
-- Definition for sequence occurrence_attributes_websites_id_seq (OID = 118339) :
--
CREATE SEQUENCE occurrence_attributes_websites_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;
--
-- Definition for sequence occurrence_images_id_seq (OID = 118341) :
--
CREATE SEQUENCE occurrence_images_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;
--
-- Definition for sequence occurrences_id_seq (OID = 118343) :
--
CREATE SEQUENCE occurrences_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;
--
-- Definition for sequence people_id_seq (OID = 118345) :
--
CREATE SEQUENCE people_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;
--
-- Definition for sequence roles_id_seq (OID = 118347) :
--
CREATE SEQUENCE roles_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;
--
-- Definition for sequence sample_attribute_values_id_seq (OID = 118349) :
--
CREATE SEQUENCE sample_attribute_values_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;
--
-- Definition for sequence sample_attributes_id_seq (OID = 118351) :
--
CREATE SEQUENCE sample_attributes_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;
--
-- Definition for sequence sample_attributes_websites_id_seq (OID = 118353) :
--
CREATE SEQUENCE sample_attributes_websites_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;
--
-- Definition for sequence samples_id_seq (OID = 118355) :
--
CREATE SEQUENCE samples_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;
--
-- Definition for sequence site_roles_id_seq (OID = 118357) :
--
CREATE SEQUENCE site_roles_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;
--
-- Definition for sequence surveys_id_seq (OID = 118359) :
--
CREATE SEQUENCE surveys_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;
--
-- Definition for sequence taxa_taxon_lists_id_seq (OID = 118361) :
--
CREATE SEQUENCE taxa_taxon_lists_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;
--
-- Definition for sequence taxon_groups_id_seq (OID = 118363) :
--
CREATE SEQUENCE taxon_groups_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;
--
-- Definition for sequence taxon_meanings_id_seq (OID = 118365) :
--
CREATE SEQUENCE taxon_meanings_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;
--
-- Definition for sequence users_id_seq (OID = 118367) :
--
CREATE SEQUENCE users_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;
--
-- Definition for sequence users_websites_id_seq (OID = 118369) :
--
CREATE SEQUENCE users_websites_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;
--
-- Definition for sequence system_id_seq (OID = 119202) :
--
CREATE SEQUENCE system_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;
--
-- Definition for sequence occurrence_comments_id_seq (OID = 119213) :
--
CREATE SEQUENCE occurrence_comments_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;
--
-- Definition for sequence user_tokens_id_seq (OID = 119245) :
--
CREATE SEQUENCE user_tokens_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;
--
-- Definition for sequence titles_id_seq (OID = 119331) :
--
CREATE SEQUENCE titles_id_seq
    START WITH 10
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;