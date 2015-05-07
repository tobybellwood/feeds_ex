<?php

/**
 * @file
 * Contains \Drupal\Tests\feeds_ex\Unit\UnitTestBase.
 */

namespace Drupal\Tests\feeds_ex\Unit;

use \ReflectionMethod;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\feeds\Result\ParserResultInterface;
use Drupal\Tests\feeds\Unit\FeedsUnitTestCase;

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

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    $this->moduleDir = dirname(dirname(dirname(dirname(__FILE__))));

    parent::setUp();
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
   * Calls a private or protected method on an object.
   *
   * @param object $object
   *   The object to invoke a method on.
   * @param string $method
   *   The name of the method.
   * @param array $arguments
   *   (optional) The arguments to provide to the method. Defaults to an empty
   *   array.
   *
   * @return mixed
   *   Whatever the method returns.
   */
  protected function invokeMethod($object, $method, array $arguments = array()) {
    $reflector = new ReflectionMethod($object, $method);
    $reflector->setAccessible(TRUE);
    return $reflector->invokeArgs($object, $arguments);
  }

  /**
   * Asserts that the correct number of items have been parsed.
   *
   * @param \Drupal\feeds\Result\ParserResultInterface $result
   *   The parser result.
   * @param int $count
   *   The number of items that should exist.
   */
  protected function assertParserResultItemCount(ParserResultInterface $result, $count) {
    $this->assertSame(count($result->items), $count, SafeMarkup::format('@count items parsed.', ['@count' => count($result->items)]));
  }

  /**
   * Asserts that the empty message is correct.
   *
   * @param array $messages
   *   The list of error messages.
   */
  protected function assertEmptyFeedMessage(array $messages) {
    $this->assertSame(1, count($messages), 'The expected number of messages.');
    $this->assertSame($messages[0]['message'], 'The feed is empty.', 'Message text is correct.');
    $this->assertSame($messages[0]['type'], 'warning', 'Message type is warning.');
    $this->assertFalse($messages[0]['repeat'], 'Repeat is set to false.');
  }

}
