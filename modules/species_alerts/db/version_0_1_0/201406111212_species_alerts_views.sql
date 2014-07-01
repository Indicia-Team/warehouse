CREATE OR REPLACE VIEW list_species_alerts AS 
  SELECT sp.id, sp.user_id, sp.alert_on_entry, sp.alert_on_verify, sp.location_id, sp.website_id, sp.external_key, sp.taxon_meaning_id
    FROM species_alerts sp
  WHERE sp.deleted = false;

CREATE OR REPLACE VIEW gv_species_alerts AS 
  SELECT sp.id, u.id as user_id, u.username, sp.alert_on_entry, sp.alert_on_verify, l.name as location_name, w.title AS website, sp.external_key, sp.taxon_meaning_id,
      max(cttl.preferred_taxon) as preferred_taxon, max(cttl.default_common_name) as default_common_name
    FROM species_alerts sp
    JOIN users u on u.id=sp.user_id
    LEFT JOIN locations l on l.id=sp.location_id
    JOIN websites w on w.id=sp.website_id
    JOIN cache_taxa_taxon_lists cttl ON (cttl.taxon_meaning_id=sp.taxon_meaning_id OR cttl.external_key=sp.external_key) and cttl.preferred=true
  WHERE sp.deleted = false
  GROUP BY sp.id, u.id, u.username, sp.alert_on_entry, sp.alert_on_verify, l.name, w.title, sp.external_key, sp.taxon_meaning_id;