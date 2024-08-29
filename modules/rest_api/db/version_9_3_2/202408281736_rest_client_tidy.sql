DROP VIEW gv_rest_api_clients;
CREATE OR REPLACE VIEW gv_rest_api_clients AS
SELECT c.id, c.title, c.username, c.description, w.title as website_title, c.website_id
FROM rest_api_clients c
JOIN websites w ON w.id=c.website_id AND w.deleted=false
WHERE c.deleted=false;

DROP VIEW gv_rest_api_client_connections;
CREATE OR REPLACE VIEW gv_rest_api_client_connections AS
SELECT cc.id, cc.title, c.username, cc.proj_id, cc.description, c.website_id, cc.rest_api_client_id
FROM rest_api_client_connections cc
JOIN rest_api_clients c ON c.id=cc.rest_api_client_id AND c.deleted=false
WHERE cc.deleted=false;

COMMENT ON TABLE rest_api_client_connections
    IS 'Configuration for a single connection method to the REST API by a client. A client may be allowed to use multiple connections for different purposes.
      Includes privileges and filtering to define the capabilities when using this connection.';