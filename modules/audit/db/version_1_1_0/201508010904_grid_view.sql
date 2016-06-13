--- use view to access the logged_actions table from ORM
CREATE VIEW logged_actions AS
SELECT *
FROM audit.logged_actions;
--- use view to access the logged_actions_websites table from ORM
CREATE VIEW logged_actions_websites AS
SELECT *
FROM audit.logged_actions_websites;

CREATE VIEW gv_logged_actions AS
SELECT min(la.id) as id,
    la.search_table_name,
    la.search_key,
    CASE la.action WHEN 'I' THEN 'insert' WHEN 'U' THEN 'update' WHEN 'D' THEN 'delete' WHEN 'T' THEN 'truncate' ELSE la.action END as action,
    la.action_tstamp_tx,
    u.username AS updated_by,
    la.transaction_id,
    law.website_id,
    w.title as website_title
FROM audit.logged_actions la
LEFT JOIN users u ON la.updated_by_id = u.id
LEFT JOIN audit.logged_actions_websites law ON law.logged_action_id = la.id
LEFT JOIN websites w ON law.website_id = w.id
GROUP BY la.search_table_name, la.search_key, la.action, la.action_tstamp_tx, u.username, la.transaction_id, law.website_id, w.title;
