<?php

namespace Drupal\Tests\feeds_ex\Unit;

/**
 * @FIXME
 * Unit tests are now written for the PHPUnit framework. You will need to refactor
 * this test in order for it to work properly.
 */
class FeedsExLineIteratorUnitTests extends \Drupal\Tests\UnitTestCase {

  /**
   * The module directory path.
   *
   * @var string
   */
  protected $moduleDir;

  public static function getInfo() {
    return array(
      'name' => 'Unit tests for the line reading iterator',
      'description' => 'Unit tests for FeedsExLineIterator.',
      'group' => 'Feeds EX',
    );
  }

  public function setUp() {
    parent::setUp();
    $this->moduleDir = drupal_get_path('module', 'feeds_ex');
    require_once \Drupal::root() . '/' . $this->moduleDir . '/src/File/FeedsExLineIterator.php';
  }

  /**
   * Tests basic iteration.
   */
  public function test() {
    $iterator = new FeedsExLineIterator($this->moduleDir . '/tests/resources/test.jsonl');
    $this->assertEqual(count(iterator_to_array($iterator)), 4);
  }

  /**
   * Tests settings line limits.
   */
  public function testLineLimit() {
    foreach (range(1, 4) as $limit) {
      $iterator = new FeedsExLineIterator($this->moduleDir . '/tests/resources/test.jsonl');
      $iterator->setLineLimit($limit);
      $array = iterator_to_array($iterator);
      $this->assertEqual(count($array), $limit, format_string('@count lines read.', array('@count' => count($array))));
    }
  }

  /**
   * Tests resuming file position.
   */
  public function testFileResume() {
    $iterator = new FeedsExLineIterator($this->moduleDir . '/tests/resources/test.jsonl');
    $iterator->setLineLimit(1);
    foreach (array('Gilbert', 'Alexa', 'May', 'Deloise') as $name) {
      foreach ($iterator as $line) {
        $line = \Drupal\Component\Serialization\Json::decode($line);
        $this->assertEqual($line['name'], $name);
      }
      $iterator->setStartPosition($iterator->ftell());
    }
  }

}