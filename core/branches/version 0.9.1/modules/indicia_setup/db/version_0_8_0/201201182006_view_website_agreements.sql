CREATE OR REPLACE VIEW gv_website_agreements AS 
 SELECT w.id, w.title, w.description
   FROM website_agreements w
  WHERE w.deleted = false;

CREATE OR REPLACE VIEW list_website_agreements AS 
 SELECT w.id, w.title, w.description
   FROM website_agreements w
  WHERE w.deleted = false;

CREATE OR REPLACE VIEW detail_website_agreements AS 
 SELECT w.id, w.title, w.description, w.public, 
     w.provide_for_reporting, w.receive_for_reporting,
     w.provide_for_peer_review, w.receive_for_peer_review,
     w.provide_for_verification, w.receive_for_verification,
     w.provide_for_data_flow, w.receive_for_data_flow,
     w.provide_for_moderation, w.receive_for_moderation,
     w.created_by_id, c.username AS created_by, w.updated_by_id, u.username AS updated_by
   FROM website_agreements w
   JOIN users c ON c.id = w.created_by_id
   JOIN users u ON u.id = w.updated_by_id
  WHERE w.deleted = false;
