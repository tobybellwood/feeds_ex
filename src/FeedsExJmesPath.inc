<?php

/**
 * @file
 * Contains FeedsExJmesPath.
 */

use JmesPath\Runtime\AstRuntime;
use JmesPath\SyntaxErrorException;

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
   * {@inheritdoc}
   */
  protected function setUp(FeedsSource $source, FeedsFetcherResult $fetcher_result) {
    $this->jmesPath = new AstRuntime();
  }

  /**
   * {@inheritdoc}
   */
  protected function cleanUp(FeedsSource $source, FeedsParserResult $result) {
    unset($this->jmesPath);
  }

  /**
   * {@inheritdoc}
   */
  protected function executeContext(FeedsSource $source, FeedsFetcherResult $fetcher_result) {
    $raw = $fetcher_result->getRaw();

    if (!strlen($raw)) {
      throw new FeedsExEmptyException();
    }

    if ($encoding = $this->detectEncoding($raw)) {
      $raw = $this->convertEncoding($raw, $encoding);
    }

    $parsed = drupal_json_decode($raw);
    return $this->jmesPath->search($this->config['context']['value'], $parsed);
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
    return count($result) === 1 ? reset($result) : $result;
  }

  /**
   * {@inheritdoc}
   */
  protected function validateExpression(&$expression) {
    $expression = trim($expression);
    if (!$expression) {
      return;
    }

    $parser = new AstRuntime();

    try {
      $parser->search($expression, array());
    }
    catch (SyntaxErrorException $e) {
      return check_plain($e->getMessage());
    }
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

  /**
   * {@inheritdoc}
   */
  protected function loadLibrary() {
    if (!$path = feeds_ex_library_path('jmespath.php', 'vendor/autoload.php')) {
      throw new RuntimeException(t('The JMESPath library is not installed.'));
    }

    require_once DRUPAL_ROOT . '/' . $path;
  }

}
