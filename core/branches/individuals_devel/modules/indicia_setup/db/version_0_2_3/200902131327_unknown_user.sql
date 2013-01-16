INSERT INTO users (person_id, created_on, created_by_id, updated_on, updated_by_id, username)
VALUES ((SELECT id from people WHERE surname LIKE 'Unknown'), now(), 1, now(), 1, 'Unknown');