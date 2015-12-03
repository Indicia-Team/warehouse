CREATE OR REPLACE VIEW list_licences_websites AS 
 SELECT lw.id,
    lw.licence_id,
    l.title AS licence_title,
    l.code AS licence_code,
    lw.website_id,
    w.title AS website
   FROM licences_websites lw
     JOIN licences l ON l.id = lw.licence_id AND l.deleted = false
     JOIN websites w ON w.id = lw.website_id AND w.deleted = false
  WHERE lw.deleted = false;

CREATE OR REPLACE VIEW detail_licences_websites AS
 SELECT lw.id,
    lw.licence_id,
    l.title AS licence_title,
    l.code AS licence_code,
    coalesce(l.url_readable, l.url_legal) as url_readable,
    coalesce(l.url_legal, l.url_readable) as url_legal,
    lw.website_id,
    w.title AS website,
    lw.created_on,
    lw.created_by_id,
    c.username AS created_by
   FROM licences_websites lw
     JOIN licences l ON l.id = lw.licence_id AND l.deleted = false
     JOIN websites w ON w.id = lw.website_id AND w.deleted = false
     JOIN users c on c.id=lw.created_by_id
  WHERE lw.deleted = false;
  
CREATE OR REPLACE VIEW gv_licences_websites AS
 SELECT lw.id,
    lw.licence_id,
    l.title AS licence_title,
    l.code AS licence_code,
    lw.website_id
   FROM licences_websites lw
     JOIN licences l ON l.id = lw.licence_id AND l.deleted = false
     JOIN websites w ON w.id = lw.website_id AND w.deleted = false
  WHERE lw.deleted = false;
