<?php

/**
 * @file
 * Contains \Drupal\feeds_ex\Tests\UnitTestBase.
 */

namespace Drupal\feeds_ex\Tests;

use Drupal\feeds\Tests\FeedsUnitTestCase;

/**
 * Base class for units tests.
 */
abstract class UnitTestBase extends FeedsUnitTestCase {

  /**
   * The module directory.
   *
   * @var string
   */
  protected $moduleDir;

  public function setUp() {
    parent::setUp();

    // Isn't this fun!
    drupal_load('module', 'feeds');
    $feeds = DRUPAL_ROOT . '/' . drupal_get_path('module', 'feeds');
    require_once $feeds . '/includes/FeedsConfigurable.inc';
    require_once $feeds . '/includes/FeedsSource.inc';
    require_once $feeds . '/includes/FeedsImporter.inc';
    require_once $feeds . '/plugins/FeedsPlugin.inc';
    require_once $feeds . '/plugins/FeedsFetcher.inc';
    require_once $feeds . '/plugins/FeedsFileFetcher.inc';
    require_once $feeds . '/plugins/FeedsParser.inc';
    require_once $feeds . '/plugins/FeedsProcessor.inc';
    require_once $feeds . '/plugins/FeedsNodeProcessor.inc';

    // This is out test feed source.
    require_once dirname(__FILE__) . '/TestFeedsSource.php';

    drupal_load('module', 'feeds_ex');
    $this->moduleDir = DRUPAL_ROOT . '/' . drupal_get_path('module', 'feeds_ex');
    require_once $this->moduleDir . '/src/Json/Utility.php';
    require_once $this->moduleDir . '/src/Text/Utility.php';
    require_once $this->moduleDir . '/src/Xml/Utility.php';
    require_once $this->moduleDir . '/src/File/LineIterator.php';
    require_once $this->moduleDir . '/src/Base.inc';
  }

  /**
   * Returns a mocked FeedsSource object.
   *
   * @param string $fetcher
   *   (optional) The fetcher class. Defaults to FeedsFileFetcher
   * @param string $processor
   *   (optional) The processor class. Defaults to FeedsNodeProcessor.
   *
   * @return FeedsSource
   *   The mocked FeedsSource object,
   */
  protected function getMockFeedsSource($fetcher = 'FeedsFileFetcher', $processor = 'FeedsNodeProcessor') {
    $importer = $this->newInstanceWithoutConstructor('FeedsImporter');

    $fetcher = $this->newInstanceWithoutConstructor($fetcher);
    $this->setProperty($importer, 'fetcher', $fetcher);

    $processor = $this->newInstanceWithoutConstructor($processor);
    $this->setProperty($importer, 'processor', $processor);

    $source = $this->newInstanceWithoutConstructor('TestFeedsSource');
    $this->setProperty($source, 'importer', $importer);

    return $source;
  }

  /**
   * Downloads JSONPath.
   */
  protected function downloadJsonPath() {
    // We don't use a variable_set() here since we want this to be a unit test.
    if (defined('FEEDS_EX_LIBRARY_PATH')) {
      return;
    }
    $url = 'https://jsonpath.googlecode.com/svn/trunk/src/php/jsonpath.php';
    $filename = 'jsonpath.php';

    // Avoid downloading the file dozens of times.
    $library_dir = $this->originalFileDirectory . '/simpletest/feeds_ex';
    $jsonpath_library_dir = DRUPAL_ROOT . '/' . $library_dir . '/jsonpath';

    if (!file_exists(DRUPAL_ROOT . '/' . $library_dir)) {
      drupal_mkdir(DRUPAL_ROOT . '/' . $library_dir);
    }

    if (!file_exists($jsonpath_library_dir)) {
      drupal_mkdir($jsonpath_library_dir);
    }

    // Local file name.
    $local_file = $jsonpath_library_dir . '/' . $filename;

    // Begin single threaded code.
    if (function_exists('sem_get')) {
      $semaphore = sem_get(ftok(__FILE__, 1));
      sem_acquire($semaphore);
    }

    // Download and extact the archive, but only in one thread.
    if (!file_exists($local_file)) {
      $local_file = system_retrieve_file($url, $local_file, FALSE, FILE_EXISTS_REPLACE);
    }

    if (function_exists('sem_get')) {
      sem_release($semaphore);
    }

    // Verify that the file was successfully downloaded.
    $this->assertTrue(file_exists($local_file), format_string('@file found.', array('@file' => $local_file)));

    // Set the library directory.
    define('FEEDS_EX_LIBRARY_PATH', $library_dir);
  }

  /**
   * Asserts that the correct number of items have been parsed.
   *
   * @param FeedsParserResult $result
   *   The parser result.
   * @param int $count
   *   The number of items that should exist.
   */
  protected function assertParserResultItemCount(FeedsParserResult $result, $count) {
    $this->assertEqual(count($result->items), $count, format_string('@count items parsed.', array('@count' => count($result->items))));
  }

  /**
   * Asserts that the empty message is correct.
   *
   * @param array $messages
   *   The list of error messages.
   */
  protected function assertEmptyFeedMessage(array $messages) {
    $this->assertEqual(1, count($messages), 'The expected number of messages.');
    $this->assertEqual($messages[0]['message'], 'The feed is empty.', 'Message text is correct.');
    $this->assertEqual($messages[0]['type'], 'warning', 'Message type is warning.');
    $this->assertFalse($messages[0]['repeat'], 'Repeat is set to false.');
  }

}
