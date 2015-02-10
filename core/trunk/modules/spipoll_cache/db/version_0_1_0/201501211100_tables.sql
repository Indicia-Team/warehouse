-- there are issues with straight dates. When geoserver retrieves them, they are assume to be in the local
-- Time zone, but this is converted to GMT for the purposes of WFS etc, so it goes back one hour (and hence one day)
-- when the data comes out.
-- for this reason we have to convert the dates to a timestamp with time zone.
DROP TABLE spipoll_collections_cache CASCADE;
CREATE TABLE spipoll_collections_cache (
    collection_id integer NOT NULL,
    datedebut date, 
    datedebut_txt character varying(10), 
    datefin date,
    datefin_txt character varying(10),
    closed timestamp without time zone,
    updated timestamp without time zone,
    nom character varying(200),
    srefX text,
    srefY text,
    lat text,
    long text,
    habitat_ids text,
    username text,
    flower_id integer NOT NULL,
    flower_type_id integer, --- flower type attribute: termlist id
    flower_taxon_ids text,
    taxons_fleur_precise text,
    status_fleur_code char(1),
    flower_taxon_type character varying(5),
    sky_ids text,
    shade_ids text,
    temp_ids text,
    wind_ids text, 
    insect_taxon_ids text,
    status_insecte_code text,
    notonaflower_ids text,
    insect_search text,
    taxons_insecte_precise text,
    image_de_environment character varying(200),
    image_de_la_fleur character varying(200)
) ;

SELECT AddGeometryColumn ('spipoll_collections_cache', 'geom', 900913, 'GEOMETRY', 2);

CREATE OR REPLACE VIEW spipoll_collections_cache_view AS
	SELECT * FROM spipoll_collections_cache ORDER BY closed DESC;

DROP TABLE spipoll_insects_cache CASCADE;
CREATE TABLE spipoll_insects_cache (
    insect_id integer NOT NULL,
    collection_id integer NOT NULL,
    datedebut date, 
    datedebut_txt character varying(10), 
    datefin date,
    datefin_txt character varying(10),
    closed timestamp without time zone,
    updated timestamp without time zone,
    nom character varying(200),
    protocol text,
    srefX text,
    srefY text,
    lat text,
    long text,
    habitat_ids text,
    habitat text,
    nearest_hive integer,
    within50m text,
    username text,
    userid text,
    email text,
    flower_type_id integer, --- flower type attribute: termlist id
    flower_type text,
    flower_taxon_ids text,
    status_fleur_giver text,
    status_fleur text,
    status_fleur_code char(1),
    flower_taxon text,
    taxons_fleur_precise text,
    fleur_historical_taxon text,
    flower_taxon_type character varying(5),
    date_de_session text,
    starttime text,
    endtime text,
    sky_ids text,
    ciel text,
    shade_ids text,
    fleur_a_lombre text,
    temp_ids text,
    temperature text,
    wind_ids text, 
    vent text, 
    insect_taxon_ids text,
    status_insecte_giver text,
    status_insecte text,
    status_insecte_code char(1),
    insect_taxon text,
    taxons_insecte_precise text,
    insect_historical_taxon text,
    insect_taxon_type character varying(5),
    notonaflower text,
    notonaflower_id text,
    number_insect text,
    image_de_environment character varying(200),
    image_de_environment_camera character varying(200),
    image_de_environment_datetime character varying(200),
    image_de_la_fleur character varying(200),
    image_de_la_fleur_camera character varying(200),
    image_de_la_fleur_datetime character varying(200),
    image_d_insecte character varying(200),
    image_d_insecte_camera character varying(200),
    image_d_insecte_datetime character varying(200)
) ;

SELECT AddGeometryColumn ('spipoll_insects_cache', 'geom', 900913, 'GEOMETRY', 2);

CREATE OR REPLACE VIEW spipoll_insects_cache_view AS
	SELECT * FROM spipoll_insects_cache ORDER BY closed DESC, insect_id;