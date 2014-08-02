<?php

/**
 * @file
 * Contains FeedsExJsonPath.
 */

/**
 * Parses JSON via JSONPath.
 */
class FeedsExJsonPath extends FeedsExBase {

  /**
   * The parsed JSON.
   *
   * @var array
   */
  protected $parsedJson;

  /**
   * {@inheritdoc}
   */
  protected function __construct($id) {
    parent::__construct($id);
    if (!$path = feeds_ex_jsonpath_library_path()) {
      drupal_set_message(t('The JSONPath library is not installed.'), 'error', FALSE);
      return;
    }

    require_once DRUPAL_ROOT . '/' . $path;
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp(FeedsSource $source, FeedsFetcherResult $fetcher_result) {
    $raw = $fetcher_result->getRaw();
    if ($encoding = $this->detectEncoding($raw)) {
      $raw = $this->convertEncoding($raw, $encoding);
    }

    $this->parsedJson = drupal_json_decode($raw);
  }

  /**
   * {@inheritdoc}
   */
  protected function cleanUp(FeedsSource $source, FeedsParserResult $result) {
    unset($this->parsedJson);
  }

  /**
   * {@inheritdoc}
   */
  protected function executeContext(FeedsSource $source, FeedsFetcherResult $fetcher_result) {
    return jsonPath($this->parsedJson, $this->config['context']['value']);
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
    return count($result) === 1 ? $result[0] : $result;
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
  protected function getErrors() {
    if (!function_exists('json_last_error')) {
      return array();
    }

    if (!$error = json_last_error()) {
      return array();
    }

    $message = array(
      'message' => FeedsExJsonUtility::translateError($error),
      'variables' => array(),
      'severity' => WATCHDOG_ERROR,
    );
    return array($message);
  }

}
