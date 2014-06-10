CREATE OR REPLACE function f_add_species_alerts_views (OUT success bool)
    LANGUAGE plpgsql AS
$func$
BEGIN 
  
success := FALSE;

BEGIN
  CREATE OR REPLACE VIEW list_species_alerts AS 
    SELECT sp.id, sp.user_id, sp.alert_on_entry, sp.alert_on_verify, sp.location_id, sp.website_id, sp.external_key, sp.taxon_meaning_id
      FROM species_alerts sp
    WHERE sp.deleted = false;
END;

BEGIN
  CREATE OR REPLACE VIEW gv_species_alerts AS 
    SELECT sp.id, u.id as user_id, u.username, sp.alert_on_entry, sp.alert_on_verify, l.name as location_name, w.title AS website, sp.external_key, sp.taxon_meaning_id
      FROM species_alerts sp
      JOIN users u on u.id=sp.user_id
      JOIN locations l on l.id=sp.location_id
      JOIN websites w on w.id=sp.website_id
    WHERE sp.deleted = false;
END;


END;
$func$;

SELECT f_add_species_alerts_views();

DROP FUNCTION f_add_species_alerts_views();