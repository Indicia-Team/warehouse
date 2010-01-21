DROP TABLE IF EXISTS grids_osgb_100k;

CREATE TABLE grids_osgb_100k
(
  square character varying(20) NOT NULL,
  CONSTRAINT pk_osgb_100k PRIMARY KEY (square)
)
WITH (OIDS=FALSE);

SELECT AddGeometryColumn('', 'grids_osgb_100k','geom',27700,'POLYGON',2);


CREATE OR REPLACE FUNCTION grids_build_osgb_100k(square character(2)) RETURNS character varying AS
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

	INSERT INTO grids_osgb_100k
	VALUES (
		square,
		ST_GeomFromText(
			'POLYGON((' ||
			CAST(east AS character varying) || ' ' || CAST(north AS character varying) || ', ' ||
			CAST(east AS character varying) || ' ' || CAST(north + 100000 AS character varying) || ', ' ||
			CAST(east + 100000 AS character varying) || ' ' || CAST(north + 100000 AS character varying) || ', ' ||
			CAST(east + 100000 AS character varying) || ' ' || CAST(north AS character varying) || ', ' ||
			CAST(east AS character varying) || ' ' || CAST(north AS character varying) ||
		'))',27700)
	);

	RETURN CAST(east AS character varying) || ', ' || CAST(north AS character varying);

END
$BODY$
LANGUAGE 'plpgsql';

SELECT grids_build_osgb_100k('HP');
SELECT grids_build_osgb_100k('HT');
SELECT grids_build_osgb_100k('HU');
SELECT grids_build_osgb_100k('HW');
SELECT grids_build_osgb_100k('HX');
SELECT grids_build_osgb_100k('HY');
SELECT grids_build_osgb_100k('HZ');
SELECT grids_build_osgb_100k('NA');
SELECT grids_build_osgb_100k('NB');
SELECT grids_build_osgb_100k('NC');
SELECT grids_build_osgb_100k('ND');
SELECT grids_build_osgb_100k('NF');
SELECT grids_build_osgb_100k('NG');
SELECT grids_build_osgb_100k('NH');
SELECT grids_build_osgb_100k('NJ');
SELECT grids_build_osgb_100k('NK');
SELECT grids_build_osgb_100k('NL');
SELECT grids_build_osgb_100k('NM');
SELECT grids_build_osgb_100k('NN');
SELECT grids_build_osgb_100k('NO');
SELECT grids_build_osgb_100k('NR');
SELECT grids_build_osgb_100k('NS');
SELECT grids_build_osgb_100k('NT');
SELECT grids_build_osgb_100k('NU');
SELECT grids_build_osgb_100k('NW');
SELECT grids_build_osgb_100k('NX');
SELECT grids_build_osgb_100k('NY');
SELECT grids_build_osgb_100k('NZ');
SELECT grids_build_osgb_100k('OV');
SELECT grids_build_osgb_100k('SC');
SELECT grids_build_osgb_100k('SD');
SELECT grids_build_osgb_100k('SE');
SELECT grids_build_osgb_100k('TA');
SELECT grids_build_osgb_100k('SH');
SELECT grids_build_osgb_100k('SJ');
SELECT grids_build_osgb_100k('SK');
SELECT grids_build_osgb_100k('TF');
SELECT grids_build_osgb_100k('TG');
SELECT grids_build_osgb_100k('SM');
SELECT grids_build_osgb_100k('SN');
SELECT grids_build_osgb_100k('SO');
SELECT grids_build_osgb_100k('SP');
SELECT grids_build_osgb_100k('TL');
SELECT grids_build_osgb_100k('TM');
SELECT grids_build_osgb_100k('SR');
SELECT grids_build_osgb_100k('SS');
SELECT grids_build_osgb_100k('ST');
SELECT grids_build_osgb_100k('SU');
SELECT grids_build_osgb_100k('TQ');
SELECT grids_build_osgb_100k('TR');
SELECT grids_build_osgb_100k('SV');
SELECT grids_build_osgb_100k('SW');
SELECT grids_build_osgb_100k('SX');
SELECT grids_build_osgb_100k('SY');
SELECT grids_build_osgb_100k('SZ');
SELECT grids_build_osgb_100k('TV');

-- Index the view using GIST
CREATE INDEX ix_spatial_grids_osgb_100k ON grids_osgb_100k USING GIST(geom);

-- And a view to intersect the grid with occurrences data
DROP VIEW IF EXISTS grid_occurrences_osgb_100k;

CREATE VIEW grid_occurrences_osgb_100k AS
SELECT t.taxon, grid.square, grid.geom, o.id as occurrence_id, s.id as sample_id, ttl.id as taxa_taxon_list_id, tl.title as taxon_list
FROM occurrences o
INNER JOIN samples s on s.id=o.sample_id
INNER JOIN grids_osgb_100k grid on ST_INTERSECTS(grid.geom,ST_TRANSFORM(s.geom, 27700))
INNER JOIN taxa_taxon_lists ttl on ttl.id=o.taxa_taxon_list_id
INNER JOIN taxa t on t.id=ttl.taxon_id
INNER JOIN taxon_lists tl on tl.id=ttl.taxon_list_id;