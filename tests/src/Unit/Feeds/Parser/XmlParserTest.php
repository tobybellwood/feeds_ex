<?php

/**
 * @file
 * Contains \Drupal\Tests\feeds_ex\Unit\Feeds\Parser\XmlParserTest.
 */

namespace Drupal\Tests\feeds_ex\Unit\Feeds\Parser;

use Drupal\feeds\Result\RawFetcherResult;
use Drupal\feeds\State;
use Drupal\feeds_ex\Feeds\Parser\XmlParser;
use Drupal\feeds_ex\Messenger\TestMessenger;
use Drupal\Tests\feeds_ex\Unit\UnitTestBase;

/**
 * @coversDefaultClass \Drupal\feeds_ex\Feeds\Parser\XmlParser
 * @group feeds_ex
 */
class XmlParserTest extends UnitTestBase {

  /**
   * @var \Drupal\feeds_ex\Feeds\Parser\XmlParser
   */
  protected $parser;

  /**
   * @var \Drupal\feeds\FeedTypeInterface
   */
  protected $feedType;

  /**
   * @var \Drupal\feeds\FeedInterface
   */
  protected $feed;

  /**
   * @var \Drupal\feeds\State
   */
  protected $state;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->feedType = $this->getMock('Drupal\feeds\FeedTypeInterface');
    $configuration = ['feed_type' => $this->feedType];
    $this->parser = new XmlParser($configuration, 'xml', []);
    $this->parser->setStringTranslation($this->getStringTranslationStub());
    $this->parser->setMessenger(new TestMessenger());

    $this->state = new State();

    $this->feed = $this->getMock('Drupal\feeds\FeedInterface');
    $this->feed->expects($this->any())
      ->method('getType')
      ->will($this->returnValue($this->feedType));
  }

  /**
   * Tests simple parsing.
   */
  public function testSimpleParsing() {
    $file = dirname(dirname(dirname(dirname(dirname(dirname(__FILE__)))))) . '/tests/resources/test.xml';
    $fetcher_result = new RawFetcherResult(file_get_contents($file));

    $config = [
      'context' => [
        'value' => '/items/item',
      ],
      'sources' => [
        'title' => [
          'name' => 'Title',
          'value' => 'title',
        ],
        'description' => [
          'name' => 'Title',
          'value' => 'description',
        ],
      ],
    ] + $this->parser->defaultConfiguration();

    $this->feed->expects($this->any())
      ->method('getConfigurationFor')
      ->with($this->parser)
      ->will($this->returnValue($config));

    $result = $this->parser->parse($this->feed, $fetcher_result, $this->state);
    $this->assertSame(count($result), 3);

    foreach ($result as $delta => $item) {
      $this->assertSame('I am a title' . $delta, $item->get('title'));
      $this->assertSame('I am a description' . $delta, $item->get('description'));
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
        'value' => '/items/item',
      ),
      'sources' => array(
        'title' => array(
          'name' => 'Title',
          'value' => 'title',
        ),
        'description' => array(
          'name' => 'Title',
          'value' => 'description',
          'raw' => TRUE,
        ),
      ),
    ));

    $result = $parser->parse($this->source, $fetcher_result);
    $this->assertParserResultItemCount($result, 3);

    foreach ($result->items as $delta => $item) {
      $this->assertEqual('I am a title' . $delta, $item->get('title'));
      $this->assertEqual('<description><text>I am a description' . $delta . '</text></description>', $item->get('description'));
    }
  }

  /**
   * Tests simple parsing.
   */
  public function testInner() {
    $parser = $this->getParserInstance();
    $fetcher_result = new FeedsFetcherResult(file_get_contents($this->moduleDir . '/tests/resources/test.xml'));

    $parser->setConfig(array(
      'context' => array(
        'value' => '/items/item',
      ),
      'sources' => array(
        'title' => array(
          'name' => 'Title',
          'value' => 'title',
        ),
        'description' => array(
          'name' => 'Title',
          'value' => 'description',
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
   * Tests parsing a CP866 (Russian) encoded file.
   */
  public function testCP866Encoded() {
    $parser = $this->getParserInstance();
    $fetcher_result = new FeedsFetcherResult(file_get_contents($this->moduleDir . '/tests/resources/test_ru.xml'));

    $parser->setConfig(array(
      'context' => array(
        'value' => '/items/item',
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
        'value' => '/items/item',
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
   * Tests batching.
   */
  public function testBatching() {
    $parser = $this->getParserInstance();
    $fetcher_result = new FeedsFetcherResult(file_get_contents($this->moduleDir . '/tests/resources/test.xml'));

    $parser->setConfig(array(
      'context' => array(
        'value' => '/items/item',
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

    $this->variableSet('feeds_process_limit', 1);

    foreach (range(0, 2) as $delta) {
      $result = $parser->parse($this->source, $fetcher_result);
      $this->assertParserResultItemCount($result, 1);
      $this->assertEqual('I am a title' . $delta, $result->items[0]['title']);
      $this->assertEqual('I am a description' . $delta, $result->items[0]['description']);
    }

    // Should be empty.
    $result = $parser->parse($this->source, $fetcher_result);
    $this->assertParserResultItemCount($result, 0);
  }

  /**
   * Tests that the link propery is set.
   */
  public function testLinkIsSet() {
    $this->setProperty($this->source, 'config', array(
      'FeedsFileFetcher' => array(
        'source' => 'file fetcher source path',
      ),
    ));

    $parser = $this->getParserInstance();
    $parser->setConfig(array('context' => array('value' => '/beep')));

    $result = $parser->parse($this->source, new FeedsFetcherResult('<?xml version="1.0" encoding="UTF-8"?><item></item>'));
    $this->assertEqual($result->link, 'file fetcher source path');
  }

  /**
   * Tests XPath validation.
   */
  public function testValidateExpression() {
    // Invalid expression.
    $parser = $this->getParserInstance();
    $expression = array('!!');
    $this->assertEqual('Invalid expression', $this->invokeMethod($parser, 'validateExpression', $expression));

    // Test that value was trimmed.
    $this->assertEqual($expression[0], '!!', 'Value was trimmed.');

    // Unknown namespace.
    $this->assertEqual(NULL, $this->invokeMethod($parser, 'validateExpression', array('thing:asdf')));

    // Empty.
    $this->assertEqual(NULL, $this->invokeMethod($parser, 'validateExpression', array('')));
  }

  /**
   * Tests empty feed handling.
   */
  public function testEmptyFeed() {
    $parser = $this->getParserInstance();
    $parser->parse($this->source, new FeedsFetcherResult(' '));
    $messages = $parser->getMessenger()->getMessages();
    $this->assertEqual(1, count($messages), 'The expected number of messages.');
    $this->assertEqual($messages[0]['message'], 'The feed is empty.', 'Message text is correct.');
    $this->assertEqual($messages[0]['type'], 'warning', 'Message type is warning.');
    $this->assertFalse($messages[0]['repeat'], 'Repeat is set to false.');
  }

}
