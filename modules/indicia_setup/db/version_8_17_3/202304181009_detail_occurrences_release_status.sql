CREATE OR REPLACE VIEW detail_occurrences AS
 SELECT o.id,
    o.confidential,
    o.comment,
    o.taxa_taxon_list_id,
    ttl.taxon_meaning_id,
    o.record_status,
    o.determiner_id,
    t.taxon,
    s.entered_sref,
    s.entered_sref_system,
    s.geom,
    st_astext(s.geom) AS wkt,
    s.location_name,
    s.survey_id,
    s.date_start,
    s.date_end,
    s.date_type,
    s.location_id,
    l.name AS location,
    l.code AS location_code,
    s.recorder_names,
    (d.first_name::text || ' '::text) || d.surname::text AS determiner,
    o.website_id,
    o.external_key,
    o.created_by_id,
    c.username AS created_by,
    o.created_on,
    o.updated_by_id,
    u.username AS updated_by,
    o.updated_on,
    o.downloaded_flag,
    o.sample_id,
    o.deleted,
    o.zero_abundance,
    t.external_key AS taxon_external_key,
    ttl.taxon_list_id,
    o.sensitivity_precision,
    o.record_substatus,
    o.record_decision_source,
    o.verified_by_id,
   (p_verified.first_name::text || ' '::text) || p_verified.surname::text AS verified_by,
    o.verified_on,
    o.release_status
   FROM occurrences o
     JOIN samples s ON s.id = o.sample_id AND s.deleted = false
     LEFT JOIN people d ON d.id = o.determiner_id AND d.deleted = false
     LEFT JOIN locations l ON l.id = s.location_id AND l.deleted = false
     LEFT JOIN taxa_taxon_lists ttl ON ttl.id = o.taxa_taxon_list_id AND ttl.deleted = false
     LEFT JOIN taxa t ON t.id = ttl.taxon_id AND t.deleted = false
     JOIN surveys su ON s.survey_id = su.id AND su.deleted = false
     JOIN users c ON c.id = o.created_by_id
     JOIN users u ON u.id = o.updated_by_id
     LEFT JOIN users u_verified ON u_verified.id = o.verified_by_id
     LEFT JOIN people p_verified ON p_verified.id = u_verified.person_id
  WHERE o.deleted = false;