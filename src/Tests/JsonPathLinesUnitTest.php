<?php

/**
 * @file
 * Contains \Drupal\feeds_ex\Tests\JsonPathLinesUnitTest.
 */

namespace Drupal\feeds_ex\Tests;

/**
 * Unit tests for JsonPathLines.
 *
 * @group feeds_ex
 */
class JsonPathLinesUnitTest extends UnitTestBase {

  /**
   * The mocked FeedsSource.
   *
   * @var FeedsSource
   */
  protected $source;

  /**
   * The parser being tested.
   *
   * @var FeedsParser
   */
  protected $parser;

  /**
   * The fetcher result used during parsing.
   *
   * @var FeedsFetcherResult
   */
  protected $fetcherResult;

  public function setUp() {
    parent::setUp();

    require_once $this->moduleDir . '/src/JsonPath.inc';
    require_once $this->moduleDir . '/src/JsonPathLines.inc';

    $this->downloadJsonPath();

    $this->source = $this->getMockFeedsSource();
    $this->parser = FeedsConfigurable::instance('JsonPathLines', strtolower($this->randomName()));
    $this->parser->setMessenger(new TestMessenger());
    $this->parser->addConfig(array(
      'sources' => array(
        'title' => array(
          'name' => 'Title',
          'value' => 'name',
        ),
      ),
    ));
    $this->fetcherResult = new FeedsFileFetcherResult($this->moduleDir . '/tests/resources/test.jsonl');
  }

  /**
   * Tests simple parsing.
   */
  public function testSimpleParsing() {
    $result = $this->parser->parse($this->source, $this->fetcherResult);
    $this->assertParserResultItemCount($result, 4);

    foreach (array('Gilbert', 'Alexa', 'May', 'Deloise') as $delta => $name) {
      $this->assertEqual($name, $result->items[$delta]['title']);
    }
  }

  /**
   * Tests batch parsing.
   */
  public function testBatching() {
    $this->variableSet('feeds_process_limit', 1);

    foreach (array('Gilbert', 'Alexa', 'May', 'Deloise') as $name) {
      $result = $this->parser->parse($this->source, $this->fetcherResult);
      $this->assertParserResultItemCount($result, 1);
      $this->assertEqual($result->items[0]['title'], $name);
    }

    // We should be out of items.
    $result = $this->parser->parse($this->source, $this->fetcherResult);
    $this->assertParserResultItemCount($result, 0);
  }

  /**
   * Tests empty feed handling.
   */
  public function testEmptyFeed() {
    $this->parser->parse($this->source, new FeedsFileFetcherResult($this->moduleDir . '/tests/resources/empty.txt'));
    $this->assertEmptyFeedMessage($this->parser->getMessenger()->getMessages());
  }

}
