CREATE OR REPLACE VIEW list_licences AS
SELECT l.id, l.title, l.code, l.version, l.url_readable, l.url_legal, lw.website_id
FROM licences l
JOIN licences_websites lw ON l.id = lw.licence_id AND lw.deleted = false
WHERE l.deleted=false;

CREATE OR REPLACE VIEW detail_licences AS
SELECT l.id, l.title, l.code, l.description, l.version, l.url_readable, l.url_legal, lw.website_id
FROM licences l
JOIN users c ON c.id = l.created_by_id
JOIN users u ON u.id = l.updated_by_id
JOIN licences_websites lw ON l.id = lw.licence_id AND lw.deleted = false
WHERE l.deleted=false;

CREATE OR REPLACE VIEW gv_licences AS
SELECT l.id, l.title, l.code, l.version
FROM licences l
WHERE l.deleted=false;