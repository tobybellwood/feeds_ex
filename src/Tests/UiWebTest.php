<?php

/**
 * @file
 * Contains \Drupal\feeds_ex\Tests\UiWebTest.
 */

namespace Drupal\feeds_ex\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * User interface tests.
 *
 * @group feeds_ex
 */
class UiWebTest extends WebTestBase {

  public static $modules = [
    'feeds_ex',
    'feeds_ex_test',
  ];

  public function setUp() {
    parent::setUp('feeds_ex', 'feeds_ex_test');
    $this->createImporterConfiguration();
    $this->setSettings('syndication', '', array('content_type' => ''));
    $this->setPlugin('syndication', 'TestUi');
  }

  /**
   * Tests basic UI functionality.
   */
  public function test() {
    $path = 'admin/structure/feeds/syndication/settings/TestUi';

    // Set context.
    $edit = array(
      'context[value]' => 'context value',
    );
    $this->drupalPost($path, $edit, t('Save'));
    $this->assertFieldByName(key($edit), reset($edit), 'Context value set.');

    // Add a value.
    $edit = array(
      'add[name]' => 'new name',
      'add[machine_name]' => 'new_value',
      'add[value]' => 'new value',
      'add[debug]' => 1,
    );
    $this->drupalPost(NULL, $edit, t('Save'));
    $this->assertFieldByName('sources[new_value][name]', 'new name', 'New expression name');
    $this->assertFieldByName('sources[new_value][value]', 'new value', 'New expression value');
    $this->assertFieldByName('sources[new_value][debug]', 1, 'Debug value set');

    // Remove the row.
    $edit = array(
      'sources[new_value][remove]' => 1,
    );
    $this->drupalPost(NULL, $edit, t('Save'));
    $this->assertNoFieldByName('sources[new_value][name]');
  }

  /**
   * Tests debug mode.
   */
  public function testDebugMode() {
    $path = 'admin/structure/feeds/syndication/settings/TestUi';

    // Set context.
    $edit = array(
      'context[value]' => 'context value',
      'debug_mode' => 1,
    );
    $this->drupalPost($path, $edit, t('Save'));
    $this->assertFieldByName('context[value]', 'context value', 'Context value set.');
    $this->assertFieldByName('debug_mode', 1, 'Debug mode set.');

    $path = 'import/syndication';

    // Set context.
    $edit = array(
      'feeds[FeedsHTTPFetcher][source]' => 'http://example.com',
      'feeds[TestUi][context][value]' => 'new context value',
    );
    $this->drupalPost($path, $edit, t('Import'));
    $this->assertFieldByName(key($edit), reset($edit), 'Context value set.');

    // Add a value.
    $edit = array(
      'feeds[TestUi][add][name]' => 'new name',
      'feeds[TestUi][add][machine_name]' => 'new_value',
      'feeds[TestUi][add][value]' => 'new value',
      'feeds[TestUi][add][debug]' => 1,
    );
    $this->drupalPost(NULL, $edit, t('Import'));
    $this->assertFieldByName('feeds[TestUi][sources][new_value][name]', 'new name', 'New expression name');
    $this->assertFieldByName('feeds[TestUi][sources][new_value][value]', 'new value', 'New expression value');
    $this->assertFieldByName('feeds[TestUi][sources][new_value][debug]', 1, 'Debug value set');

    // Remove the row.
    $edit = array(
      'feeds[TestUi][sources][new_value][remove]' => 1,
    );
    $this->drupalPost(NULL, $edit, t('Import'));
    $this->assertNoFieldByName('feeds[TestUi][sources][new_value][name]');
  }

}
