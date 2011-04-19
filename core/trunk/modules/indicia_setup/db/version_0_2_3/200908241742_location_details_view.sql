DROP VIEW detail_locations;

CREATE OR REPLACE VIEW detail_locations AS
 SELECT l.id, l.name, l.code, l.parent_id, p.name AS parent, l.centroid_sref, l.centroid_sref_system,
     st_astext(l.centroid_geom) as centroid_geom, st_astext(l.boundary_geom) as boundary_geom,
     l.created_by_id, c.username AS created_by, l.updated_by_id, u.username AS updated_by, lw.website_id
   FROM locations l
   JOIN users c ON c.id = l.created_by_id
   JOIN users u ON u.id = l.updated_by_id
   JOIN locations_websites lw ON l.id = lw.location_id
   LEFT JOIN locations p ON p.id = l.parent_id;