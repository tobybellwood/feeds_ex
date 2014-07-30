<?php

/**
 * @file
 * Contains FeedsExJmesPath.
 */

use JmesPath\Runtime\AstRuntime;

/**
 * Parses JSON documents with JMESPath.
 */
class FeedsExJmesPath extends FeedsExBase {

  /**
   * The JMESPath parser.
   *
   * @var \JmesPath\Runtime\RuntimeInterface
   */
  protected $jmesPath;

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
    require_once DRUPAL_ROOT . '/' . feeds_ex_library_path('jmespath.php', 'vendor/autoload.php');

    $this->jmesPath = new AstRuntime();

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
    unset($this->jmesPath);
    unset($this->parsedJson);
  }

  /**
   * {@inheritdoc}
   */
  protected function executeContext() {
    return $this->jmesPath->search($this->config['context']['value'], $this->parsedJson);
  }

  /**
   * {@inheritdoc}
   */
  protected function executeSourceExpression($machine_name, $expression, $row) {
    $result = $this->jmesPath->search($expression, $row);

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
