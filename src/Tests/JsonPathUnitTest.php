<?php

/**
 * @file
 * Contains \Drupal\feeds_ex\Tests\JsonPathUnitTest.
 */

namespace Drupal\feeds_ex\Tests;

/**
 * Unit tests for JsonPath.
 *
 * @group feeds_ex
 */
class JsonPathUnitTest extends UnitTestBase {

  /**
   * The mocked FeedsSource.
   *
   * @var FeedsSource
   */
  protected $source;

  public function setUp() {
    parent::setUp();
    require_once $this->moduleDir . '/src/JsonPath.inc';
    $this->source = $this->getMockFeedsSource();
    $this->downloadJsonPath();
  }

  /**
   * Tests simple parsing.
   */
  public function testSimpleParsing() {
    $parser = $this->getParserInstance();
    $fetcher_result = new FeedsFetcherResult(file_get_contents($this->moduleDir . '/tests/resources/test.json'));

    $parser->setConfig(array(
      'context' => array(
        'value' => '$.items.*',
      ),
      'sources' => array(
        'title' => array(
          'name' => 'Title',
          'value' => 'title',
        ),
        'description' => array(
          'name' => 'Title',
          'value' => 'description',
        ),
      ),
    ));

    $result = $parser->parse($this->source, $fetcher_result);
    $this->assertParserResultItemCount($result, 3);

    foreach ($result->items as $delta => $item) {
      $this->assertEqual('I am a title' . $delta, $item['title']);
      $this->assertEqual('I am a description' . $delta, $item['description']);
    }
  }

  /**
   * Tests parsing error handling.
   */
  public function testErrorHandling() {
    // Parse some invalid JSON.
    json_decode('\\"asdfasfd');

    $parser = $this->getParserInstance();
    $errors = $this->invokeMethod($parser, 'getErrors');
    $this->assertEqual(3, $errors[0]['severity']);
  }

  /**
   * Tests batch parsing.
   */
  public function testBatchParsing() {
    // Set batch limit.
    $this->variableSet('feeds_process_limit', 1);

    $parser = $this->getParserInstance();
    $fetcher_result = new FeedsFetcherResult(file_get_contents($this->moduleDir . '/tests/resources/test.json'));

    $parser->setConfig(array(
      'context' => array(
        'value' => '$.items.*',
      ),
      'sources' => array(
        'title' => array(
          'name' => 'Title',
          'value' => 'title',
        ),
        'description' => array(
          'name' => 'Title',
          'value' => 'description',
        ),
      ),
    ));

    foreach (range(0, 2) as $delta) {
      $result = $parser->parse($this->source, $fetcher_result);
      $this->assertParserResultItemCount($result, 1);
      $this->assertEqual('I am a title' . $delta, $result->items[0]['title']);
      $this->assertEqual('I am a description' . $delta, $result->items[0]['description']);
    }

    // We should be out of items.
    $result = $parser->parse($this->source, $fetcher_result);
    $this->assertParserResultItemCount($result, 0);
  }

  /**
   * Tests JSONPath validation.
   *
   * @todo Do real validation.
   */
  public function testValidateExpression() {
    // Invalid expression.
    $parser = $this->getParserInstance();
    $expression = array('!! ');
    $this->assertEqual(NULL, $this->invokeMethod($parser, 'validateExpression', $expression));

    // Test that value was trimmed.
    $this->assertEqual($expression[0], '!!', 'Value was trimmed.');
  }

  /**
   * Tests parsing invalid JSON.
   */
  public function testInvalidJson() {
    $parser = $this->getParserInstance();

    $parser->setConfig(array(
      'context' => array(
        'value' => '$.items[asdfasdf]',
      ),
      'sources' => array(),
    ));

    $args = array($this->source, new FeedsFetcherResult('{"items": "not an array"}'));
    $this->assertException(array($parser, 'parse'), $args, 'RuntimeException', t('The context expression must return an object or array.'));

    // Invalid JSON.
    $args = array($this->source, new FeedsFetcherResult('invalid json'));
    $this->assertException(array($parser, 'parse'), $args, 'RuntimeException', t('The JSON is invalid.'));

    $log_messages = $this->source->getLogMessages();
    $this->assertEqual(count($log_messages), 1);
    $this->assertEqual($log_messages[0]['message'], 'Syntax error');
    $this->assertEqual($log_messages[0]['type'], 'feeds_ex');
    $this->assertEqual($log_messages[0]['severity'], 3);
  }

  /**
   * Tests empty feed handling.
   */
  public function testEmptyFeed() {
    $parser = $this->getParserInstance();
    $parser->parse($this->source, new FeedsFetcherResult(' '));
    $this->assertEmptyFeedMessage($parser->getMessenger()->getMessages());
  }

  /**
   * Returns a new instance of the parser.
   *
   * @return JsonPath
   *   A parser instance.
   */
  protected function getParserInstance() {
    $parser = FeedsConfigurable::instance('JsonPath', strtolower($this->randomName()));
    $parser->setMessenger(new TestMessenger());
    return $parser;
  }

}
