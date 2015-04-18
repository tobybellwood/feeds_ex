<?php

/**
 * @file
 * Contains \Drupal\Tests\feeds_ex\Unit\File\LineIteratorTest.
 */

namespace Drupal\Tests\feeds_ex\Unit\File;

use Drupal\Tests\UnitTestCase;

/**
 * Unit tests for LineIterator.
 *
 * @group feeds_ex
 */
class LineIteratorTest extends UnitTestCase {

  /**
   * The module directory path.
   *
   * @var string
   */
  protected $moduleDir;

  public function setUp() {
    parent::setUp();
    $this->moduleDir = drupal_get_path('module', 'feeds_ex');
    require_once DRUPAL_ROOT . '/' . $this->moduleDir . '/src/File/LineIterator.php';
  }

  /**
   * Tests basic iteration.
   */
  public function test() {
    $iterator = new LineIterator($this->moduleDir . '/tests/resources/test.jsonl');
    $this->assertEqual(count(iterator_to_array($iterator)), 4);
  }

  /**
   * Tests settings line limits.
   */
  public function testLineLimit() {
    foreach (range(1, 4) as $limit) {
      $iterator = new LineIterator($this->moduleDir . '/tests/resources/test.jsonl');
      $iterator->setLineLimit($limit);
      $array = iterator_to_array($iterator);
      $this->assertEqual(count($array), $limit, format_string('@count lines read.', array('@count' => count($array))));
    }
  }

  /**
   * Tests resuming file position.
   */
  public function testFileResume() {
    $iterator = new LineIterator($this->moduleDir . '/tests/resources/test.jsonl');
    $iterator->setLineLimit(1);
    foreach (array('Gilbert', 'Alexa', 'May', 'Deloise') as $name) {
      foreach ($iterator as $line) {
        $line = drupal_json_decode($line);
        $this->assertEqual($line['name'], $name);
      }
      $iterator->setStartPosition($iterator->ftell());
    }
  }

}