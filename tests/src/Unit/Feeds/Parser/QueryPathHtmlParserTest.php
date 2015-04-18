<?php

/**
 * @file
 * Contains \Drupal\Tests\feeds_ex\Unit\Feeds\Parser\QueryPathHtmlParserTest.
 */

namespace Drupal\Tests\feeds_ex\Unit\Feeds\Parser;

use Drupal\Tests\feeds_ex\Unit\UnitTestBase;

/**
 * Unit tests for QueryPathHtml.
 *
 * @group feeds_ex
 */
class QueryPathHtmlParserTest extends UnitTestBase {

  /**
   * The mocked FeedsSource.
   *
   * @var FeedsSource
   */
  protected $source;

  public function setUp() {
    parent::setUp();

    $query_path = drupal_get_path('module', 'querypath');
    require_once DRUPAL_ROOT . '/' . $query_path .  '/QueryPath/QueryPath.php';

    require_once $this->moduleDir . '/src/Xml.inc';
    require_once $this->moduleDir . '/src/QueryPathXml.inc';
    require_once $this->moduleDir . '/src/QueryPathHtml.inc';

    $this->source = $this->getMockFeedsSource();
  }

  /**
   * Tests simple parsing.
   */
  public function testSimpleParsing() {
    $parser = $this->getParserInstance();
    $fetcher_result = new FeedsFetcherResult(file_get_contents($this->moduleDir . '/tests/resources/test.html'));

    $parser->setConfig(array(
      'context' => array(
        'value' => '.post',
      ),
      'sources' => array(
        'title' => array(
          'name' => 'Title',
          'value' => 'h3',
          'attribute' => '',
        ),
        'description' => array(
          'name' => 'Title',
          'value' => 'p',
          'attribute' => '',
        ),
      ),
    ));

    $result = $parser->parse($this->source, $fetcher_result);
    $this->assertParserResultItemCount($result, 3);

    $this->assertEqual('I am a title<thing>Stuff</thing>', $result->items[0]['title']);
    $this->assertEqual('I am a description0', $result->items[0]['description']);
    $this->assertEqual('I am a title1', $result->items[1]['title']);
    $this->assertEqual('I am a description1', $result->items[1]['description']);
    $this->assertEqual('I am a title2', $result->items[2]['title']);
    $this->assertEqual('I am a description2', $result->items[2]['description']);
  }

  /**
   * Tests raw.
   */
  public function testRaw() {
    $parser = $this->getParserInstance();
    $fetcher_result = new FeedsFetcherResult(file_get_contents($this->moduleDir . '/tests/resources/test.html'));

    $parser->setConfig(array(
      'context' => array(
        'value' => '.post',
      ),
      'sources' => array(
        'title' => array(
          'name' => 'Title',
          'value' => 'h3',
          'attribute' => '',
        ),
        'description' => array(
          'name' => 'Title',
          'value' => 'p',
          'attribute' => '',
          'raw' => TRUE,
        ),
      ),
    ));

    $result = $parser->parse($this->source, $fetcher_result);
    $this->assertParserResultItemCount($result, 3);

    $this->assertEqual('I am a title<thing>Stuff</thing>', $result->items[0]['title']);
    $this->assertEqual('<p>I am a description0</p>', $result->items[0]['description']);
    $this->assertEqual('I am a title1', $result->items[1]['title']);
    $this->assertEqual('<p>I am a description1</p>', $result->items[1]['description']);
    $this->assertEqual('I am a title2', $result->items[2]['title']);
    $this->assertEqual('<p>I am a description2</p>', $result->items[2]['description']);
  }

  /**
   * Tests inner xml.
   */
  public function testInner() {
    $parser = $this->getParserInstance();
    $fetcher_result = new FeedsFetcherResult(file_get_contents($this->moduleDir . '/tests/resources/test.html'));

    $parser->setConfig(array(
      'context' => array(
        'value' => '.post',
      ),
      'sources' => array(
        'title' => array(
          'name' => 'Title',
          'value' => 'h3',
          'attribute' => '',
        ),
        'description' => array(
          'name' => 'Title',
          'value' => 'p',
          'attribute' => '',
          'raw' => TRUE,
          'inner' => TRUE,
        ),
      ),
    ));

    $result = $parser->parse($this->source, $fetcher_result);
    $this->assertParserResultItemCount($result, 3);

    $this->assertEqual('I am a title<thing>Stuff</thing>', $result->items[0]['title']);
    $this->assertEqual('I am a description0', $result->items[0]['description']);
    $this->assertEqual('I am a title1', $result->items[1]['title']);
    $this->assertEqual('I am a description1', $result->items[1]['description']);
    $this->assertEqual('I am a title2', $result->items[2]['title']);
    $this->assertEqual('I am a description2', $result->items[2]['description']);
  }

  /**
   * Tests grabbing an attribute.
   */
  public function testAttributeParsing() {
    $parser = $this->getParserInstance();
    $fetcher_result = new FeedsFetcherResult(file_get_contents($this->moduleDir . '/tests/resources/test.html'));

    $parser->setConfig(array(
      'context' => array(
        'value' => '.post',
      ),
      'sources' => array(
        'title' => array(
          'name' => 'Title',
          'value' => 'h3',
          'attribute' => 'attr',
        ),
        'description' => array(
          'name' => 'Title',
          'value' => 'p',
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
   * Tests parsing a CP866 (Russian) encoded file.
   */
  public function testCP866Encoded() {
    $parser = $this->getParserInstance();
    $fetcher_result = new FeedsFetcherResult(file_get_contents($this->moduleDir . '/tests/resources/test_ru.html'));

    $parser->setConfig(array(
      'context' => array(
        'value' => '.post',
      ),
      'sources' => array(
        'title' => array(
          'name' => 'Title',
          'value' => 'h3',
          'attribute' => '',
        ),
        'description' => array(
          'name' => 'Title',
          'value' => 'p',
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
    $fetcher_result = new FeedsFetcherResult(file_get_contents($this->moduleDir . '/tests/resources/test_jp.html'));

    $parser->setConfig(array(
      'context' => array(
        'value' => '.post',
      ),
      'sources' => array(
        'title' => array(
          'name' => 'Title',
          'value' => 'h3',
          'attribute' => '',
        ),
        'description' => array(
          'name' => 'Title',
          'value' => 'p',
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
   * @return QueryPathHtml
   *   A parser instance.
   */
  protected function getParserInstance() {
    $parser = FeedsConfigurable::instance('QueryPathHtml', strtolower($this->randomName()));
    $parser->setMessenger(new TestMessenger());
    return $parser;
  }

}
