<?php

use PHPUnit\DbUnit\DataSet\YamlDataSet as DbUDataSetYamlDataSet;

require_once 'client_helpers/data_entry_helper.php';
require_once 'client_helpers/submission_builder.php';

$postedUserId = 1;

// This forces the get_hostsite_user_id shim to be made available.
warehouse::getMasterTaxonListId();

class SpatialIndexBuilderTest extends Indicia_DatabaseTestCase {

  private static $extraUserId;

  private static $db;

  private static $auth;

  public static function setUpBeforeClass(): void {
    parent::setUpBeforeClass();
    self::$auth = data_entry_helper::get_read_write_auth(1, 'password');
    // Make the tokens re-usable.
    self::$auth['write_tokens']['persist_auth'] = TRUE;
    self::$db = new Database();
  }

  public function getDataSet() {
    $ds1 = new DbUDataSetYamlDataSet('modules/phpUnit/config/core_fixture.yaml');
    return $ds1;
  }

  /**
   * Full test for the population of location.higher_location_ids.
   */
  public function testLocationIndexing() {
    $locationTypeId = 2;
    $higherLocationTypeId = 9;
    // A submission structure for locations plus websites.
    $structureWithWebsite = [
      'model' => 'location',
      'subModels' => [
        'locations_website' => ['fk' => 'location_id'],
      ],
    ];
    // Add a higher location.
    $array = [
      'location:name' => 'Higher location 1',
      'location:centroid_sref' => '51.33909N, 1.89374W',
      'location:centroid_sref_system' => '4326',
      'location:location_type_id' => $higherLocationTypeId,
      'location:boundary_geom' => 'POLYGON((-229155.23824695 6697394.0883963,-230378.23069935 6668042.2695389,-196134.44203236 6671711.2468961,-199803.41938953 6693725.1110391,-229155.23824695 6697394.0883963))',
      'location:public' => 't',
    ];
    $s = submission_builder::build_submission($array, ['model' => 'location']);
    $r = data_entry_helper::forward_post_to('location', $s, self::$auth['write_tokens']);
    $this->assertTrue(isset($r['success']), 'Submitting a location did not return success response');
    $higherLocId1 = $r['success'];
    // Add an identical, but private location.
    $array['location:public'] = 'f';
    $s = submission_builder::build_submission($array, ['model' => 'location']);
    $r = data_entry_helper::forward_post_to('location', $s, self::$auth['write_tokens']);
    $this->assertTrue(isset($r['success']), 'Submitting a location did not return success response');
    $higherLocId1Private = $r['success'];
    // Add another adjacent higher location.
    $array = [
      'location:name' => 'Higher location 2',
      'location:centroid_sref' => '51.35453N, 2.17074W',
      'location:centroid_sref_system' => '4326',
      'location:location_type_id' => $higherLocationTypeId,
      'location:boundary_geom' => 'POLYGON((-229155.23824695 6697394.0883963,-230378.23069935 6668042.2695389,-255709.93960096 6667583.6473693,-254945.56931821 6698005.5846225,-229155.23824695 6697394.0883963))',
      'location:public' => 't',
    ];
    $s = submission_builder::build_submission($array, ['model' => 'location']);
    $r = data_entry_helper::forward_post_to('location', $s, self::$auth['write_tokens']);
    $this->assertTrue(isset($r['success']), 'Submitting a location did not return success response');
    $higherLocId2 = $r['success'];
    // Add a location within the first higher location.
    $array = [
      'location:name' => 'Lower location 1',
      'location:centroid_sref' => '51.31935N, 2.00457W',
      'location:centroid_sref_system' => '4326',
      'location:location_type_id' => $locationTypeId,
      'location:boundary_geom' => 'POLYGON((-225288.00234769 6680730.8162325,-225746.62451734 6675533.0983098,-219937.41036847 6675227.3501967,-220243.15848157 6679966.4459498,-225288.00234769 6680730.8162325))',
      'locations_website:website_id' => 1,
    ];
    $s = submission_builder::build_submission($array, $structureWithWebsite);
    $r = data_entry_helper::forward_post_to('location', $s, self::$auth['write_tokens']);
    $this->assertTrue(isset($r['success']), 'Submitting a location did not return success response');
    $lowerLoc1 = $r['success'];
    // Add an identical location, but in website 2 so not indexed.
    $array['locations_website:website_id'] = 2;
    $s = submission_builder::build_submission($array, $structureWithWebsite);
    $r = data_entry_helper::forward_post_to('location', $s, self::$auth['write_tokens']);
    $this->assertTrue(isset($r['success']), 'Submitting a location did not return success response');
    $lowerLoc1InWebsite2 = $r['success'];
    // Add a location right on intersection of both higher locations
    $array = [
      'location:name' => 'Lower location 2',
      'location:centroid_sref' => '51.35367N, 2.06362W',
      'location:centroid_sref_system' => '4326',
      'location:location_type_id' => $locationTypeId,
      'location:boundary_geom' => 'POLYGON((-232473.0830055 6687457.2747207,-232778.83111859 6681648.0605718,-227886.86130903 6681648.0605718,-227122.49102628 6687151.5266076,-232473.0830055 6687457.2747207))',
      'locations_website:website_id' => 1,
    ];
    $s = submission_builder::build_submission($array, $structureWithWebsite);
    $r = data_entry_helper::forward_post_to('location', $s, self::$auth['write_tokens']);
    $this->assertTrue(isset($r['success']), 'Submitting a location did not return success response');
    $lowerLoc2 = $r['success'];
    // Add a location outside both.
    $array = [
      'location:name' => 'Lower location 3',
      'location:centroid_sref' => '51.13359N, 2.23116W',
      'location:centroid_sref_system' => '4326',
      'location:location_type_id' => $locationTypeId,
      'location:boundary_geom' => 'POLYGON((-250665.09573483 6647404.2719048,-250817.96979139 6643735.2945476,-245620.25186872 6642970.9242649,-245314.50375562 6646334.153509,-250665.09573483 6647404.2719048))',
      'locations_website:website_id' => 1,
    ];
    $s = submission_builder::build_submission($array, $structureWithWebsite);
    $r = data_entry_helper::forward_post_to('location', $s, self::$auth['write_tokens']);
    $this->assertTrue(isset($r['success']), 'Submitting a location did not return success response');
    $lowerLoc3 = $r['success'];
    // Run work queue.
    $q = new WorkQueue();
    $q->process(self::$db, TRUE);
    // Check everything correctly linked.
    $this->assertEquals('{}', self::$db->query("SELECT higher_location_ids FROM locations WHERE id=?", [$higherLocId1])->current()->higher_location_ids, 'Incorrect indexing found for a higher location.');
    $this->assertEquals('{}', self::$db->query("SELECT higher_location_ids FROM locations WHERE id=?", [$higherLocId1Private])->current()->higher_location_ids, 'Incorrect indexing found for a higher location.');
    $this->assertEquals('{}', self::$db->query("SELECT higher_location_ids FROM locations WHERE id=?", [$higherLocId2])->current()->higher_location_ids, 'Incorrect indexing found for a higher location.');
    $this->assertEquals("{{$higherLocId1}}", self::$db->query("SELECT higher_location_ids FROM locations WHERE id=?", [$lowerLoc1])->current()->higher_location_ids, 'Lower location missing indexing into parent.');
    $this->assertEquals("{{$higherLocId1},{$higherLocId2}}", self::$db->query("SELECT ARRAY_AGG(x) AS higher_location_ids FROM (SELECT unnest(higher_location_ids) AS x FROM locations WHERE id=? ORDER BY x) AS _", [$lowerLoc2])->current()->higher_location_ids, 'Lower location missing indexing into 2 intersecting parent.');
    $this->assertEquals('{}', self::$db->query("SELECT higher_location_ids FROM locations WHERE id=?", [$lowerLoc3])->current()->higher_location_ids, 'Incorrect indexing found for an isolated lower location.');
    // Move lower location 1 away.
    $array = [
      'location:id' => $lowerLoc1,
      'location:centroid_sref' => '51.90521N, 4.58539W',
      'location:boundary_geom' => 'POLYGON((-528788.38908314 6798902.4619449,-526342.40417835 6768327.6506351,-490875.62305897 6765881.6657303,-494544.60041614 6805017.4242069,-528788.38908314 6798902.4619449))',
    ];
    $s = submission_builder::build_submission($array, ['model' => 'location']);
    $r = data_entry_helper::forward_post_to('location', $s, self::$auth['write_tokens']);
    $this->assertTrue(isset($r['success']), 'Updating to move a location did not return success response');
    // Alter higher location 2 so doesn't intersect location 2 but does
    // intersect location 3.
    $array = [
      'location:id' => $higherLocId2,
      'location:centroid_sref' => '51.13359N, 2.23116W',
      'location:boundary_geom' => 'POLYGON((-250665.09573483 6647404.2719048,-250817.96979139 6643735.2945476,-245620.25186872 6642970.9242649,-245314.50375562 6646334.153509,-250665.09573483 6647404.2719048))',
    ];
    $s = submission_builder::build_submission($array, ['model' => 'location']);
    $r = data_entry_helper::forward_post_to('location', $s, self::$auth['write_tokens']);
    $this->assertTrue(isset($r['success']), 'Updating to move a location did not return success response');
    // Run work queue.
    $q->process(self::$db, TRUE);
    // Check everything correctly linked.
    $this->assertEquals('{}', self::$db->query("SELECT higher_location_ids FROM locations WHERE id=?", [$higherLocId1])->current()->higher_location_ids, 'Incorrect indexing found for a higher location.');
    $this->assertEquals('{}', self::$db->query("SELECT higher_location_ids FROM locations WHERE id=?", [$higherLocId1Private])->current()->higher_location_ids, 'Incorrect indexing found for a higher location.');
    $this->assertEquals('{}', self::$db->query("SELECT higher_location_ids FROM locations WHERE id=?", [$higherLocId2])->current()->higher_location_ids, 'Incorrect indexing found for a higher location.');
    $this->assertEquals('{}', self::$db->query("SELECT higher_location_ids FROM locations WHERE id=?", [$lowerLoc1])->current()->higher_location_ids, 'Lower location moved into isolated area incorrectly indexed into higher location.');
    $this->assertEquals("{{$higherLocId1}}", self::$db->query("SELECT higher_location_ids FROM locations WHERE id=?", [$lowerLoc2])->current()->higher_location_ids, 'Lower location missing indexing into 2 intersecting parent.');
    $this->assertEquals("{{$higherLocId2}}", self::$db->query("SELECT higher_location_ids FROM locations WHERE id=?", [$lowerLoc3])->current()->higher_location_ids, 'Lower location missing indexing into 2 intersecting parent.');
  }

}