--- Ensure this module is active!

---
--- Tidy up
---
DROP FUNCTION IF EXISTS update_spipoll_cache_entry(integer, samples);
DROP FUNCTION IF EXISTS update_spipoll_cache_entry(integer, samples, boolean);
DROP FUNCTION IF EXISTS update_spipoll_cache(integer, text);
DROP FUNCTION IF EXISTS build_spipoll_cache(integer);

--- 1)	Extra field added to report table and associated view.

ALTER TABLE spipoll_insects_cache ADD COLUMN taxons_insecte_id_tool_decision_log text;
COMMENT ON COLUMN spipoll_insects_cache.taxons_insecte_id_tool_decision_log IS 'Details of decisions taken in the ID tool to arrive at the taxon(s). NULL if direct entry, ie tool not used.';

--- View has new column so can't just do a replace.
DROP VIEW spipoll_insects_cache_view;
CREATE VIEW spipoll_insects_cache_view AS
    SELECT * FROM spipoll_insects_cache ORDER BY closed DESC, insect_id;

--- 2)	Database code update to populate the field.

CREATE OR REPLACE FUNCTION update_spipoll_cache_entry(arg_collection_row samples, batchMode boolean) RETURNS void AS $$
DECLARE
 -- following should reflect values in Database: hardcoded.
 protocol_attr_id integer := 21;
 closed_attr_id  integer := 20;
 cms_userid_attr_id integer := 18;
 cms_username_attr_id integer := 19;
 flower_type_attr_id  integer := 3;
 front_page_attr_id  integer := 29;
 habitat_attr_id  integer := 1;
 hive_attr_id  integer := 2;
 within50m_attr_id  integer := 3;
 location_picture_camera_attr_id  integer := 4;
 location_picture_datetime_attr_id  integer := 5;
 email_attr_id  integer := 8;
 start_time_attr_id  integer := 22;
 end_time_attr_id  integer := 23;
 sky_attr_id  integer := 24;
 shade_attr_id  integer := 27;
 temp_attr_id  integer := 25;
 wind_attr_id  integer := 26;
 insect_foraging_attr_id  integer := 5;
 insect_number_attr_id  integer := 4;
 occurrence_picture_camera_attr_id  integer := 6;
 occurrence_picture_datetime_attr_id  integer := 7;
 ---
 mySrefSystem  integer := 27572;
 mySrefSystem2  integer := 4326;
 maxHistoricalDeterminations integer := 0; --- 0 = ALL
 ---
 rowsampleattributevalue sample_attribute_values%ROWTYPE;
 curlocation refcursor;
 rowlocation locations%ROWTYPE;
 rowlocationattributevalue location_attribute_values%ROWTYPE;
 curlocationimage refcursor;
 rowlocationimage location_images%ROWTYPE;
 curflower refcursor;
 rowflower occurrences%ROWTYPE;
 --- curdetermination refcursor;
 rowdetermination determinations%ROWTYPE;
 curflowerimage refcursor;
 rowflowerimage occurrence_images%ROWTYPE;
 rowoccurrenceattributevalue occurrence_attribute_values%ROWTYPE;
 rowsession  samples%ROWTYPE;
 rowinsect occurrences%ROWTYPE;
 --- curinsectdetermination refcursor;
 rowinsectdetermination determinations%ROWTYPE;
 curinsectimage refcursor;
 rowinsectimage occurrence_images%ROWTYPE;
 status    integer;
 ---
 cacherow   spipoll_collections_cache%ROWTYPE;
 cacheinsecttemplate1 spipoll_insects_cache%ROWTYPE;
 cacheinsecttemplate2 spipoll_insects_cache%ROWTYPE;
 updated    timestamp without time zone;
 sessionupdated  timestamp without time zone;
 temp    text;
 first    boolean;
 numHist    integer := 0;
 taxon_type   char(1);
BEGIN
  status := 2; -- 0 is open, 1 is closed, 2 is don't know
  cacherow.collection_id := arg_collection_row.id;
  cacheinsecttemplate1.collection_id := arg_collection_row.id;
  cacherow.datedebut := arg_collection_row.date_start;
  cacherow.datedebut_txt := to_char(arg_collection_row.date_start, 'YYYY-MM-DD');
  cacheinsecttemplate1.datedebut := arg_collection_row.date_start;
  cacheinsecttemplate1.datedebut_txt := cacherow.datedebut_txt;
  cacherow.datefin := arg_collection_row.date_end;
  cacherow.datefin_txt := to_char(arg_collection_row.date_end, 'YYYY-MM-DD');
  cacheinsecttemplate1.datefin := arg_collection_row.date_end;
  cacheinsecttemplate1.datefin_txt := cacherow.datefin_txt;
  updated := arg_collection_row.updated_on;
  cacherow.insect_search := '';

  DELETE FROM spipoll_collections_cache WHERE collection_id = arg_collection_row.id;
  DELETE FROM spipoll_insects_cache WHERE collection_id = arg_collection_row.id;

  --- Before we check the location image etc, we need to retrieve the collection attribute: proceed only if closed.
  FOR rowsampleattributevalue IN SELECT * FROM sample_attribute_values WHERE sample_id = arg_collection_row.id AND deleted = false ORDER BY id desc
  LOOP
   CASE rowsampleattributevalue.sample_attribute_id
    WHEN closed_attr_id THEN
     IF status = 2 THEN
      status := rowsampleattributevalue.int_value;
     ELSIF batchMode = true THEN
      RAISE WARNING 'Multiple Closed Attributes for Collection ID --> %, ignored Attr ID --> %', arg_collection_row.id, rowsampleattributevalue.id ;
     END IF;
     cacherow.closed = rowsampleattributevalue.updated_on;
     cacheinsecttemplate1.closed = rowsampleattributevalue.updated_on;
    WHEN cms_username_attr_id THEN
     IF cacherow.username IS NULL THEN
      cacherow.username := rowsampleattributevalue.text_value;
      cacheinsecttemplate1.username :=rowsampleattributevalue.text_value;
     ELSIF batchMode = true THEN
      RAISE WARNING 'Multiple Username Attributes for Collection ID --> %, ignored Attr ID --> %', arg_collection_row.id, rowsampleattributevalue.id ;
     END IF;
    WHEN cms_userid_attr_id THEN
     IF cacheinsecttemplate1.userid IS NULL THEN
      cacheinsecttemplate1.userid := rowsampleattributevalue.int_value;
     ELSIF batchMode = true THEN
      RAISE WARNING 'Multiple UserID Attributes for Collection ID --> %, ignored Attr ID --> %', arg_collection_row.id, rowsampleattributevalue.id ;
     END IF;
    WHEN email_attr_id THEN
     IF cacheinsecttemplate1.email IS NULL THEN
      cacheinsecttemplate1.email := rowsampleattributevalue.text_value;
     ELSIF batchMode = true THEN
      RAISE WARNING 'Multiple Email Attributes for Collection ID --> %, ignored Attr ID --> %', arg_collection_row.id, rowsampleattributevalue.id ;
     END IF;
    WHEN protocol_attr_id THEN
     IF cacheinsecttemplate1.protocol IS NULL THEN
      cacheinsecttemplate1.protocol := spipoll_get_term(rowsampleattributevalue.int_value);
      IF position('(' in cacheinsecttemplate1.protocol) <> 0 THEN
       cacheinsecttemplate1.protocol := substring(cacheinsecttemplate1.protocol for (position('(' in cacheinsecttemplate1.protocol)-2));
      END IF;
     ELSIF batchMode = true THEN
      RAISE WARNING 'Multiple Protocol Attributes for Collection ID --> %, ignored Attr ID --> %', arg_collection_row.id, rowsampleattributevalue.id ;
     END IF;
    WHEN front_page_attr_id THEN
    ELSE
     IF batchMode = true THEN
      RAISE WARNING 'Unrecognised Collection Attribute, Collection ID --> %, SA ID --> % in SAV ID --> %: ignored', arg_collection_row.id, rowsampleattributevalue.sample_attribute_id, rowsampleattributevalue.id ;
     END IF;
   END CASE;
   IF rowsampleattributevalue.updated_on > updated THEN
    updated := rowsampleattributevalue.updated_on;
   END IF;
  END LOOP;
  CASE status
   WHEN 1 THEN
    OPEN curlocation FOR SELECT * FROM locations WHERE id = arg_collection_row.location_id AND deleted = false ORDER BY id DESC;
    FETCH curlocation INTO rowlocation;
    IF FOUND THEN
     cacherow.nom := rowlocation.name;
     cacheinsecttemplate1.nom := rowlocation.name;
     cacherow.geom := rowlocation.centroid_geom;
     cacheinsecttemplate1.geom := rowlocation.centroid_geom;
     IF rowlocation.updated_on > updated THEN
      updated := rowlocation.updated_on;
     END IF;
     SELECT ST_asText(ST_Transform(cacherow.geom,mySrefSystem)) INTO temp;
     temp := trim(trailing ')' from trim(leading 'POINT(' from temp));
     cacherow.srefX = substring(temp for (position(' ' in temp) - 1));
     cacherow.srefY = substring(temp from (position(' ' in temp) + 1));
     cacheinsecttemplate1.srefX = cacherow.srefX;
     cacheinsecttemplate1.srefY = cacherow.srefY;
     --- The next line is a bodge: without it, repeated calls with 4326 will fail.
     SELECT ST_asText(ST_Transform(cacherow.geom,4267)) INTO temp;
     SELECT ST_asText(ST_Transform(cacherow.geom,mySrefSystem2)) INTO temp;
     temp := trim(trailing ')' from trim(leading 'POINT(' from temp));
     cacherow.long = substring(temp for (position(' ' in temp) - 1));
     cacherow.lat = substring(temp from (position(' ' in temp) + 1));
     cacheinsecttemplate1.long = cacherow.long;
     cacheinsecttemplate1.lat = cacherow.lat;

     FOR rowlocationattributevalue IN SELECT * FROM location_attribute_values WHERE location_id = rowlocation.id AND deleted = false ORDER BY id desc
     LOOP
      CASE rowlocationattributevalue.location_attribute_id
       WHEN habitat_attr_id THEN --- multiple values separated by |
        IF rowlocationattributevalue.int_value > 0 THEN --- zero used to indicate entry was unchecked - ie not selected - so ignore.
         IF cacheinsecttemplate1.habitat_ids IS NULL THEN
          cacheinsecttemplate1.habitat_ids := '|' || rowlocationattributevalue.int_value || '|';
          cacheinsecttemplate1.habitat := spipoll_get_term(rowlocationattributevalue.int_value);
         ELSE
          cacheinsecttemplate1.habitat_ids := cacheinsecttemplate1.habitat_ids || rowlocationattributevalue.int_value || '|';
          cacheinsecttemplate1.habitat := cacheinsecttemplate1.habitat || ',' || spipoll_get_term(rowlocationattributevalue.int_value);
         END IF;
        END IF;
       WHEN hive_attr_id THEN
        IF cacheinsecttemplate1.nearest_hive IS NULL THEN
         cacheinsecttemplate1.nearest_hive := rowlocationattributevalue.int_value;
        ELSIF batchMode = true THEN
         RAISE WARNING 'Multiple Hive Distance Attributes for Collection ID --> %, Location ID --> %, ignored Attr ID --> %', arg_collection_row.id, rowlocation.id, rowlocationattributevalue.id ;
        END IF;
       WHEN within50m_attr_id THEN
        IF cacheinsecttemplate1.within50m IS NULL THEN
         IF rowlocationattributevalue.int_value = 0 THEN
          cacheinsecttemplate1.within50m := 'Non';
         ELSE
          cacheinsecttemplate1.within50m := 'Oui';
         END IF;
        ELSIF batchMode = true THEN
         RAISE WARNING 'Multiple Within50m Attributes for Collection ID --> %, Location ID --> %, ignored Attr ID --> %', arg_collection_row.id, rowlocation.id, rowlocationattributevalue.id ;
        END IF;
       WHEN location_picture_camera_attr_id THEN
        IF cacheinsecttemplate1.image_de_environment_camera IS NULL THEN
         cacheinsecttemplate1.image_de_environment_camera := rowlocationattributevalue.text_value;
        ELSIF batchMode = true THEN
         RAISE WARNING 'Multiple Location Picture Camera Attributes for Collection ID --> %, Location ID --> %, ignored Attr ID --> %', arg_collection_row.id, rowlocation.id, rowlocationattributevalue.id ;
        END IF;
       WHEN location_picture_datetime_attr_id THEN
        IF cacheinsecttemplate1.image_de_environment_datetime IS NULL THEN
         cacheinsecttemplate1.image_de_environment_datetime := rowlocationattributevalue.text_value;
        ELSIF batchMode = true THEN
         RAISE WARNING 'Multiple Location Picture Datetime Attributes for Collection ID --> %, Location ID --> %, ignored Attr ID --> %', arg_collection_row.id, rowlocation.id, rowlocationattributevalue.id ;
        END IF;
       ELSE
        IF batchMode = true THEN
         RAISE WARNING 'Unrecognised Location Attribute, Location ID --> %, LA ID --> % in LAV ID --> %: ignored', rowlocation.id, rowlocationattributevalue.location_attribute_id, rowlocationattributevalue.id ;
        END IF;
      END CASE;
      IF rowlocationattributevalue.updated_on > updated THEN
       updated := rowlocationattributevalue.updated_on;
      END IF;
     END LOOP;
     cacherow.habitat_ids := cacheinsecttemplate1.habitat_ids;
     OPEN curlocationimage FOR SELECT * FROM location_images WHERE location_id = rowlocation.id AND deleted = false ORDER BY id DESC;
     FETCH curlocationimage INTO rowlocationimage;
     IF FOUND THEN
      cacherow.image_de_environment = rowlocationimage.path;
      cacheinsecttemplate1.image_de_environment = rowlocationimage.path;
      IF rowlocationimage.updated_on > updated THEN
       updated := rowlocationimage.updated_on;
      END IF;
      FETCH curlocationimage INTO rowlocationimage;
      IF FOUND AND batchMode = true THEN
       RAISE WARNING 'Multiple Locations Images on Location ID --> %, only most recent location used, ignoring ID --> %', rowlocation.id, rowlocationimage.id ;
      END IF;
      OPEN curflower FOR SELECT * FROM occurrences WHERE sample_id = arg_collection_row.id AND deleted = false ORDER BY id DESC;
      FETCH curflower INTO rowflower;
      IF FOUND THEN
       IF rowflower.updated_on > updated THEN
        updated := rowflower.updated_on;
       END IF;
       cacherow.flower_id = rowflower.id;
       FOR rowoccurrenceattributevalue IN SELECT * FROM occurrence_attribute_values WHERE occurrence_id = rowflower.id AND deleted = false ORDER BY id desc
       LOOP
        CASE rowoccurrenceattributevalue.occurrence_attribute_id
         WHEN flower_type_attr_id THEN
          IF cacherow.flower_type_id IS NULL THEN
           cacherow.flower_type_id = rowoccurrenceattributevalue.int_value;
           cacheinsecttemplate1.flower_type_id = rowoccurrenceattributevalue.int_value;
           cacheinsecttemplate1.flower_type = spipoll_get_term(rowoccurrenceattributevalue.int_value);
          ELSIF batchMode = true THEN
           RAISE WARNING 'Multiple Flower Type Attributes for Flower ID --> %, ignored Attr ID --> %', rowflower.id, rowoccurrenceattributevalue.id ;
          END IF;
         WHEN occurrence_picture_camera_attr_id THEN
          IF cacheinsecttemplate1.image_de_la_fleur_camera IS NULL THEN
           cacheinsecttemplate1.image_de_la_fleur_camera := rowoccurrenceattributevalue.text_value;
          ELSIF batchMode = true THEN
           RAISE WARNING 'Multiple Flower Picture Camera Attributes for Flower ID --> %, ignored Attr ID --> %', rowflower.id, rowoccurrenceattributevalue.id ;
          END IF;
         WHEN occurrence_picture_datetime_attr_id THEN
          IF cacheinsecttemplate1.image_de_la_fleur_datetime IS NULL THEN
           cacheinsecttemplate1.image_de_la_fleur_datetime := rowoccurrenceattributevalue.text_value;
          ELSIF batchMode = true THEN
           RAISE WARNING 'Multiple Flower Picture Datetime Attributes for Flower ID --> %, ignored Attr ID --> %', rowflower.id, rowoccurrenceattributevalue.id ;
          END IF;
         ELSE
          IF batchMode = true THEN
           RAISE WARNING 'Unrecognised Flower Attribute, Flower ID --> %, OA ID --> % in OAV ID --> %: ignored', rowflower.id, rowoccurrenceattributevalue.occurrence_attribute_id, rowoccurrenceattributevalue.id ;
          END IF;
        END CASE;
        IF rowoccurrenceattributevalue.updated_on > updated THEN
         updated := rowoccurrenceattributevalue.updated_on;
        END IF;
       END LOOP;
       first := true;
       numHist := 0;
       cacherow.status_fleur_code := 'X';
       FOR rowdetermination IN SELECT * FROM determinations WHERE occurrence_id = rowflower.id AND deleted = false ORDER BY id desc LOOP
        IF rowdetermination.updated_on > updated THEN
         updated := rowdetermination.updated_on;
        END IF;
        IF first THEN
         --- flower treated slightly differently to insect - there must always be only one flower and it must have a determination
         IF rowdetermination.determination_type <> 'X' THEN --- identified
          IF rowdetermination.taxa_taxon_list_id IS NOT NULL THEN
           cacherow.flower_taxon_ids := '|'||rowdetermination.taxa_taxon_list_id||'|';
           cacherow.flower_taxon_type := 'seul';
          ELSE
           cacherow.flower_taxon_ids := ARRAY(select '|'||unnest(rowdetermination.taxa_taxon_list_id_list)::text||'|')::text;
           IF array_length(rowdetermination.taxa_taxon_list_id_list, 1) < 2 THEN
            cacherow.flower_taxon_type := 'seul';
           ELSE
            cacherow.flower_taxon_type := 'multi';
           END IF;
          END IF;
          cacheinsecttemplate1.flower_taxon_type := cacherow.flower_taxon_type;
          cacheinsecttemplate1.flower_taxon_ids := cacherow.flower_taxon_ids;
          cacheinsecttemplate1.flower_taxon := spipoll_get_taxon_details(rowdetermination.taxa_taxon_list_id, rowdetermination.taxa_taxon_list_id_list);
         ELSE
          cacherow.flower_taxon_ids := '||';
          cacherow.flower_taxon_type := '';
          cacheinsecttemplate1.flower_taxon_type := cacherow.flower_taxon_type;
          cacheinsecttemplate1.flower_taxon_ids := cacherow.flower_taxon_ids;
          cacheinsecttemplate1.flower_taxon := '';
         END IF;
         cacherow.taxons_fleur_precise := rowdetermination.taxon_extra_info;
         cacheinsecttemplate1.taxons_fleur_precise := cacherow.taxons_fleur_precise;
         cacheinsecttemplate1.status_fleur_giver := rowdetermination.person_name::text;
         cacheinsecttemplate1.status_fleur := spipoll_get_determination_details(rowdetermination.determination_type, NULL, rowdetermination.updated_on::date);
         cacherow.status_fleur_code := rowdetermination.determination_type;
         cacheinsecttemplate1.status_fleur_code := rowdetermination.determination_type;
        ELSE
         IF maxHistoricalDeterminations = 0 OR numHist < maxHistoricalDeterminations THEN
          IF cacheinsecttemplate1.fleur_historical_taxon IS NULL THEN
           cacheinsecttemplate1.fleur_historical_taxon := '{{'||spipoll_get_determination_details(rowdetermination.determination_type, rowdetermination.person_name::text, rowdetermination.updated_on::date)||'},{'||spipoll_get_taxon_details(rowdetermination.taxa_taxon_list_id, rowdetermination.taxa_taxon_list_id_list)||'}}';
          ELSE
           cacheinsecttemplate1.fleur_historical_taxon := cacheinsecttemplate1.fleur_historical_taxon||',{{'||spipoll_get_determination_details(rowdetermination.determination_type, rowdetermination.person_name::text, rowdetermination.updated_on::date)||'},{'||spipoll_get_taxon_details(rowdetermination.taxa_taxon_list_id, rowdetermination.taxa_taxon_list_id_list)||'}}';
          END IF;
          numHist := numHist+1;
         END IF;
        END IF;
        first = false;
       END LOOP;
       IF first=false THEN
        OPEN curflowerimage FOR SELECT * FROM occurrence_images WHERE occurrence_id = rowflower.id AND deleted = false ORDER BY id DESC;
        FETCH curflowerimage INTO rowflowerimage;
        IF FOUND THEN
         IF rowflowerimage.updated_on > updated THEN
          updated := rowflowerimage.updated_on;
         END IF;
         cacherow.updated = updated;
         cacherow.image_de_la_fleur = rowflowerimage.path;
         cacheinsecttemplate1.image_de_la_fleur = rowflowerimage.path;
         FETCH curflowerimage INTO rowflowerimage;
         IF FOUND AND batchMode = true THEN
          RAISE WARNING 'Multiple Flower Images on Flower ID --> %, only most recent image used, ignoring ID --> %', rowflower.id, rowflowerimage.id ;
         END IF;
         FOR rowsession IN SELECT * FROM samples WHERE parent_id = arg_collection_row.id AND deleted = false
         LOOP
          cacheinsecttemplate2 := cacheinsecttemplate1;
          cacheinsecttemplate2.date_de_session = rowsession.date_start;
          --- multiple sessions mean multiple values in collection.
          IF rowsession.updated_on > updated THEN
           sessionupdated := rowsession.updated_on;
          ELSE
           sessionupdated := updated;
          END IF;
          FOR rowsampleattributevalue IN SELECT * FROM sample_attribute_values WHERE sample_id = rowsession.id AND deleted = false ORDER BY id desc
          LOOP
           CASE rowsampleattributevalue.sample_attribute_id
            WHEN sky_attr_id THEN
             IF cacherow.sky_ids IS NULL THEN
              cacherow.sky_ids := '|' || rowsampleattributevalue.int_value || '|';
             ELSE
              cacherow.sky_ids := cacherow.sky_ids || rowsampleattributevalue.int_value || '|';
             END IF;
             IF cacheinsecttemplate2.sky_ids IS NULL THEN
              cacheinsecttemplate2.sky_ids := '|' || rowsampleattributevalue.int_value || '|';
              cacheinsecttemplate2.ciel := spipoll_get_term(rowsampleattributevalue.int_value);
             ELSIF batchMode = true THEN
              RAISE WARNING 'Multiple Sky Attributes for Session ID --> %, ignored Attr ID --> %', rowsession.id, rowsampleattributevalue.id ;
             END IF;
            WHEN shade_attr_id THEN
             IF cacherow.shade_ids IS NULL THEN
              cacherow.shade_ids := '|' || rowsampleattributevalue.int_value || '|';
             ELSE
              cacherow.shade_ids := cacherow.shade_ids || rowsampleattributevalue.int_value || '|';
             END IF;
             IF cacheinsecttemplate2.shade_ids IS NULL THEN
              cacheinsecttemplate2.shade_ids := '|' || rowsampleattributevalue.int_value || '|';
              IF rowsampleattributevalue.int_value = 0 THEN
               cacheinsecttemplate2.fleur_a_lombre := 'Non';
              ELSE
               cacheinsecttemplate2.fleur_a_lombre := 'Oui';
              END IF;
             ELSIF batchMode = true THEN
              RAISE WARNING 'Multiple Shade Attributes for Session ID --> %, ignored Attr ID --> %', rowsession.id, rowsampleattributevalue.id ;
             END IF;
            WHEN temp_attr_id THEN
             IF cacherow.temp_ids IS NULL THEN
              cacherow.temp_ids := '|' || rowsampleattributevalue.int_value || '|';
             ELSE
              cacherow.temp_ids := cacherow.temp_ids || rowsampleattributevalue.int_value || '|';
             END IF;
             IF cacheinsecttemplate2.temp_ids IS NULL THEN
              cacheinsecttemplate2.temp_ids := '|' || rowsampleattributevalue.int_value || '|';
              cacheinsecttemplate2.temperature := spipoll_get_term(rowsampleattributevalue.int_value);
             ELSIF batchMode = true THEN
              RAISE WARNING 'Multiple Temp Attributes for Session ID --> %, ignored Attr ID --> %', rowsession.id, rowsampleattributevalue.id ;
             END IF;
            WHEN wind_attr_id THEN
             IF cacherow.wind_ids IS NULL THEN
              cacherow.wind_ids := '|' || rowsampleattributevalue.int_value || '|';
             ELSE
              cacherow.wind_ids := cacherow.wind_ids || rowsampleattributevalue.int_value || '|';
             END IF;
             IF cacheinsecttemplate2.wind_ids IS NULL THEN
              cacheinsecttemplate2.wind_ids := '|' || rowsampleattributevalue.int_value || '|';
              cacheinsecttemplate2.vent := spipoll_get_term(rowsampleattributevalue.int_value);
             ELSIF batchMode = true THEN
              RAISE WARNING 'Multiple Wind Attributes for Session ID --> %, ignored Attr ID --> %', rowsession.id, rowsampleattributevalue.id ;
             END IF;
            WHEN start_time_attr_id THEN
             IF cacheinsecttemplate2.starttime IS NULL THEN
              cacheinsecttemplate2.starttime := rowsampleattributevalue.text_value;
             ELSIF batchMode = true THEN
              RAISE WARNING 'Multiple Start Time Attributes for Session ID --> %, ignored Attr ID --> %', rowsession.id, rowsampleattributevalue.id ;
             END IF;
            WHEN end_time_attr_id THEN
             IF cacheinsecttemplate2.endtime IS NULL THEN
              cacheinsecttemplate2.endtime := rowsampleattributevalue.text_value;
             ELSIF batchMode = true THEN
              RAISE WARNING 'Multiple Start Time Attributes for Session ID --> %, ignored Attr ID --> %', rowsession.id, rowsampleattributevalue.id ;
             END IF;
            ELSE
             IF batchMode = true THEN
              RAISE WARNING 'Unrecognised Session Attribute, Session ID --> %, SA ID --> % in SAV ID --> %: ignored', rowsession.id, rowsampleattributevalue.sample_attribute_id, rowsampleattributevalue.id ;
             END IF;
           END CASE;
           IF rowsampleattributevalue.updated_on > sessionupdated THEN
            sessionupdated := rowsampleattributevalue.updated_on;
           END IF;
          END LOOP;
          FOR rowinsect IN SELECT * FROM occurrences WHERE sample_id = rowsession.id AND deleted = false
          LOOP
            DECLARE
           cacheinsectrow spipoll_insects_cache%ROWTYPE;
           notOnFlower char(1);
            BEGIN
            cacheinsectrow := cacheinsecttemplate2;
           cacheinsectrow.insect_id := rowinsect.id;
           cacheinsectrow.geom := cacherow.geom;
           cacheinsectrow.updated := sessionupdated;
           notOnFlower := '0';
           --- with V3_2 we now search more details on insects, so we have to be canny about the data stored in the
           --- cacherow. all the queries have to be anded, plus we are very restricted by what the ogc like filter
           --- can do. Each entry is of the format |<status: XABC>:<type: MS>:<notOnflower:YN>:<taxa_taxon_list_id>|
           --- cacherow.insect_taxon_ids is renamed to cacherow.insect_details
           FOR rowoccurrenceattributevalue IN SELECT * FROM occurrence_attribute_values WHERE occurrence_id = rowinsect.id AND deleted = false ORDER BY id desc
           LOOP
            CASE rowoccurrenceattributevalue.occurrence_attribute_id
             WHEN insect_number_attr_id THEN
              IF cacheinsectrow.number_insect IS NULL THEN
               cacheinsectrow.number_insect := spipoll_get_term(rowoccurrenceattributevalue.int_value);
              ELSE
               RAISE WARNING 'Multiple Insect Number Attributes for Insect ID --> %, ignored Attr ID --> %', rowinsect.id, rowoccurrenceattributevalue.id ;
              END IF;
             WHEN insect_foraging_attr_id THEN
              IF cacheinsectrow.notonaflower IS NULL THEN
               IF cacherow.notonaflower_ids IS NULL THEN
                cacherow.notonaflower_ids := '|' || rowoccurrenceattributevalue.int_value || '|';
               ELSE
                cacherow.notonaflower_ids := cacherow.notonaflower_ids || rowoccurrenceattributevalue.int_value || '|';
               END IF;
               cacheinsectrow.notonaflower_id = rowoccurrenceattributevalue.int_value;
               IF rowoccurrenceattributevalue.int_value = 0 THEN
                cacheinsectrow.notonaflower := 'Non';
               ELSE
                notOnFlower := '1';
                cacheinsectrow.notonaflower := 'Oui';
               END IF;
              ELSIF batchMode = true THEN
               RAISE WARNING 'Multiple Foraging Attributes for Insect ID --> %, ignored Attr ID --> %', rowinsect.id, rowoccurrenceattributevalue.id ;
              END IF;
             WHEN occurrence_picture_camera_attr_id THEN
              IF cacheinsectrow.image_d_insecte_camera IS NULL THEN
               cacheinsectrow.image_d_insecte_camera := rowoccurrenceattributevalue.text_value;
              ELSIF batchMode = true THEN
               RAISE WARNING 'Multiple Insect Picture Camera Attributes for Insect ID --> %, ignored Attr ID --> %', rowinsect.id, rowoccurrenceattributevalue.id ;
              END IF;
             WHEN occurrence_picture_datetime_attr_id THEN
              IF cacheinsectrow.image_d_insecte_datetime IS NULL THEN
               cacheinsectrow.image_d_insecte_datetime := rowoccurrenceattributevalue.text_value;
              ELSIF batchMode = true THEN
               RAISE WARNING 'Multiple Insect Picture Datetime Attributes for Insect ID --> %, ignored Attr ID --> %', rowinsect.id, rowoccurrenceattributevalue.id ;
              END IF;
             ELSE
              IF batchMode = true THEN
               RAISE WARNING 'Unrecognised Insect Attribute, Insect ID --> %, OA ID --> % in OAV ID --> %: ignored', rowinsect.id, rowoccurrenceattributevalue.occurrence_attribute_id, rowoccurrenceattributevalue.id ;
              END IF;
            END CASE;
            IF rowoccurrenceattributevalue.updated_on > cacheinsectrow.updated THEN
             cacheinsectrow.updated := rowoccurrenceattributevalue.updated_on;
            END IF;
           END LOOP;
           cacheinsectrow.status_insecte_code := 'X';
           first := true;
           numHist := 0;
           FOR rowinsectdetermination IN SELECT * FROM determinations WHERE occurrence_id = rowinsect.id AND deleted = false ORDER BY id desc LOOP
            IF rowinsectdetermination.updated_on > cacheinsectrow.updated THEN
             cacheinsectrow.updated := rowinsectdetermination.updated_on;
            END IF;
            IF first THEN
             IF rowinsectdetermination.determination_type <> 'X' THEN --- identified
              cacheinsectrow.insect_taxon := spipoll_get_taxon_details(rowinsectdetermination.taxa_taxon_list_id, rowinsectdetermination.taxa_taxon_list_id_list);
              IF rowinsectdetermination.taxa_taxon_list_id IS NOT NULL THEN
               cacheinsectrow.insect_taxon_ids := '|'||rowinsectdetermination.taxa_taxon_list_id||'|';
               cacheinsectrow.insect_taxon_type := 'seul';
               cacherow.insect_search := cacherow.insect_search||'|'||rowinsectdetermination.determination_type||':S:'||notOnFlower||':'||to_char(rowinsectdetermination.taxa_taxon_list_id,'FM09999')||'|';
              ELSE
               taxon_type := 'S';
               cacheinsectrow.insect_taxon_ids := ARRAY(select '|'||unnest(rowinsectdetermination.taxa_taxon_list_id_list)::text||'|')::text;
               IF array_length(rowinsectdetermination.taxa_taxon_list_id_list, 1) < 2 THEN
                cacheinsectrow.insect_taxon_type := 'seul';
               ELSE
                taxon_type := 'M';
                cacheinsectrow.insect_taxon_type := 'multi';
               END IF;
               cacherow.insect_search := cacherow.insect_search||ARRAY(select '|'||rowinsectdetermination.determination_type||':'||taxon_type||':'||notOnFlower||':'||to_char(unnest(rowinsectdetermination.taxa_taxon_list_id_list),'FM09999')||'|')::text;
              END IF;
              IF cacherow.insect_taxon_ids IS NULL THEN
               cacherow.insect_taxon_ids := cacheinsectrow.insect_taxon_ids;
              ELSE
               cacherow.insect_taxon_ids := cacherow.insect_taxon_ids||cacheinsectrow.insect_taxon_ids;
              END IF;
             ELSE
              cacherow.insect_search := cacherow.insect_search||'|X: :'||notOnFlower||':00000|';
             END IF;
             cacheinsectrow.status_insecte_giver := rowinsectdetermination.person_name::text;
             cacheinsectrow.status_insecte := spipoll_get_determination_details(rowinsectdetermination.determination_type, NULL, rowinsectdetermination.updated_on::date);
             cacheinsectrow.status_insecte_code := rowinsectdetermination.determination_type;
             IF cacherow.status_insecte_code IS NULL THEN
              cacherow.status_insecte_code := cacheinsectrow.status_insecte_code; -- this may get deleted
             ELSE
              cacherow.status_insecte_code := cacherow.status_insecte_code||cacheinsectrow.status_insecte_code;
             END IF;
             cacheinsectrow.taxons_insecte_precise := rowinsectdetermination.taxon_extra_info;
             cacheinsectrow.taxons_insecte_id_tool_decision_log := rowinsectdetermination.taxon_details;
             IF cacherow.taxons_insecte_precise IS NULL THEN
              cacherow.taxons_insecte_precise := '|' || rowinsectdetermination.taxon_extra_info || '|';
             ELSE
              cacherow.taxons_insecte_precise := cacherow.taxons_insecte_precise || rowinsectdetermination.taxon_extra_info || '|';
             END IF;
            ELSE
             IF maxHistoricalDeterminations = 0 OR numHist < maxHistoricalDeterminations THEN
              IF cacheinsectrow.insect_historical_taxon IS NULL THEN
               cacheinsectrow.insect_historical_taxon := '{{'||spipoll_get_determination_details(rowinsectdetermination.determination_type, rowinsectdetermination.person_name::text, rowinsectdetermination.updated_on::date)||'},{'||spipoll_get_taxon_details(rowinsectdetermination.taxa_taxon_list_id, rowinsectdetermination.taxa_taxon_list_id_list)||'}}';
              ELSE
               cacheinsectrow.insect_historical_taxon := cacheinsectrow.insect_historical_taxon||',{{'||spipoll_get_determination_details(rowinsectdetermination.determination_type, rowinsectdetermination.person_name::text, rowinsectdetermination.updated_on::date)||'},{'||spipoll_get_taxon_details(rowinsectdetermination.taxa_taxon_list_id, rowinsectdetermination.taxa_taxon_list_id_list)||'}}';
              END IF;
              numHist := numHist+1;
             END IF;
            END IF;
            first = false;
           END LOOP;
           OPEN curinsectimage FOR SELECT * FROM occurrence_images WHERE occurrence_id = rowinsect.id AND deleted = false ORDER BY id DESC;
           FETCH curinsectimage INTO rowinsectimage;
           IF FOUND THEN
            cacheinsectrow.image_d_insecte = rowinsectimage.path;
            IF rowinsectimage.updated_on > cacheinsectrow.updated THEN
             cacheinsectrow.updated := rowinsectimage.updated_on;
            END IF;
            IF cacheinsectrow.updated > cacherow.updated THEN
             cacherow.updated := cacheinsectrow.updated;
            END IF;
            FETCH curinsectimage INTO rowinsectimage;
            IF FOUND AND batchMode = true THEN
             RAISE WARNING 'Multiple Insect Images on Insect ID --> %, only most recent image used, ignoring ID --> %', rowinsect.id, rowinsectimage.id ;
            END IF;
            ---------------------------------
            INSERT INTO spipoll_insects_cache SELECT cacheinsectrow.*;
            ---------------------------------
           END IF;
           CLOSE curinsectimage;
            END;
          END LOOP;
         END LOOP;
         ---------------------------------
         INSERT INTO spipoll_collections_cache SELECT cacherow.*;
         ---------------------------------
        ELSIF batchMode = true THEN
         RAISE WARNING 'Could not find Flower Image for Flower ID --> %, Collection % not cached', rowflower.id, arg_collection_row.id ;
        END IF;
        CLOSE curflowerimage;
       ELSIF batchMode = true THEN
        RAISE WARNING 'Could not find Determination for Flower ID --> %, Collection % not cached', rowflower.id, arg_collection_row.id ;
       END IF;
       FETCH curflower INTO rowflower;
       IF FOUND AND batchMode = true THEN
        RAISE WARNING 'Multiple Flowers on Collection ID --> %, only most recent flower used, ignoring ID --> %', arg_collection_row.id, rowflower.id ;
       END IF;
      ELSIF batchMode = true THEN
       RAISE WARNING 'Could not find Flower for Collection ID --> %, Collection not cached', arg_collection_row.id ;
      END IF;
      CLOSE curflower;
     ELSIF batchMode = true THEN
      RAISE WARNING 'Could not find Location Image for Location ID --> %, Collection % not cached', rowlocation.id, arg_collection_row.id ;
     END IF;
     CLOSE curlocationimage;
     FETCH curlocation INTO rowlocation;
     IF FOUND AND batchMode = true THEN
      RAISE WARNING 'Multiple Locations on Collection ID --> %, only most recent location used, ignoring ID --> %', arg_collection_row.id, rowlocation.id ;
     END IF;
    ELSIF batchMode = true THEN
     RAISE WARNING 'Could not find Location for Collection ID --> %, Collection not cached', arg_collection_row.id ;
    END IF;
    CLOSE curlocation;
   WHEN 0 THEN
    --- Collection not closed
   WHEN 2 THEN
    IF batchMode = true THEN
     RAISE WARNING 'No closed attribute found for Collection ID --> %', arg_collection_row.id ;
    END IF;
  END CASE;
END;
$$ LANGUAGE plpgsql;

--- 3)	Reconfigure geoserver to output the field in the report. (manual configuration)
--- 4)	Wait for next table rebuild (happens each night automatically)
