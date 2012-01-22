/**
A view which lists every website in the from_website_id, plus every website they have an agreement with in the to_website_id,
plus flags indicating what type of data they must share according to the agreement.
*/
CREATE OR REPLACE VIEW build_index_websites_website_agreements AS 
select wwafrom.website_id as from_website_id, wwato.website_id as to_website_id,
  case 
    when wwafrom.website_id=wwato.website_id then true 
    else 
      case wa.provide_for_reporting
        when 'D' then false
        when 'R' then true
        else wwafrom.provide_for_reporting
      end and case wa.receive_for_reporting
        when 'D' then false
        when 'R' then true
        else wwato.receive_for_reporting
      end
  end as provide_for_reporting,
  case 
    when wwafrom.website_id=wwato.website_id then true 
    else 
      case wa.receive_for_reporting
        when 'D' then false
        when 'R' then true
        else wwafrom.receive_for_reporting
      end and case wa.provide_for_reporting
        when 'D' then false
        when 'R' then true
        else wwato.provide_for_reporting
      end
  end as receive_for_reporting,
  case 
    when wwafrom.website_id=wwato.website_id then true 
    else 
      case wa.provide_for_peer_review
        when 'D' then false
        when 'R' then true
        else wwafrom.provide_for_peer_review
      end and case wa.receive_for_peer_review
        when 'D' then false
        when 'R' then true
        else wwato.receive_for_peer_review
      end
  end as provide_for_peer_review,
  case 
    when wwafrom.website_id=wwato.website_id then true 
    else 
      case wa.receive_for_peer_review
        when 'D' then false
        when 'R' then true
        else wwafrom.receive_for_peer_review
      end and case wa.provide_for_peer_review
        when 'D' then false
        when 'R' then true
        else wwato.provide_for_peer_review
      end
  end as receive_for_peer_review,
  case 
    when wwafrom.website_id=wwato.website_id then true 
    else 
      case wa.provide_for_verification
        when 'D' then false
        when 'R' then true
        else wwafrom.provide_for_verification
      end and case wa.receive_for_verification
        when 'D' then false
        when 'R' then true
        else wwato.receive_for_verification
      end
  end as provide_for_verification,
  case 
    when wwafrom.website_id=wwato.website_id then true 
    else 
      case wa.receive_for_verification
        when 'D' then false
        when 'R' then true
        else wwafrom.receive_for_verification
      end and case wa.provide_for_verification
        when 'D' then false
        when 'R' then true
        else wwato.provide_for_verification
      end
  end as receive_for_verification,
  case 
    when wwafrom.website_id=wwato.website_id then true 
    else 
      case wa.provide_for_data_flow
        when 'D' then false
        when 'R' then true
        else wwafrom.provide_for_data_flow
      end and case wa.receive_for_data_flow
        when 'D' then false
        when 'R' then true
        else wwato.receive_for_data_flow
      end
  end as provide_for_data_flow,
  case 
    when wwafrom.website_id=wwato.website_id then true 
    else 
      case wa.receive_for_data_flow
        when 'D' then false
        when 'R' then true
        else wwafrom.receive_for_data_flow
      end and case wa.provide_for_data_flow
        when 'D' then false
        when 'R' then true
        else wwato.provide_for_data_flow
      end
  end as receive_for_data_flow,
  case 
    when wwafrom.website_id=wwato.website_id then true 
    else 
      case wa.provide_for_moderation
        when 'D' then false
        when 'R' then true
        else wwafrom.provide_for_moderation
      end and case wa.receive_for_moderation
        when 'D' then false
        when 'R' then true
        else wwato.receive_for_moderation
      end
  end as provide_for_moderation,
  case 
    when wwafrom.website_id=wwato.website_id then true 
    else 
      case wa.receive_for_moderation
        when 'D' then false
        when 'R' then true
        else wwafrom.receive_for_moderation
      end and case wa.provide_for_moderation
        when 'D' then false
        when 'R' then true
        else wwato.provide_for_moderation
      end
  end as receive_for_moderation
from websites_website_agreements wwafrom
join website_agreements wa on wa.id=wwafrom.website_agreement_id and wa.deleted=false
join websites_website_agreements wwato on wwato.website_agreement_id=wa.id and wwato.deleted=false
where wwafrom.deleted=false;

COMMENT ON VIEW build_index_websites_website_agreements IS 'A view which lists every website in the from_website_id, plus every website they have an agreement with in the to_website_id, plus flags indicating what type of data they must share according to the agreement.';

CREATE TABLE index_websites_website_agreements
(
  id serial NOT NULL,
  from_website_id integer NOT NULL,
  to_website_id integer NOT NULL,
  provide_for_reporting boolean NOT NULL,
  receive_for_reporting boolean NOT NULL,
  provide_for_peer_review boolean NOT NULL,
  receive_for_peer_review boolean NOT NULL,
  provide_for_verification boolean NOT NULL,
  receive_for_verification boolean NOT NULL,
  provide_for_data_flow boolean NOT NULL,
  receive_for_data_flow boolean NOT NULL,
  provide_for_moderation boolean NOT NULL,
  receive_for_moderation boolean NOT NULL,
  CONSTRAINT pk_index_websites_website_agreements PRIMARY KEY (id)
)
WITH (
  OIDS=FALSE
);

COMMENT ON VIEW build_index_websites_website_agreements IS 'A view which lists every website in the from_website_id, plus every website they have an agreement with in the to_website_id, plus flags indicating what type of data they must share according to the agreement.';
COMMENT ON TABLE index_websites_website_agreements IS 'Lists every website in the from_website_id, plus every website they have an agreement with in the to_website_id, plus flags indicating what type of data they must share according to the agreement. Physical copy of the build_index_websites_website_agreement view since this saves a few joins in most report queries.';
COMMENT ON COLUMN index_websites_website_agreements.id IS 'Unique identifier for table.';
COMMENT ON COLUMN index_websites_website_agreements.from_website_id IS 'Partipating website\'s ID.';
COMMENT ON COLUMN index_websites_website_agreements.to_website_id IS 'ID of website being participated with.';
COMMENT ON COLUMN index_websites_website_agreements.provide_for_reporting IS 'Does the participating website provide data for reporting to the other?';
COMMENT ON COLUMN index_websites_website_agreements.receive_for_reporting IS 'Does the participating website receive data for reporting from the other?';
COMMENT ON COLUMN index_websites_website_agreements.provide_for_peer_review IS 'Does the participating website provide data for peer review to the other?';
COMMENT ON COLUMN index_websites_website_agreements.receive_for_peer_review IS 'Does the participating website receive data for peer review from the other?';
COMMENT ON COLUMN index_websites_website_agreements.provide_for_verification IS 'Does the participating website provide data for verification to the other?';
COMMENT ON COLUMN index_websites_website_agreements.receive_for_verification IS 'Does the participating website receive data for verification from the other?';
COMMENT ON COLUMN index_websites_website_agreements.provide_for_data_flow IS 'Does the participating website provide data for data flow to the other?';
COMMENT ON COLUMN index_websites_website_agreements.receive_for_data_flow IS 'Does the participating website receive data for data flow from the other?';
COMMENT ON COLUMN index_websites_website_agreements.provide_for_moderation IS 'Does the participating website provide data for moderation to the other?';
COMMENT ON COLUMN index_websites_website_agreements.receive_for_moderation IS 'Does the participating website receive data for moderation from the other?';


CREATE FUNCTION refresh_index_websites_website_agreements() RETURNS boolean AS $$
BEGIN
DELETE FROM index_websites_website_agreements i
WHERE NOT EXISTS(SELECT 1 FROM build_index_websites_website_agreements bi WHERE bi.from_website_id=i.from_website_id AND bi.to_website_id=i.to_website_id);

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
    receive_for_moderation=bi.receive_for_moderation
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
 OR i.receive_for_moderation<>bi.receive_for_moderation);
 
INSERT INTO index_websites_website_agreements 
SELECT nextval('index_websites_website_agreements_id_seq'::regclass), bi.from_website_id, bi.to_website_id, bi.provide_for_reporting, bi.receive_for_reporting,
    bi.provide_for_peer_review, bi.receive_for_peer_review, bi.provide_for_verification, bi.receive_for_verification,
    bi.provide_for_data_flow, bi.receive_for_data_flow, bi.provide_for_moderation, bi.receive_for_moderation
FROM build_index_websites_website_agreements bi
LEFT JOIN index_websites_website_agreements i ON i.from_website_id=bi.from_website_id AND i.to_website_id=bi.to_website_id
WHERE i.from_website_id IS NULL;

RETURN true;
END;
$$ LANGUAGE plpgsql;

COMMENT ON FUNCTION refresh_index_websites_website_agreements() IS 'A Function containing a script to refresh the contents of index_websites_website_agreements from the view build_index_websites_website_agreements.';