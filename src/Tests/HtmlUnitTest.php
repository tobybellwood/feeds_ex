<?php

/**
 * @file
 * Contains \Drupal\feeds_ex\Tests\HtmlUnitTest.
 */

namespace Drupal\feeds_ex\Tests;

/**
 * Unit tests for Html.
 *
 * @group feeds_ex
 */
class HtmlUnitTest extends UnitTestBase {

  /**
   * The mocked FeedsSource.
   *
   * @var FeedsSource
   */
  protected $source;

  public function setUp() {
    parent::setUp();

    require_once $this->moduleDir . '/src/Xml.inc';
    require_once $this->moduleDir . '/src/Html.inc';

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
        'value' => '//div[@class="post"]',
      ),
      'sources' => array(
        'title' => array(
          'name' => 'Title',
          'value' => 'h3',
        ),
        'description' => array(
          'name' => 'Description',
          'value' => 'p',
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
   * Tests getting the raw value.
   */
  public function testRaw() {
    $parser = $this->getParserInstance();
    $fetcher_result = new FeedsFetcherResult(file_get_contents($this->moduleDir . '/tests/resources/test.html'));

    $parser->setConfig(array(
      'context' => array(
        'value' => '//div[@class="post"]',
      ),
      'sources' => array(
        'title' => array(
          'name' => 'Title',
          'value' => 'h3',
        ),
        'description' => array(
          'name' => 'Description',
          'value' => 'p',
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
   * Tests innerxml.
   */
  public function testInner() {
    $parser = $this->getParserInstance();
    $fetcher_result = new FeedsFetcherResult(file_get_contents($this->moduleDir . '/tests/resources/test.html'));

    $parser->setConfig(array(
      'context' => array(
        'value' => '//div[@class="post"]',
      ),
      'sources' => array(
        'title' => array(
          'name' => 'Title',
          'value' => 'h3',
        ),
        'description' => array(
          'name' => 'Description',
          'value' => 'p',
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
   * Tests parsing a CP866 (Russian) encoded file.
   */
  public function testCP866Encoded() {
    $parser = $this->getParserInstance();
    $fetcher_result = new FeedsFetcherResult(file_get_contents($this->moduleDir . '/tests/resources/test_ru.html'));

    $parser->setConfig(array(
      'context' => array(
        'value' => '//div[@class="post"]',
      ),
      'sources' => array(
        'title' => array(
          'name' => 'Title',
          'value' => 'h3',
        ),
        'description' => array(
          'name' => 'Title',
          'value' => 'p',
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
        'value' => '//div[@class="post"]',
      ),
      'sources' => array(
        'title' => array(
          'name' => 'Title',
          'value' => 'h3',
        ),
        'description' => array(
          'name' => 'Title',
          'value' => 'p',
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
   * @return Html
   *   A parser instance.
   */
  protected function getParserInstance() {
    $parser = FeedsConfigurable::instance('Html', strtolower($this->randomName()));
    $parser->setMessenger(new TestMessenger());
    return $parser;
  }

}
