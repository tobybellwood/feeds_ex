<?php

/**
 * @file
 * Contains \Drupal\Tests\feeds_ex\Unit\Feeds\Parser\JmesPathLinesParserTest.
 */

namespace Drupal\Tests\feeds_ex\Unit\Feeds\Parser;

use Drupal\Tests\feeds_ex\Unit\UnitTestBase;

/**
 * Unit tests for JmesPathLines.
 *
 * @group feeds_ex
 */
class JmesPathLinesParserTest extends JsonPathLinesParserTest {

  public function setUp() {
    parent::setUp();

    require_once $this->moduleDir . '/src/JmesPath.inc';
    require_once $this->moduleDir . '/src/JmesPathLines.inc';

    $this->source = $this->getMockFeedsSource();
    $this->parser = FeedsConfigurable::instance('JmesPathLines', strtolower($this->randomName()));
    $this->parser->setMessenger(new TestMessenger());

    // Set compile directory manually.
    $this->variableDel('feeds_ex_jmespath_compile_dir');
    $path = file_directory_temp() . '/' . drupal_base64_encode(drupal_random_bytes(40)) . '_feeds_ex_jmespath_dir';
    $this->variableSet('feeds_ex_jmespath_compile_dir', $path);

    $this->parser->setConfig(array(
      'sources' => array(
        'title' => array(
          'name' => 'Title',
          'value' => 'name',
        ),
      ),
    ));
    $this->fetcherResult = new FeedsFileFetcherResult($this->moduleDir . '/tests/resources/test.jsonl');
    // Tests are in JsonPathLinesParserUnitTests.
  }

}
