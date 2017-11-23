

DROP SEQUENCE IF EXISTS verification_templates_id_seq CASCADE;

DROP TABLE IF EXISTS verification_templates CASCADE;

DELETE from system
  WHERE "name"='verification_templates';
