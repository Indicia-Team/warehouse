CREATE VIEW detail_taxa_taxon_lists AS
SELECT ttl.id, ttl.taxon_id, t.taxon, t.authority, ttl.taxon_list_id,
    tl.title AS taxon_list, ttl.taxon_meaning_id, ttl.preferred,
    ttl.parent_id, tp.taxon AS parent, ttl.created_by_id, c.username AS
    created_by, ttl.updated_by_id, u.username AS updated_by
FROM ((((((taxa_taxon_lists ttl JOIN taxon_lists tl ON ((tl.id =
    ttl.taxon_list_id))) JOIN taxa t ON ((t.id = ttl.taxon_id))) JOIN users
    c ON ((c.id = ttl.created_by_id))) JOIN users u ON ((u.id =
    ttl.updated_by_id))) LEFT JOIN taxa_taxon_lists ttlp ON ((ttlp.id =
    ttl.parent_id))) LEFT JOIN taxa tp ON ((tp.id = ttlp.taxon_id)))
WHERE (ttl.deleted = false);

CREATE VIEW list_taxa_taxon_lists AS
SELECT ttl.id, ttl.taxon_id, t.taxon, t.authority, ttl.taxon_list_id,
    ttl.preferred, tl.title AS taxon_list, tl.website_id
FROM ((taxa_taxon_lists ttl JOIN taxon_lists tl ON ((tl.id =
    ttl.taxon_list_id))) JOIN taxa t ON ((t.id = ttl.taxon_id)))
WHERE (ttl.deleted = false);