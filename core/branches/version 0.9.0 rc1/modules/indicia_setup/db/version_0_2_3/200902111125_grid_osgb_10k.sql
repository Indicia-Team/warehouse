DROP TABLE IF EXISTS grids_osgb_10k;

CREATE TABLE grids_osgb_10k
(
  square character varying(20) NOT NULL,
  CONSTRAINT pk_osgb_10k PRIMARY KEY (square)
)
WITH (OIDS=FALSE);

SELECT AddGeometryColumn('', 'grids_osgb_10k','geom',27700,'POLYGON',2);


CREATE OR REPLACE FUNCTION grids_build_osgb_10k(square character(2)) RETURNS character varying AS
$BODY$
DECLARE north INTEGER;
DECLARE	east INTEGER;
DECLARE char1 character(1);
DECLARE char2ord INTEGER;
DECLARE x INTEGER;
DECLARE y INTEGER;
BEGIN

	north = 0;
	east = 0;
	char1 = substring(square from 1 for 1);
	IF char1='H' THEN
		north = north + 1000000;
	ELSE
		IF char1='N' THEN
			north=north+500000;
		ELSE
			IF char1='O' THEN
				north=north+500000;
				east = east + 500000;
			ELSE
				IF char1='T' THEN
					east = east + 500000;
				END IF;
			END IF;
		END IF;
	END IF;

	char2ord=ascii(substring(square from 2 for 1));
	IF char2ord > 73 THEN
		-- Adjust for no I
		char2ord = char2ord-1;
	END IF;
	east = east + ((char2ord - 65) % 5) * 100000;
	north = north + (4 - floor((char2ord - 65) / 5)) * 100000;

	-- We have got the x,y of the bottom left of the 100k square, so build the 10 k squares
	FOR x IN 0..9 LOOP
		FOR y IN 0..9 LOOP
			INSERT INTO grids_osgb_10k
			VALUES (
				square || CAST(x AS character varying) || CAST(y AS character varying),
				ST_GeomFromText(
					'POLYGON((' ||
					CAST(east + x * 10000 AS character varying) || ' ' || CAST(north + y * 10000 AS character varying) || ', ' ||
					CAST(east + x * 10000 AS character varying) || ' ' || CAST(north + (y+1) * 10000 AS character varying) || ', ' ||
					CAST(east + (x+1) * 10000 AS character varying) || ' ' || CAST(north + (y+1) * 10000 AS character varying) || ', ' ||
					CAST(east + (x+1) * 10000 AS character varying) || ' ' || CAST(north + y * 10000 AS character varying) || ', ' ||
					CAST(east + x * 10000 AS character varying) || ' ' || CAST(north + y * 10000 AS character varying) ||
					'))',27700)
			);
		END LOOP;
	END LOOP;


	RETURN CAST(east AS character varying) || ', ' || CAST(north AS character varying);

END
$BODY$
LANGUAGE 'plpgsql';

SELECT grids_build_osgb_10k('HP');
SELECT grids_build_osgb_10k('HT');
SELECT grids_build_osgb_10k('HU');
SELECT grids_build_osgb_10k('HW');
SELECT grids_build_osgb_10k('HX');
SELECT grids_build_osgb_10k('HY');
SELECT grids_build_osgb_10k('HZ');
SELECT grids_build_osgb_10k('NA');
SELECT grids_build_osgb_10k('NB');
SELECT grids_build_osgb_10k('NC');
SELECT grids_build_osgb_10k('ND');
SELECT grids_build_osgb_10k('NF');
SELECT grids_build_osgb_10k('NG');
SELECT grids_build_osgb_10k('NH');
SELECT grids_build_osgb_10k('NJ');
SELECT grids_build_osgb_10k('NK');
SELECT grids_build_osgb_10k('NL');
SELECT grids_build_osgb_10k('NM');
SELECT grids_build_osgb_10k('NN');
SELECT grids_build_osgb_10k('NO');
SELECT grids_build_osgb_10k('NR');
SELECT grids_build_osgb_10k('NS');
SELECT grids_build_osgb_10k('NT');
SELECT grids_build_osgb_10k('NU');
SELECT grids_build_osgb_10k('NW');
SELECT grids_build_osgb_10k('NX');
SELECT grids_build_osgb_10k('NY');
SELECT grids_build_osgb_10k('NZ');
SELECT grids_build_osgb_10k('OV');
SELECT grids_build_osgb_10k('SC');
SELECT grids_build_osgb_10k('SD');
SELECT grids_build_osgb_10k('SE');
SELECT grids_build_osgb_10k('TA');
SELECT grids_build_osgb_10k('SH');
SELECT grids_build_osgb_10k('SJ');
SELECT grids_build_osgb_10k('SK');
SELECT grids_build_osgb_10k('TF');
SELECT grids_build_osgb_10k('TG');
SELECT grids_build_osgb_10k('SM');
SELECT grids_build_osgb_10k('SN');
SELECT grids_build_osgb_10k('SO');
SELECT grids_build_osgb_10k('SP');
SELECT grids_build_osgb_10k('TL');
SELECT grids_build_osgb_10k('TM');
SELECT grids_build_osgb_10k('SR');
SELECT grids_build_osgb_10k('SS');
SELECT grids_build_osgb_10k('ST');
SELECT grids_build_osgb_10k('SU');
SELECT grids_build_osgb_10k('TQ');
SELECT grids_build_osgb_10k('TR');
SELECT grids_build_osgb_10k('SV');
SELECT grids_build_osgb_10k('SW');
SELECT grids_build_osgb_10k('SX');
SELECT grids_build_osgb_10k('SY');
SELECT grids_build_osgb_10k('SZ');
SELECT grids_build_osgb_10k('TV');

-- Index the view using GIST
CREATE INDEX ix_spatial_grids_osgb_10k ON grids_osgb_10k USING GIST(geom);

-- And a view to intersect the grid with occurrences data
DROP VIEW IF EXISTS grid_occurrences_osgb_10k;

CREATE VIEW grid_occurrences_osgb_10k AS
SELECT t.taxon, grid.square, grid.geom, o.id as occurrence_id, s.id as sample_id, ttl.id as taxa_taxon_list_id, tl.title as taxon_list
FROM occurrences o
INNER JOIN samples s on s.id=o.sample_id
INNER JOIN grids_osgb_10k grid on ST_INTERSECTS(grid.geom,ST_TRANSFORM(s.geom, 27700))
INNER JOIN taxa_taxon_lists ttl on ttl.id=o.taxa_taxon_list_id
INNER JOIN taxa t on t.id=ttl.taxon_id
INNER JOIN taxon_lists tl on tl.id=ttl.taxon_list_id;