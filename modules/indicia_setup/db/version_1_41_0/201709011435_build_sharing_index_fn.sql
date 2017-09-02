CREATE OR REPLACE FUNCTION refresh_index_websites_website_agreements() RETURNS boolean AS $$
BEGIN
DELETE FROM index_websites_website_agreements i
WHERE NOT EXISTS(SELECT 1 FROM build_index_websites_website_agreements bi 
WHERE bi.from_website_id=i.from_website_id AND bi.to_website_id=i.to_website_id);

UPDATE index_websites_website_agreements i 
SET provide_for_reporting=bi.provide_for_reporting,
    receive_for_reporting=bi.receive_for_reporting,
    provide_for_peer_review=bi.provide_for_peer_review,
    receive_for_peer_review=bi.receive_for_peer_review,
    provide_for_verification=bi.provide_for_verification,
    receive_for_verification=bi.receive_for_verification,
    provide_for_data_flow=bi.provide_for_data_flow,
    receive_for_data_flow=bi.receive_for_data_flow,
    provide_for_moderation=bi.provide_for_moderation,
    receive_for_moderation=bi.receive_for_moderation,
    provide_for_editing=bi.provide_for_editing,
    receive_for_editing=bi.receive_for_editing
FROM build_index_websites_website_agreements bi
WHERE i.from_website_id=bi.from_website_id AND i.to_website_id=bi.to_website_id 
AND (i.provide_for_reporting<>bi.provide_for_reporting
 OR i.receive_for_reporting<>bi.receive_for_reporting
 OR i.provide_for_peer_review<>bi.provide_for_peer_review
 OR i.receive_for_peer_review<>bi.receive_for_peer_review
 OR i.provide_for_verification<>bi.provide_for_verification
 OR i.receive_for_verification<>bi.receive_for_verification
 OR i.provide_for_data_flow<>bi.provide_for_data_flow
 OR i.receive_for_data_flow<>bi.receive_for_data_flow
 OR i.provide_for_moderation<>bi.provide_for_moderation
 OR i.receive_for_moderation<>bi.receive_for_moderation
 OR i.provide_for_editing<>bi.provide_for_editing
 OR i.receive_for_editing<>bi.receive_for_editing);
 
INSERT INTO index_websites_website_agreements 
SELECT nextval('index_websites_website_agreements_id_seq'::regclass), bi.from_website_id, bi.to_website_id, 
    bi.provide_for_reporting, bi.receive_for_reporting, bi.provide_for_peer_review, bi.receive_for_peer_review, 
    bi.provide_for_verification, bi.receive_for_verification, bi.provide_for_data_flow, bi.receive_for_data_flow, 
    bi.provide_for_moderation, bi.receive_for_moderation, bi.provide_for_editing, bi.provide_for_editing
 
FROM build_index_websites_website_agreements bi
LEFT JOIN index_websites_website_agreements i ON i.from_website_id=bi.from_website_id AND i.to_website_id=bi.to_website_id
WHERE i.from_website_id IS NULL;

RETURN true;
END;
$$ LANGUAGE plpgsql;