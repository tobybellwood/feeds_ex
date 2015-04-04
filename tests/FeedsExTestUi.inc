<?php

/**
 * @file
 * Contains TestUi.
 */

/**
 * A minimal implementation of a parser for UI testing.
 */
class TestUi extends Base {

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
