
CREATE OR REPLACE VIEW list_species_alerts AS 
 SELECT sp.id,
    sp.user_id,
    sp.alert_on_entry,
    sp.alert_on_verify,
    sp.location_id,
    sp.website_id,
    sp.external_key,
    sp.taxon_meaning_id, 
    sp.taxon_list_id
   FROM species_alerts sp
  WHERE sp.deleted = false;

CREATE OR REPLACE VIEW gv_species_alerts AS 
 SELECT sp.id,
    u.id AS user_id,
    u.username,
    sp.alert_on_entry,
    sp.alert_on_verify,
    l.name AS location_name,
    w.title AS website,
    sp.external_key,
    sp.taxon_meaning_id,
    max(cttl.preferred_taxon::text) AS preferred_taxon,
    max(cttl.default_common_name::text) AS default_common_name,
    tl.title as taxon_list_title
   FROM species_alerts sp
     JOIN users u ON u.id = sp.user_id
     LEFT JOIN locations l ON l.id = sp.location_id
     JOIN websites w ON w.id = sp.website_id
     LEFT JOIN cache_taxa_taxon_lists cttl ON (cttl.taxon_meaning_id = sp.taxon_meaning_id OR cttl.external_key::text = sp.external_key::text) AND cttl.preferred = true
     LEFT JOIN taxon_lists tl on tl.id=sp.taxon_list_id and tl.deleted=false
  WHERE sp.deleted = false
  GROUP BY sp.id, u.id, u.username, sp.alert_on_entry, sp.alert_on_verify, l.name, w.title, sp.external_key, sp.taxon_meaning_id, tl.title;


CREATE OR REPLACE VIEW detail_species_alerts AS 
 SELECT sp.id,
    u.id AS user_id,
    u.username,
    sp.alert_on_entry,
    sp.alert_on_verify,
    l.name AS location_name,
    w.title AS website,
    sp.external_key,
    sp.taxon_meaning_id,
    max(cttl.preferred_taxon::text) AS preferred_taxon,
    max(cttl.default_common_name::text) AS default_common_name,
    tl.title as taxon_list_title
   FROM species_alerts sp
     JOIN users u ON u.id = sp.user_id
     LEFT JOIN locations l ON l.id = sp.location_id
     JOIN websites w ON w.id = sp.website_id
     LEFT JOIN cache_taxa_taxon_lists cttl ON (cttl.taxon_meaning_id = sp.taxon_meaning_id OR cttl.external_key::text = sp.external_key::text) AND cttl.preferred = true
     LEFT JOIN taxon_lists tl on tl.id=sp.taxon_list_id and tl.deleted=false
  WHERE sp.deleted = false
  GROUP BY sp.id, u.id, u.username, sp.alert_on_entry, sp.alert_on_verify, l.name, w.title, sp.external_key, sp.taxon_meaning_id, tl.title;

