CREATE OR REPLACE VIEW gv_rest_api_clients AS
SELECT c.id, c.title, c.description, w.title as website_title, c.website_id
FROM rest_api_clients c
JOIN websites w ON w.id=c.website_id AND w.deleted=false
WHERE c.deleted=false;

CREATE OR REPLACE VIEW gv_rest_api_client_connections AS
SELECT c.id, c.title, c.description, c.website_id
FROM rest_api_client_connections cc
JOIN rest_api_clients c ON c.id=cc.rest_api_client_id AND c.deleted=false
WHERE cc.deleted=false;