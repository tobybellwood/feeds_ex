<?php

namespace Drupal\Tests\feeds_ex\Unit\Feeds\Parser;

use Drupal\feeds\Result\RawFetcherResult;
use Drupal\feeds_ex\Feeds\Parser\HtmlParser;
use Drupal\feeds_ex\Messenger\TestMessenger;

/**
 * @coversDefaultClass \Drupal\feeds_ex\Feeds\Parser\HtmlParser
 * @group feeds_ex
 */
class HtmlParserTest extends ParserTestBase {

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $configuration = ['feed_type' => $this->feedType];
    $this->parser = new HtmlParser($configuration, 'html', []);
    $this->parser->setStringTranslation($this->getStringTranslationStub());
    $this->parser->setMessenger(new TestMessenger());
  }

  /**
   * Tests simple parsing.
   */
  public function testSimpleParsing() {
    $fetcher_result = new RawFetcherResult(file_get_contents($this->moduleDir . '/tests/resources/test.html'));

    $config = [
      'context' => [
        'value' => '//div[@class="post"]',
      ],
      'sources' => [
        'title' => [
          'name' => 'Title',
          'value' => 'h3',
        ],
        'description' => [
          'name' => 'Description',
          'value' => 'p',
        ],
      ],
    ] + $this->parser->defaultConfiguration();
    $this->parser->setConfiguration($config);

    $result = $this->parser->parse($this->feed, $fetcher_result, $this->state);
    $this->assertSame(count($result), 3);

    $this->assertSame('I am a title<thing>Stuff</thing>', $result[0]->get('title'));
    $this->assertSame('I am a description0', $result[0]->get('description'));
    $this->assertSame('I am a title1', $result[1]->get('title'));
    $this->assertSame('I am a description1', $result[1]->get('description'));
    $this->assertSame('I am a title2', $result[2]->get('title'));
    $this->assertSame('I am a description2', $result[2]->get('description'));
  }

  /**
   * Tests getting the raw value.
   */
  public function testRaw() {
    $fetcher_result = new RawFetcherResult(file_get_contents($this->moduleDir . '/tests/resources/test.html'));

    $config = [
      'context' => [
        'value' => '//div[@class="post"]',
      ],
      'sources' => [
        'title' => [
          'name' => 'Title',
          'value' => 'h3',
        ],
        'description' => [
          'name' => 'Description',
          'value' => 'p',
          'raw' => TRUE,
        ],
      ],
    ] + $this->parser->defaultConfiguration();
    $this->parser->setConfiguration($config);

    $result = $this->parser->parse($this->feed, $fetcher_result, $this->state);
    $this->assertSame(count($result), 3);

    $this->assertSame('I am a title<thing>Stuff</thing>', $result[0]->get('title'));
    $this->assertSame('<p>I am a description0</p>', $result[0]->get('description'));
    $this->assertSame('I am a title1', $result[1]->get('title'));
    $this->assertSame('<p>I am a description1</p>', $result[1]->get('description'));
    $this->assertSame('I am a title2', $result[2]->get('title'));
    $this->assertSame('<p>I am a description2</p>', $result[2]->get('description'));
  }

  /**
   * Tests innerxml.
   */
  public function testInner() {
    $fetcher_result = new RawFetcherResult(file_get_contents($this->moduleDir . '/tests/resources/test.html'));

    $config = [
      'context' => [
        'value' => '//div[@class="post"]',
      ],
      'sources' => [
        'title' => [
          'name' => 'Title',
          'value' => 'h3',
        ],
        'description' => [
          'name' => 'Description',
          'value' => 'p',
          'raw' => TRUE,
          'inner' => TRUE,
        ],
      ],
    ] + $this->parser->defaultConfiguration();
    $this->parser->setConfiguration($config);

    $result = $this->parser->parse($this->feed, $fetcher_result, $this->state);
    $this->assertSame(count($result), 3);

    $this->assertSame('I am a title<thing>Stuff</thing>', $result[0]->get('title'));
    $this->assertSame('I am a description0', $result[0]->get('description'));
    $this->assertSame('I am a title1', $result[1]->get('title'));
    $this->assertSame('I am a description1', $result[1]->get('description'));
    $this->assertSame('I am a title2', $result[2]->get('title'));
    $this->assertSame('I am a description2', $result[2]->get('description'));
  }

  /**
   * Tests parsing a CP866 (Russian) encoded file.
   */
  public function testCP866Encoded() {
    $fetcher_result = new RawFetcherResult(file_get_contents($this->moduleDir . '/tests/resources/test_ru.html'));

    $config = [
      'context' => [
        'value' => '//div[@class="post"]',
      ],
      'sources' => [
        'title' => [
          'name' => 'Title',
          'value' => 'h3',
        ],
        'description' => [
          'name' => 'Title',
          'value' => 'p',
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
    $fetcher_result = new RawFetcherResult(file_get_contents($this->moduleDir . '/tests/resources/test_jp.html'));

    $config = [
      'context' => [
        'value' => '//div[@class="post"]',
      ],
      'sources' => [
        'title' => [
          'name' => 'Title',
          'value' => 'h3',
        ],
        'description' => [
          'name' => 'Title',
          'value' => 'p',
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
   * Tests that the link property is set.
   *
   * @todo turned off, because unsure if this is still needed.
   */
  public function _testLinkIsSet() {
    $this->setProperty($this->feed, 'config', [
      'FeedsFileFetcher' => [
        'source' => 'file fetcher source path',
      ],
    ]);

    $config = ['context' => ['value' => '/beep']];

    $result = $this->parser->parse($this->feed, new RawFetcherResult('<?xml version="1.0" encoding="UTF-8"?><item></item>'));
    $this->assertSame($result->link, 'file fetcher source path');
  }

  /**
   * Tests empty feed handling.
   */
  public function testEmptyFeed() {
    $this->parser->parse($this->feed, new RawFetcherResult(' '), $this->state);
    $this->assertEmptyFeedMessage($this->parser->getMessenger()->getMessages());
  }

}
