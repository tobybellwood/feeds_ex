<?php

/**
 * @file
 * Contains \Drupal\feeds_ex\Tests\Feeds\Parser\HtmlWebTest.
 */

namespace Drupal\feeds_ex\Tests\Feeds\Parser;

use Drupal\feeds_ex\UnitTestBase;

/**
 * Integration tests for Html.
 *
 * @group feeds_ex
 */
class HtmlParserWebTest extends FeedsWebTestCase {

  public function setUp() {
    parent::setUp('feeds_ex');
    $this->createImporterConfiguration();
    $this->setSettings('syndication', '', array('content_type' => ''));
    $this->setPlugin('syndication', 'Html');
  }

  /**
   * Tests the full import process.
   */
  public function test() {
    $this->setContext('syndication', '//div[@class="post"]');
    $this->addMappings('syndication', array(
      0 => array(
        'source' => $this->addExpression('syndication', 'h3'),
        'target' => 'title',
      ),
      1 => array(
        'source' => $this->addExpression('syndication', 'p'),
        'target' => 'body',
      ),
    ));

    $this->importUrl('syndication', file_create_url(drupal_get_path('module', 'feeds_ex') . '/tests/resources/test.html'));
    $this->drupalGet('node/1/edit');
    $this->assertFieldByName('title', 'I am a title<thing>Stuff</thing>');
    $this->assertFieldByName('body[und][0][value]', 'I am a description0');
    $this->drupalGet('node/2/edit');
    $this->assertFieldByName('title', 'I am a title1');
    $this->assertFieldByName('body[und][0][value]', 'I am a description1');
    $this->drupalGet('node/3/edit');
    $this->assertFieldByName('title', 'I am a title2');
    $this->assertFieldByName('body[und][0][value]', 'I am a description2');
  }

  /**
   * Sets the form context value.
   *
   * @param string $id
   *   The importer id.
   * @param string $value
   *   The context value.
   */
  protected function setContext($id, $value) {
    $importer = feeds_importer($id);
    $config = $importer->parser->getConfig();
    $config['context']['value'] = $value;
    $importer->parser->setConfig($config);
    $importer->save();
  }

  /**
   * Adds an expression.
   *
   * @param string $id
   *   The importer id.
   * @param string $value
   *   The expression value.
   * @param array $settings
   *   (optional) Settings to configure the expression. Defaults to an empty
   *   array.
   */
  protected function addExpression($id, $value, array $settings = array()) {
    $importer = feeds_importer($id);
    $config = $importer->parser->getConfig();

    if (!isset($settings['weight'])) {
      $weight = end($config['sources']);
      $weight = $weight ? $weight['weight'] + 1 : 0;
      $settings['weight'] = $weight;
    }

    $settings += array('raw' => 0, 'debug' => 0);

    $machine_name = strtolower($this->randomName());

    $config['sources'][$machine_name] = array(
      'name' => $this->randomString(),
      'value' => $value,
    ) + $settings;

    $importer->parser->setConfig($config);
    $importer->save();

    return $machine_name;
  }

}
