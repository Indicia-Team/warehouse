CREATE OR REPLACE VIEW gv_websites_website_agreements AS 
 SELECT wwa.id, w.id as website_id, w.title as website, wa.title as agreement
   FROM websites_website_agreements wwa
   JOIN websites w ON w.id=wwa.website_id AND wwa.deleted=false
   JOIN website_agreements wa ON wa.id=wwa.website_agreement_id AND wa.deleted=false
  WHERE wwa.deleted = false;

CREATE OR REPLACE VIEW list_websites_website_agreements AS 
 SELECT wwa.id, w.title as website, wa.title as agreement
   FROM websites_website_agreements wwa
   JOIN websites w ON w.id=wwa.website_id AND wwa.deleted=false
   JOIN website_agreements wa ON wa.id=wwa.website_agreement_id AND wa.deleted=false
  WHERE wwa.deleted = false;

CREATE OR REPLACE VIEW detail_websites_website_agreements AS 
 SELECT wwa.id, w.title as website, wa.title as agreement, wwa.created_by_id, c.username AS created_by, wwa.updated_by_id, u.username AS updated_by
   FROM websites_website_agreements wwa
   JOIN websites w ON w.id=wwa.website_id AND wwa.deleted=false
   JOIN website_agreements wa ON wa.id=wwa.website_agreement_id AND wa.deleted=false
   JOIN users c ON c.id = wwa.created_by_id
   JOIN users u ON u.id = wwa.updated_by_id
  WHERE wwa.deleted = false;
