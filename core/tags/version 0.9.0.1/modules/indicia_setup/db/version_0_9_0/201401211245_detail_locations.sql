--- Does 2 things:
--- 1) adds the external key as a field
--- 2) adds a deleted flag check to the locations_websites left join

DROP VIEW detail_locations;

CREATE OR REPLACE VIEW detail_locations AS 
 SELECT l.id, l.name, l.code, l.comment, l.parent_id, p.name AS parent, 
    l.centroid_sref, l.location_type_id, l.centroid_sref_system, 
    st_astext(l.centroid_geom) AS centroid_geom, 
    st_astext(l.boundary_geom) AS boundary_geom, 
        CASE
            WHEN l.boundary_geom IS NULL THEN l.centroid_geom
            ELSE l.boundary_geom
        END AS geom,
    l.external_key,
    l.created_on, l.created_by_id, c.username AS created_by, l.updated_on, l.updated_by_id, 
    u.username AS updated_by, lw.website_id, l.public
   FROM locations l
   JOIN users c ON c.id = l.created_by_id
   JOIN users u ON u.id = l.updated_by_id
   LEFT JOIN locations_websites lw ON l.id = lw.location_id AND lw.deleted = FALSE
   LEFT JOIN locations p ON p.id = l.parent_id
  WHERE l.deleted = false AND (l.public=TRUE OR lw.website_id IS NOT NULL);