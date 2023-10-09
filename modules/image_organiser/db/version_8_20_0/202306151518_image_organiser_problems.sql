CREATE TABLE image_organiser_problems
(
  id serial NOT NULL,
  problem text,
  media_id integer,
  entity text,
  created_on timestamp without time zone NOT NULL, -- Date this record was created.
  created_by_id integer NOT NULL, -- Foreign key to the users table (creator).
  CONSTRAINT pk_image_organiser_problems PRIMARY KEY (id),
  CONSTRAINT fk_image_organiser_problems_creator FOREIGN KEY (created_by_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION
)
WITH (
  OIDS=FALSE
);