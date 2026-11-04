<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../libraries/RecordCleanerApi.php';

/**
 * Test Record Cleaner cache updates using PostgreSQL temporary tables.
 *
 * Set INDICIA_TEST_PG_DSN to a PDO PostgreSQL DSN for a test connection.
 */
class RecordCleanerApiTest extends TestCase {

  /**
   * Difficulty is taxon metadata, including when no warning comment is saved.
   *
   * @dataProvider difficultyCases
   */
  public function testDifficultyCacheMatchesTaxonCache(?int $cachedDifficulty, ?int $reportedDifficulty) {
    $dsn = getenv('INDICIA_TEST_PG_DSN');
    if (!$dsn || !extension_loaded('pdo_pgsql')) {
      $this->markTestSkipped('Set INDICIA_TEST_PG_DSN with pdo_pgsql enabled to run temporary-table SQL tests.');
    }
    $pdo = new PDO($dsn, NULL, NULL, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $pdo->exec(<<<SQL
      SET search_path=pg_temp;
      CREATE TEMP TABLE occurrences (id integer PRIMARY KEY, last_verification_check_date timestamp);
      CREATE TEMP TABLE occurrence_comments (
        id integer GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
        occurrence_id integer, comment text, auto_generated boolean,
        generated_by text, generated_by_subtype text,
        implies_manual_check_required boolean, deleted boolean DEFAULT false,
        created_by_id integer, created_on timestamp, updated_by_id integer, updated_on timestamp
      );
      CREATE TEMP TABLE cache_taxon_searchterms (
        taxa_taxon_list_id integer, taxon_meaning_id integer,
        simplified boolean, identification_difficulty integer
      );
      CREATE TEMP TABLE cache_taxa_taxon_lists (
        external_key text, preferred boolean, taxon_meaning_id integer,
        applicable_verification_rule_types text[]
      );
      CREATE TEMP TABLE cache_occurrences_functional (
        id integer PRIMARY KEY, taxa_taxon_list_id integer, taxon_meaning_id integer,
        taxa_taxon_list_external_key text, identification_difficulty integer,
        data_cleaner_result boolean, applied_verification_rule_types text[]
      );
      CREATE TEMP TABLE cache_occurrences_nonfunctional (id integer PRIMARY KEY, data_cleaner_info text);
      INSERT INTO occurrences VALUES (1,now()),(2,now());
      INSERT INTO cache_taxon_searchterms VALUES (10,100,false,NULL),(10,100,true,NULL),(20,200,false,4);
      INSERT INTO cache_taxa_taxon_lists VALUES
        ('TESTKEY',true,100,'{identification_difficulty}'),
        ('OTHERKEY',true,200,'{identification_difficulty}');
      INSERT INTO cache_occurrences_functional VALUES
        (1,10,100,'TESTKEY',4,NULL,NULL),
        (2,20,200,'OTHERKEY',4,NULL,NULL);
      INSERT INTO cache_occurrences_nonfunctional VALUES (1,NULL),(2,'unchanged');
      INSERT INTO occurrence_comments (
        occurrence_id, generated_by, generated_by_subtype, implies_manual_check_required, deleted
      ) VALUES (1,'record_cleaner_difficulty','5',true,true);
    SQL);
    $statement = $pdo->prepare(
      'UPDATE cache_taxon_searchterms SET identification_difficulty=? WHERE taxa_taxon_list_id=10'
    );
    $statement->execute([$cachedDifficulty]);

    $db = new class($pdo) {

      private PDO $pdo;

      public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
      }

      public function query(string $sql, array $parameters = []) {
        if ($parameters) {
          $statement = $this->pdo->prepare($sql);
          $statement->execute($parameters);
        }
        else {
          $this->pdo->exec($sql);
        }
      }

    };
    $reflection = new ReflectionClass(RecordCleanerApi::class);
    $api = $reflection->newInstanceWithoutConstructor();
    $reflection->getProperty('db')->setValue($api, $db);
    $record = (object) [
      'id' => 1,
      'messages' => $reportedDifficulty === NULL ? [] : [
        ":TEST:difficulty:$reportedDifficulty:Test difficulty",
      ],
    ];
    $reflection->getMethod('saveRecordResponse')->invoke($api, $record, 'SU12');
    $reflection->getMethod('updateCacheTables')->invoke($api, [1]);

    $expectedDifficulty = $reportedDifficulty ?? $cachedDifficulty;
    $warningExpected = $reportedDifficulty !== NULL && $reportedDifficulty >= 2;
    $taxonDifficulty = $pdo->query(
      'SELECT identification_difficulty FROM cache_taxon_searchterms WHERE taxa_taxon_list_id=10 AND simplified=false'
    )->fetchColumn();
    $occurrence = $pdo->query(
      'SELECT identification_difficulty, data_cleaner_result, applied_verification_rule_types
       FROM cache_occurrences_functional WHERE id=1'
    )->fetchObject();
    $this->assertSame($expectedDifficulty, $taxonDifficulty);
    $this->assertSame($expectedDifficulty, $occurrence->identification_difficulty);
    $this->assertSame(!$warningExpected, $occurrence->data_cleaner_result);
    $this->assertSame('{identification_difficulty}', $occurrence->applied_verification_rule_types);
    $this->assertSame(
      $warningExpected ? 1 : 0,
      $pdo->query('SELECT COUNT(*) FROM occurrence_comments WHERE occurrence_id=1 AND deleted=false')->fetchColumn()
    );
    $this->assertSame(
      $warningExpected ? '[record_cleaner_difficulty]{Test difficulty (TEST)}' : 'pass',
      $pdo->query('SELECT data_cleaner_info FROM cache_occurrences_nonfunctional WHERE id=1')->fetchColumn()
    );
    $this->assertSame(
      4,
      $pdo->query('SELECT identification_difficulty FROM cache_occurrences_functional WHERE id=2')->fetchColumn()
    );
    $this->assertSame(
      'unchanged',
      $pdo->query('SELECT data_cleaner_info FROM cache_occurrences_nonfunctional WHERE id=2')->fetchColumn()
    );
  }

  /**
   * @return array
   *   New, changed, existing, and unknown taxon difficulties.
   */
  public static function difficultyCases(): array {
    return [
      'easy taxon without warning' => [NULL, 1],
      'difficulty reduced to easy' => [3, 1],
      'difficulty warning' => [NULL, 2],
      'highest difficulty warning' => [NULL, 5],
      'no difficulty message preserves taxon difficulty' => [3, NULL],
      'unknown difficulty stays null' => [NULL, NULL],
    ];
  }

}
