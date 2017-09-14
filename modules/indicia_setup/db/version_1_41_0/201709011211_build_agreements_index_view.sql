CREATE OR REPLACE VIEW build_index_websites_website_agreements AS 
 SELECT 
    wwafrom.website_id AS from_website_id,
    wwato.website_id AS to_website_id,
        BOOL_OR(CASE
            WHEN wwafrom.website_id = wwato.website_id THEN true
            ELSE
            CASE wa.provide_for_reporting
                WHEN 'D'::bpchar THEN false
                WHEN 'R'::bpchar THEN true
                ELSE wwafrom.provide_for_reporting
            END AND
            CASE wa.receive_for_reporting
                WHEN 'D'::bpchar THEN false
                WHEN 'R'::bpchar THEN true
                ELSE wwato.receive_for_reporting
            END
        END) AS provide_for_reporting,
        BOOL_OR(CASE
            WHEN wwafrom.website_id = wwato.website_id THEN true
            ELSE
            CASE wa.receive_for_reporting
                WHEN 'D'::bpchar THEN false
                WHEN 'R'::bpchar THEN true
                ELSE wwafrom.receive_for_reporting
            END AND
            CASE wa.provide_for_reporting
                WHEN 'D'::bpchar THEN false
                WHEN 'R'::bpchar THEN true
                ELSE wwato.provide_for_reporting
            END
        END) AS receive_for_reporting,
        BOOL_OR(CASE
            WHEN wwafrom.website_id = wwato.website_id THEN true
            ELSE
            CASE wa.provide_for_peer_review
                WHEN 'D'::bpchar THEN false
                WHEN 'R'::bpchar THEN true
                ELSE wwafrom.provide_for_peer_review
            END AND
            CASE wa.receive_for_peer_review
                WHEN 'D'::bpchar THEN false
                WHEN 'R'::bpchar THEN true
                ELSE wwato.receive_for_peer_review
            END
        END) AS provide_for_peer_review,
        BOOL_OR(CASE
            WHEN wwafrom.website_id = wwato.website_id THEN true
            ELSE
            CASE wa.receive_for_peer_review
                WHEN 'D'::bpchar THEN false
                WHEN 'R'::bpchar THEN true
                ELSE wwafrom.receive_for_peer_review
            END AND
            CASE wa.provide_for_peer_review
                WHEN 'D'::bpchar THEN false
                WHEN 'R'::bpchar THEN true
                ELSE wwato.provide_for_peer_review
            END
        END) AS receive_for_peer_review,
        BOOL_OR(CASE
            WHEN wwafrom.website_id = wwato.website_id THEN true
            ELSE
            CASE wa.provide_for_verification
                WHEN 'D'::bpchar THEN false
                WHEN 'R'::bpchar THEN true
                ELSE wwafrom.provide_for_verification
            END AND
            CASE wa.receive_for_verification
                WHEN 'D'::bpchar THEN false
                WHEN 'R'::bpchar THEN true
                ELSE wwato.receive_for_verification
            END
        END) AS provide_for_verification,
        BOOL_OR(CASE
            WHEN wwafrom.website_id = wwato.website_id THEN true
            ELSE
            CASE wa.receive_for_verification
                WHEN 'D'::bpchar THEN false
                WHEN 'R'::bpchar THEN true
                ELSE wwafrom.receive_for_verification
            END AND
            CASE wa.provide_for_verification
                WHEN 'D'::bpchar THEN false
                WHEN 'R'::bpchar THEN true
                ELSE wwato.provide_for_verification
            END
        END) AS receive_for_verification,
        BOOL_OR(CASE
            WHEN wwafrom.website_id = wwato.website_id THEN true
            ELSE
            CASE wa.provide_for_data_flow
                WHEN 'D'::bpchar THEN false
                WHEN 'R'::bpchar THEN true
                ELSE wwafrom.provide_for_data_flow
            END AND
            CASE wa.receive_for_data_flow
                WHEN 'D'::bpchar THEN false
                WHEN 'R'::bpchar THEN true
                ELSE wwato.receive_for_data_flow
            END
        END) AS provide_for_data_flow,
        BOOL_OR(CASE
            WHEN wwafrom.website_id = wwato.website_id THEN true
            ELSE
            CASE wa.receive_for_data_flow
                WHEN 'D'::bpchar THEN false
                WHEN 'R'::bpchar THEN true
                ELSE wwafrom.receive_for_data_flow
            END AND
            CASE wa.provide_for_data_flow
                WHEN 'D'::bpchar THEN false
                WHEN 'R'::bpchar THEN true
                ELSE wwato.provide_for_data_flow
            END
        END) AS receive_for_data_flow,
        BOOL_OR(CASE
            WHEN wwafrom.website_id = wwato.website_id THEN true
            ELSE
            CASE wa.provide_for_moderation
                WHEN 'D'::bpchar THEN false
                WHEN 'R'::bpchar THEN true
                ELSE wwafrom.provide_for_moderation
            END AND
            CASE wa.receive_for_moderation
                WHEN 'D'::bpchar THEN false
                WHEN 'R'::bpchar THEN true
                ELSE wwato.receive_for_moderation
            END
        END) AS provide_for_moderation,
        BOOL_OR(CASE
            WHEN wwafrom.website_id = wwato.website_id THEN true
            ELSE
            CASE wa.receive_for_moderation
                WHEN 'D'::bpchar THEN false
                WHEN 'R'::bpchar THEN true
                ELSE wwafrom.receive_for_moderation
            END AND
            CASE wa.provide_for_moderation
                WHEN 'D'::bpchar THEN false
                WHEN 'R'::bpchar THEN true
                ELSE wwato.provide_for_moderation
            END
        END) AS receive_for_moderation,
        BOOL_OR(CASE
            WHEN wwafrom.website_id = wwato.website_id THEN true
            ELSE
            CASE wa.provide_for_editing
                WHEN 'D'::bpchar THEN false
                WHEN 'R'::bpchar THEN true
                ELSE wwafrom.provide_for_editing
            END AND
            CASE wa.receive_for_editing
                WHEN 'D'::bpchar THEN false
                WHEN 'R'::bpchar THEN true
                ELSE wwato.receive_for_editing
            END
        END) AS provide_for_editing,
        BOOL_OR(CASE
            WHEN wwafrom.website_id = wwato.website_id THEN true
            ELSE
            CASE wa.receive_for_editing
                WHEN 'D'::bpchar THEN false
                WHEN 'R'::bpchar THEN true
                ELSE wwafrom.receive_for_editing
            END AND
            CASE wa.provide_for_editing
                WHEN 'D'::bpchar THEN false
                WHEN 'R'::bpchar THEN true
                ELSE wwato.provide_for_editing
            END
        END) AS receive_for_editing
   FROM websites_website_agreements wwafrom
     JOIN website_agreements wa ON wa.id = wwafrom.website_agreement_id AND wa.deleted = false
     JOIN websites_website_agreements wwato ON wwato.website_agreement_id = wa.id AND wwato.deleted = false AND wwato.website_id <> wwafrom.website_id
  WHERE wwafrom.deleted = false
  GROUP BY wwafrom.website_id, wwato.website_id
UNION
 SELECT websites.id AS from_website_id,
    websites.id AS to_website_id,
    true AS provide_for_reporting,
    true AS receive_for_reporting,
    true AS provide_for_peer_review,
    true AS receive_for_peer_review,
    true AS provide_for_verification,
    true AS receive_for_verification,
    true AS provide_for_data_flow,
    true AS receive_for_data_flow,
    true AS provide_for_moderation,
    true AS receive_for_moderation,
    true AS provide_for_editing,
    true AS receive_for_editing
   FROM websites
  WHERE websites.deleted = false;