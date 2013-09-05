create or replace view gv_verification_rules as
select id, title, description, test_type
from verification_rules
where deleted=false;