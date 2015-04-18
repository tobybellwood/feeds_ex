<?php

/**
 * @file
 * Contains \Drupal\feeds_ex_test\Feeds\Parser\TestUiParser.
 */

namespace Drupal\feeds_ex_test\Feeds\Parser;

use Drupal\feeds_ex\Feeds\Parser\ParserBase;

/**
 * A minimal implementation of a parser for UI testing.
 *
 * @FeedsParser(
 *   id = "feeds_ex_test_ui",
 *   title = @Translation("Test UI parser"),
 *   description = @Translation("Do not use this.")
 * )
 */
class TestUiParser extends ParserBase {

  /**
   * {@inheritdoc}
   */
  protected function executeContext(FeedsSource $source, FeedsFetcherResult $fetcher_result) {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  protected function executeSourceExpression($machine_name, $expression, $row) {
  }

  /**
   * {@inheritdoc}
   */
  protected function validateExpression(&$expression) {
  }

  /**
   * {@inheritdoc}
   */
  protected function getErrors() {
    return array();
  }

}
