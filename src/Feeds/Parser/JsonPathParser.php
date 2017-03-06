<?php

/**
 * @file
 * Contains \Drupal\feeds_ex\Feeds\Parser\JsonPathParser.
 */

namespace Drupal\feeds_ex\Feeds\Parser;

use \RuntimeException;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\Result\FetcherResultInterface;
use Drupal\feeds\Result\ParserResultInterface;
use Drupal\feeds\StateInterface;
use Drupal\feeds_ex\Utility\JsonUtility;
use Peekmo\JsonPath\JsonStore;

/**
 * Defines a JSON parser using JSONPath.
 *
 * @FeedsParser(
 *   id = "jsonpath",
 *   title = @Translation("JsonPath"),
 *   description = @Translation("Parse JSON with JSONPath.")
 * )
 */
class JsonPathParser extends ParserBase {

  /**
   * {@inheritdoc}
   */
  protected function executeContext(FeedInterface $feed, FetcherResultInterface $fetcher_result, StateInterface $state) {
    $raw = $this->prepareRaw($fetcher_result);
    $parsed = JsonUtility::decodeJsonArray($raw);
    $store = new JsonStore();
    $parsed = $store->get($raw, $this->configuration['context']['value']);

    if (!$state->total) {
      $state->total = count($parsed);
    }

    $start = (int) $state->pointer;
    $state->pointer = $start + $this->configuration['line_limit'];
    return array_slice($parsed, $start, $this->configuration['line_limit']);
  }

  /**
   * {@inheritdoc}
   */
  protected function cleanUp(FeedInterface $feed, ParserResultInterface $result, StateInterface $state) {
    // Calculate progress.
    $state->progress($state->total, $state->pointer);
  }

  /**
   * {@inheritdoc}
   */
  protected function executeSourceExpression($machine_name, $expression, $row) {
    $store = new JsonStore();
    $result = $store->get($row, $expression);

    if (is_scalar($result)) {
      return $result;
    }

    // Return a single value if there's only one value.
    return count($result) === 1 ? reset($result) : $result;
  }

  /**
   * {@inheritdoc}
   */
  protected function validateExpression(&$expression) {
    $expression = trim($expression);
  }

  /**
   * {@inheritdoc}
   */
  protected function startErrorHandling() {
    // Clear the json errors from previous parsing.
    json_decode('');
  }

  /**
   * {@inheritdoc}
   */
  protected function getErrors() {
    if (!function_exists('json_last_error')) {
      return array();
    }

    if (!$error = json_last_error()) {
      return array();
    }

    $message = array(
      'message' => JsonUtility::translateError($error),
      'variables' => array(),
      'severity' => RfcLogLevel::ERROR,
    );
    return array($message);
  }

  /**
   * {@inheritdoc}
   */
  protected function loadLibrary() {
    if (!class_exists('Peekmo\JsonPath\JsonStore')) {
      throw new RuntimeException(t('The JSONPath library is not installed.'));
    }
  }

}
