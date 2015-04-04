<?php

/**
 * @file
 * Contains \Drupal\feeds_ex\Tests\QueryPathXmlUnitTest.
 */

namespace Drupal\feeds_ex\Tests;

/**
 * Unit tests for QueryPathXml.
 *
 * @group feeds_ex
 */
class QueryPathXmlUnitTest extends UnitTestBase {

  /**
   * The mocked FeedsSource.
   *
   * @var FeedsSource
   */
  protected $source;

  public static $modules = [
    'querypath',
  ];

  public function setUp() {
    parent::setUp();

    $query_path = drupal_get_path('module', 'querypath');
    require_once DRUPAL_ROOT . '/' . $query_path .  '/QueryPath/QueryPath.php';

    require_once $this->moduleDir . '/src/Xml.inc';
    require_once $this->moduleDir . '/src/QueryPathXml.inc';

    $this->source = $this->getMockFeedsSource();
  }

  /**
   * Tests simple parsing.
   */
  public function testSimpleParsing() {
    $parser = $this->getParserInstance();
    $fetcher_result = new FeedsFetcherResult(file_get_contents($this->moduleDir . '/tests/resources/test.xml'));

    $parser->setConfig(array(
      'context' => array(
        'value' => 'items item',
      ),
      'sources' => array(
        'title' => array(
          'name' => 'Title',
          'value' => 'title',
          'attribute' => '',
        ),
        'description' => array(
          'name' => 'Title',
          'value' => 'description',
          'attribute' => '',
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
   * Tests raw parsing.
   */
  public function testRaw() {
    $parser = $this->getParserInstance();
    $fetcher_result = new FeedsFetcherResult(file_get_contents($this->moduleDir . '/tests/resources/test.xml'));

    $parser->setConfig(array(
      'context' => array(
        'value' => 'items item',
      ),
      'sources' => array(
        'title' => array(
          'name' => 'Title',
          'value' => 'title',
          'attribute' => '',
        ),
        'description' => array(
          'name' => 'Title',
          'value' => 'description',
          'attribute' => '',
          'raw' => TRUE,
        ),
      ),
    ));

    $result = $parser->parse($this->source, $fetcher_result);
    $this->assertParserResultItemCount($result, 3);

    foreach ($result->items as $delta => $item) {
      $this->assertEqual('I am a title' . $delta, $item['title']);
      $this->assertEqual('<description><text>I am a description' . $delta . '</text></description>', $item['description']);
    }
  }

  /**
   * Tests inner xml.
   */
  public function testInner() {
    $parser = $this->getParserInstance();
    $fetcher_result = new FeedsFetcherResult(file_get_contents($this->moduleDir . '/tests/resources/test.xml'));

    $parser->setConfig(array(
      'context' => array(
        'value' => 'items item',
      ),
      'sources' => array(
        'title' => array(
          'name' => 'Title',
          'value' => 'title',
          'attribute' => '',
        ),
        'description' => array(
          'name' => 'Title',
          'value' => 'description',
          'attribute' => '',
          'raw' => TRUE,
          'inner' => TRUE,
        ),
      ),
    ));

    $result = $parser->parse($this->source, $fetcher_result);
    $this->assertParserResultItemCount($result, 3);

    foreach ($result->items as $delta => $item) {
      $this->assertEqual('I am a title' . $delta, $item['title']);
      $this->assertEqual('<text>I am a description' . $delta . '</text>', $item['description']);
    }
  }

  /**
   * Tests grabbing an attribute.
   */
  public function testAttributeParsing() {
    $parser = $this->getParserInstance();
    $fetcher_result = new FeedsFetcherResult(file_get_contents($this->moduleDir . '/tests/resources/test.xml'));

    $parser->setConfig(array(
      'context' => array(
        'value' => 'items item',
      ),
      'sources' => array(
        'title' => array(
          'name' => 'Title',
          'value' => 'title',
          'attribute' => 'attr',
        ),
        'description' => array(
          'name' => 'Title',
          'value' => 'description',
          'attribute' => '',
        ),
      ),
    ));

    $result = $parser->parse($this->source, $fetcher_result);
    $this->assertParserResultItemCount($result, 3);

    foreach ($result->items as $delta => $item) {
      $this->assertEqual('attribute' . $delta, $item['title']);
      $this->assertEqual('I am a description' . $delta, $item['description']);
    }
  }

  /**
   * Tests grabbing multiple attributes.
   */
  public function testMultipleAttributeParsing() {
    $parser = $this->getParserInstance();
    $fetcher_result = new FeedsFetcherResult(file_get_contents($this->moduleDir . '/tests/resources/test.xml'));

    $parser->setConfig(array(
      'context' => array(
        'value' => 'items thing',
      ),
      'sources' => array(
        'url' => array(
          'name' => 'URL',
          'value' => 'img',
          'attribute' => 'src',
        ),
      ),
    ));

    $result = $parser->parse($this->source, $fetcher_result);
    $this->assertParserResultItemCount($result, 1);

    $this->assertEqual(count($result->items[0]['url']), 2);

    $this->assertEqual($result->items[0]['url'][0], 'http://drupal.org');
    $this->assertEqual($result->items[0]['url'][1], 'http://drupal.org/project/feeds_ex');
  }

  /**
   * Tests parsing a CP866 (Russian) encoded file.
   */
  public function testCP866Encoded() {
    $parser = $this->getParserInstance();
    $fetcher_result = new FeedsFetcherResult(file_get_contents($this->moduleDir . '/tests/resources/test_ru.xml'));

    $parser->setConfig(array(
      'context' => array(
        'value' => 'items item',
      ),
      'sources' => array(
        'title' => array(
          'name' => 'Title',
          'value' => 'title',
          'attribute' => '',
        ),
        'description' => array(
          'name' => 'Title',
          'value' => 'description',
          'attribute' => '',
        ),
      ),
    ));

    $result = $parser->parse($this->source, $fetcher_result);
    $this->assertParserResultItemCount($result, 3);

    foreach ($result->items as $delta => $item) {
      $this->assertEqual('Я название' . $delta, $item['title']);
      $this->assertEqual('Я описание' . $delta, $item['description']);
    }
  }

  /**
   * Tests a EUC-JP (Japanese) encoded file without the encoding declaration.
   *
   * This implicitly tests Base's encoding conversion.
   */
  public function testEUCJPEncodedNoDeclaration() {
    $parser = $this->getParserInstance();
    $fetcher_result = new FeedsFetcherResult(file_get_contents($this->moduleDir . '/tests/resources/test_jp.xml'));

    $parser->setConfig(array(
      'context' => array(
        'value' => 'items item',
      ),
      'sources' => array(
        'title' => array(
          'name' => 'Title',
          'value' => 'title',
          'attribute' => '',
        ),
        'description' => array(
          'name' => 'Title',
          'value' => 'description',
          'attribute' => '',
        ),
      ),
      'source_encoding' => array('EUC-JP'),
    ));

    $result = $parser->parse($this->source, $fetcher_result);
    $this->assertParserResultItemCount($result, 3);

    foreach ($result->items as $delta => $item) {
      $this->assertEqual('私はタイトルです' . $delta, $item['title']);
      $this->assertEqual('私が説明してい' . $delta, $item['description']);
    }
  }

  /**
   * Tests that batch parsing works.
   */
  public function testBatchParsing() {
    $parser = $this->getParserInstance();
    $fetcher_result = new FeedsFetcherResult(file_get_contents($this->moduleDir . '/tests/resources/test.xml'));

    $parser->setConfig(array(
      'context' => array(
        'value' => 'items item',
      ),
      'sources' => array(
        'title' => array(
          'name' => 'Title',
          'value' => 'title',
          'attribute' => '',
        ),
        'description' => array(
          'name' => 'Title',
          'value' => 'description',
          'attribute' => '',
        ),
      ),
    ));

    $this->variableSet('feeds_process_limit', 1);

    foreach (range(0, 2) as $delta) {
      $result = $parser->parse($this->source, $fetcher_result);
      $this->assertParserResultItemCount($result, 1);
      $this->assertEqual('I am a title' . $delta, $result->items[0]['title']);
      $this->assertEqual('I am a description' . $delta, $result->items[0]['description']);
    }

    $result = $parser->parse($this->source, $fetcher_result);
    $this->assertParserResultItemCount($result, 0);
  }

  /**
   * Tests QueryPath validation.
   */
  public function testValidateExpression() {
    // Invalid expression.
    $parser = $this->getParserInstance();
    $expression = array('!!');
    $this->assertEqual('CSS selector is not well formed.', $this->invokeMethod($parser, 'validateExpression', $expression));

    // Test that value was trimmed.
    $this->assertEqual($expression[0], '!!', 'Value was trimmed.');

    // Empty.
    $this->assertEqual(NULL, $this->invokeMethod($parser, 'validateExpression', array('')));
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
   * @return QueryPathXml
   *   A parser instance.
   */
  protected function getParserInstance() {
    $parser = FeedsConfigurable::instance('QueryPathXml', strtolower($this->randomName()));
    $parser->setMessenger(new TestMessenger());
    return $parser;
  }

}
