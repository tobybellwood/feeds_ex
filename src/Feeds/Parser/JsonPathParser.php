<?php

/**
 * @file
 * Contains \Drupal\feeds_ex\Feeds\Parser\JsonPathParser.
 */

namespace Drupal\feeds_ex\Feeds\Parser;

use Drupal\Core\Logger\RfcLogLevel;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\Result\FetcherResultInterface;
use Drupal\feeds\Result\ParserResultInterface;
use Drupal\feeds\StateInterface;

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
    $parsed = jsonPath($parsed, $this->config['context']['value']);
    if (!is_array($parsed)) {
      throw new RuntimeException(t('The context expression must return an object or array.'));
    }

    if (!$state->total) {
      $state->total = count($parsed);
    }

    $start = (int) $state->pointer;
    $state->pointer = $start + $feed->importer->getLimit();
    return array_slice($parsed, $start, $feed->importer->getLimit());
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
    $result = jsonPath($row, $expression);

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
    if (!$path = feeds_ex_jsonpath_library_path()) {
      throw new RuntimeException(t('The JSONPath library is not installed.'));
    }

    require_once \Drupal::root() . '/' . $path;
  }

}
