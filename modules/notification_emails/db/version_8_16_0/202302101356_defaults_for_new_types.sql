-- Default Query and Redet notification frequency to same settings as for comments, since they are a subtype of comment.
INSERT INTO user_email_notification_settings (user_id, notification_source_type, notification_frequency, created_on, created_by_id, updated_on, updated_by_id)
SELECT t1.user_id, 'Q', t1.notification_frequency, t1.created_on, t1.created_by_id, t1.updated_on, t1.updated_by_id
FROM user_email_notification_settings t1
LEFT JOIN user_email_notification_settings t2 ON t2.user_id=t1.user_id AND t2.notification_source_type='Q' AND t2.deleted=false
WHERE t1.notification_source_type='C'
AND t1.deleted=false
AND t2.id IS NULL;

INSERT INTO user_email_notification_settings (user_id, notification_source_type, notification_frequency, created_on, created_by_id, updated_on, updated_by_id)
SELECT t1.user_id, 'RD', t1.notification_frequency, t1.created_on, t1.created_by_id, t1.updated_on, t1.updated_by_id
FROM user_email_notification_settings t1
LEFT JOIN user_email_notification_settings t2 ON t2.user_id=t1.user_id AND t2.notification_source_type='RD' AND t2.deleted=false
WHERE t1.notification_source_type='C'
AND t1.deleted=false
AND t2.id IS NULL;

INSERT INTO website_email_notification_settings (website_id, notification_source_type, notification_frequency, created_on, created_by_id, updated_on, updated_by_id)
SELECT t1.website_id, 'Q', t1.notification_frequency, t1.created_on, t1.created_by_id, t1.updated_on, t1.updated_by_id
FROM website_email_notification_settings t1
LEFT JOIN website_email_notification_settings t2 ON t2.website_id=t1.website_id AND t2.notification_source_type='Q' AND t2.deleted=false
WHERE t1.notification_source_type='C'
AND t1.deleted=false
AND t2.id IS NULL;

INSERT INTO website_email_notification_settings (website_id, notification_source_type, notification_frequency, created_on, created_by_id, updated_on, updated_by_id)
SELECT t1.website_id, 'RD', t1.notification_frequency, t1.created_on, t1.created_by_id, t1.updated_on, t1.updated_by_id
FROM website_email_notification_settings t1
LEFT JOIN website_email_notification_settings t2 ON t2.website_id=t1.website_id AND t2.notification_source_type='RD' AND t2.deleted=false
WHERE t1.notification_source_type='C'
AND t1.deleted=false
AND t2.id IS NULL;