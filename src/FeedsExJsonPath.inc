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
  protected function setUp(FeedsSource $source, FeedsFetcherResult $fetcher_result) {
    if (!$path = feeds_ex_jsonpath_library_path()) {
      return;
    }

    require_once DRUPAL_ROOT . '/' . $path;

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
  protected function executeContext() {
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

    switch (json_last_error()) {
      case JSON_ERROR_NONE:
        return array();

      case JSON_ERROR_DEPTH:
        $message_text = 'Maximum stack depth exceeded';
        break;

      case JSON_ERROR_STATE_MISMATCH:
        $message_text = 'Underflow or the modes mismatch';
        break;

      case JSON_ERROR_CTRL_CHAR:
        $message_text = 'Unexpected control character found';
        break;

      case JSON_ERROR_SYNTAX:
        $message_text = 'Syntax error, malformed JSON';
        break;

      case JSON_ERROR_UTF8:
        $message_text = 'Malformed UTF-8 characters, possibly incorrectly encoded';
        break;

      default:
        $message_text = 'Unknown error';
        break;
    }

    $message = array(
      'message' => $message_text,
      'variables' => array(),
      'severity' => WATCHDOG_ERROR,
    );
    return array($message);
  }

}
