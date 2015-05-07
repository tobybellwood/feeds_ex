<?php

/**
 * @file
 * Contains \Drupal\Tests\feeds_ex\Unit\Feeds\Parser\XmlParserTest.
 */

namespace Drupal\Tests\feeds_ex\Unit\Feeds\Parser;

use Drupal\feeds\Result\RawFetcherResult;
use Drupal\feeds_ex\Feeds\Parser\XmlParser;
use Drupal\feeds_ex\Messenger\TestMessenger;

/**
 * @coversDefaultClass \Drupal\feeds_ex\Feeds\Parser\XmlParser
 * @group feeds_ex
 */
class XmlParserTest extends ParserTestBase {

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $configuration = ['feed_type' => $this->feedType];
    $this->parser = new XmlParser($configuration, 'xml', []);
    $this->parser->setStringTranslation($this->getStringTranslationStub());
    $this->parser->setMessenger(new TestMessenger());
  }

  /**
   * Tests simple parsing.
   */
  public function testSimpleParsing() {
    $fetcher_result = new RawFetcherResult(file_get_contents($this->moduleDir . '/tests/resources/test.xml'));

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
    $this->parser->setConfiguration($config);

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
    $fetcher_result = new RawFetcherResult(file_get_contents($this->moduleDir . '/tests/resources/test.xml'));

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
          'raw' => TRUE,
        ],
      ],
    ] + $this->parser->defaultConfiguration();
    $this->parser->setConfiguration($config);

    $result = $this->parser->parse($this->feed, $fetcher_result, $this->state);
    $this->assertSame(count($result), 3);

    foreach ($result as $delta => $item) {
      $this->assertSame('I am a title' . $delta, $item->get('title'));
      $this->assertSame('<description><text>I am a description' . $delta . '</text></description>', $item->get('description'));
    }
  }

  /**
   * Tests simple parsing.
   */
  public function testInner() {
    $fetcher_result = new RawFetcherResult(file_get_contents($this->moduleDir . '/tests/resources/test.xml'));

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
          'raw' => TRUE,
          'inner' => TRUE,
        ],
      ],
    ] + $this->parser->defaultConfiguration();
    $this->parser->setConfiguration($config);

    $result = $this->parser->parse($this->feed, $fetcher_result, $this->state);
    $this->assertSame(count($result), 3);

    foreach ($result as $delta => $item) {
      $this->assertSame('I am a title' . $delta, $item->get('title'));
      $this->assertSame('<text>I am a description' . $delta . '</text>', $item->get('description'));
    }
  }

  /**
   * Tests parsing a CP866 (Russian) encoded file.
   */
  public function testCP866Encoded() {
    $fetcher_result = new RawFetcherResult(file_get_contents($this->moduleDir . '/tests/resources/test_ru.xml'));

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
    $this->parser->setConfiguration($config);

    $result = $this->parser->parse($this->feed, $fetcher_result, $this->state);
    $this->assertSame(count($result), 3);

    foreach ($result as $delta => $item) {
      $this->assertSame('Я название' . $delta, $item->get('title'));
      $this->assertSame('Я описание' . $delta, $item->get('description'));
    }
  }

  /**
   * Tests a EUC-JP (Japanese) encoded file without the encoding declaration.
   *
   * This implicitly tests Base's encoding conversion.
   */
  public function testEUCJPEncodedNoDeclaration() {
    $fetcher_result = new RawFetcherResult(file_get_contents($this->moduleDir . '/tests/resources/test_jp.xml'));

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
      'source_encoding' => ['EUC-JP'],
    ] + $this->parser->defaultConfiguration();
    $this->parser->setConfiguration($config);

    $result = $this->parser->parse($this->feed, $fetcher_result, $this->state);
    $this->assertSame(count($result), 3);

    foreach ($result as $delta => $item) {
      $this->assertSame('私はタイトルです' . $delta, $item->get('title'));
      $this->assertSame('私が説明してい' . $delta, $item->get('description'));
    }
  }

  /**
   * Tests batching.
   */
  public function testBatching() {
    $fetcher_result = new RawFetcherResult(file_get_contents($this->moduleDir . '/tests/resources/test.xml'));

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
      'line_limit' => 1,
    ] + $this->parser->defaultConfiguration();
    $this->parser->setConfiguration($config);

    foreach (range(0, 2) as $delta) {
      $result = $this->parser->parse($this->feed, $fetcher_result, $this->state);
      $this->assertSame(count($result), 1);
      $this->assertSame('I am a title' . $delta, $result[0]->get('title'));
      $this->assertSame('I am a description' . $delta, $result[0]->get('description'));
    }

    // Should be empty.
    $result = $this->parser->parse($this->feed, $fetcher_result, $this->state);
    $this->assertSame(count($result), 0);
  }

  /**
   * Tests that the link propery is set.
   *
   * @todo replace setProperty().
   */
  public function _testLinkIsSet() {
    $this->setProperty($this->feed, 'config', [
      'FeedsFileFetcher' => [
        'source' => 'file fetcher source path',
      ],
    ]);

    $this->parser = $this->getParserInstance();
    $this->parser->setConfiguration(['context' => ['value' => '/beep']]);

    $result = $this->parser->parse($this->feed, new RawFetcherResult('<?xml version="1.0" encoding="UTF-8"?><item></item>'));
    $this->assertSame($result->link, 'file fetcher source path');
  }

  /**
   * Tests XPath validation.
   */
  public function testValidateExpression() {
    // Invalid expression.
    $expression = ['!!'];
    $this->assertSame('Invalid expression', $this->invokeMethod($this->parser, 'validateExpression', $expression));

    // Test that value was trimmed.
    $this->assertSame($expression[0], '!!', 'Value was trimmed.');

    // Unknown namespace.
    $this->assertSame(NULL, $this->invokeMethod($this->parser, 'validateExpression', ['thing:asdf']));

    // Empty.
    $this->assertSame(NULL, $this->invokeMethod($this->parser, 'validateExpression', ['']));
  }

  /**
   * Tests empty feed handling.
   */
  public function testEmptyFeed() {
    $this->parser->parse($this->feed, new RawFetcherResult(' '), $this->state);
    $messages = $this->parser->getMessenger()->getMessages();
    $this->assertSame(1, count($messages), 'The expected number of messages.');
    $this->assertSame($messages[0]['message'], 'The feed is empty.', 'Message text is correct.');
    $this->assertSame($messages[0]['type'], 'warning', 'Message type is warning.');
    $this->assertFalse($messages[0]['repeat'], 'Repeat is set to false.');
  }

}
