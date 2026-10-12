<?php

use PHPUnit\DbUnit\DataSet\YamlDataSet as DbUDataSetYamlDataSet;

/**
 * Tests taxon lookup handling of misidentified names.
 */
class RestApiSyncFindTaxonTest extends Indicia_DatabaseTestCase {

  /**
   * Database connection used to arrange taxon lookup test data.
   *
   * @var Database
   */
  private static $db;

  /**
   * Creates the database connection used by the test class.
   */
  public static function setUpBeforeClass(): void {
    parent::setUpBeforeClass();
    self::$db = new Database();
  }

  /**
   * Loads the core fixture required by the database test framework.
   *
   * @return PHPUnit\DbUnit\DataSet\YamlDataSet
   *   The database fixture dataset.
   */
  public function getDataSet() {
    return new DbUDataSetYamlDataSet('modules/phpUnit/config/core_fixture.yaml');
  }

  /**
   * Checks a NULL authority name is selected over a matching misident.
   */
  public function testOriginalNameLookupSelectsNullAuthorityOverMisident() {
    self::$db->query(
      "UPDATE cache_taxon_searchterms SET original=?, searchterm=?, authority=? " .
      "WHERE taxa_taxon_list_id=2 AND simplified=false",
      ['Test taxon', 'Test taxon', 'auct. non Test taxon']
    );

    $this->assertSame('1', (string) $this->findTaxon(['original' => 'Test taxon']));
  }

  /**
   * Checks a valid match is selected even when a preferred misident also matches.
   */
  public function testSearchCodeLookupSelectsValidNameOverPreferredMisident() {
    self::$db->query(
      'UPDATE taxa SET search_code=?, authority=? WHERE taxon=?',
      ['DUPLICATE_TEST', 'Test authority', 'Test taxon']
    );
    self::$db->query(
      'UPDATE taxa SET search_code=?, authority=? WHERE taxon=?',
      ['DUPLICATE_TEST', 'auct. non Test taxon', 'Test taxon 2']
    );
    self::$db->query(
      'UPDATE taxa_taxon_lists SET preferred=false WHERE taxon_id=1 AND taxon_list_id=1'
    );
    self::$db->query(
      'UPDATE taxa_taxon_lists SET preferred=true WHERE taxon_id=2 AND taxon_list_id=1'
    );

    $this->assertSame('1', (string) $this->findTaxon(['search_code' => 'DUPLICATE_TEST']));
  }

  /**
   * Checks distinct valid concepts still make a lookup ambiguous.
   */
  public function testSearchCodeLookupRejectsMultipleValidConcepts() {
    self::$db->query(
      'UPDATE taxa SET search_code=?, authority=? WHERE taxon IN (?, ?)',
      ['AMBIGUOUS_TEST', 'Test authority', 'Test taxon', 'Test taxon 2']
    );

    $this->assertNull($this->findTaxon(['search_code' => 'AMBIGUOUS_TEST']));
  }

  /**
   * Checks a single match found only by a misidentified name is accepted.
   */
  public function testSearchCodeLookupAcceptsSingleMisidentifiedConcept() {
    self::$db->query(
      'UPDATE taxa SET search_code=?, authority=? WHERE taxon=?',
      ['MISIDENT_TEST', 'auct. non Test taxon', 'Test taxon 2']
    );

    $this->assertSame('2', (string) $this->findTaxon(['search_code' => 'MISIDENT_TEST']));
  }

  /**
   * Invokes the private taxon lookup helper.
   *
   * @param array $lookup
   *   Search fields and values.
   *
   * @return int|null
   *   The matching taxon-list ID, if unique.
   */
  private function findTaxon(array $lookup) {
    $method = new ReflectionMethod('api_persist', 'findTaxon');
    return $method->invoke(null, self::$db, 1, $lookup);
  }

}
