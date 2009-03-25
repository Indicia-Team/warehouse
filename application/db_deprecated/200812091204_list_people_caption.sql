DROP VIEW list_people;

CREATE OR REPLACE VIEW list_people AS 
 SELECT p.id, p.first_name, p.surname, p.initials, p.first_name||' '||p.surname as caption
   FROM people p;
