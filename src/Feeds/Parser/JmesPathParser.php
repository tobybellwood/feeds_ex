<?php

/**
 * @file
 * Contains \Drupal\feeds_ex\Feeds\Parser\JmesPathParser.
 */

namespace Drupal\feeds_ex\Feeds\Parser;

/**
 * Defines a JSON parser using JMESPath.
 *
 * @FeedsParser(
 *   id = "jmespath",
 *   title = @Translation("JSON JMESPath"),
 *   description = @Translation("Parse JSON with JMESPath.")
 * )
 */
class JmesPathParser extends ParserBase {

  /**
   * The JMESPath parser.
   *
   * @var \JmesPath\Runtime\RuntimeInterface
   */
  protected $jmesPath;

  /**
   * The directory JmesPath uses to store generated code.
   *
   * @var string
   */
  protected $compileDirectory;

  /**
   * {@inheritdoc}
   */
  protected function setUp(FeedsSource $source, FeedsFetcherResult $fetcher_result) {
    // This is probably overly paranoid, but safety first.
    if (!$path = $this->getCompileDirectory()) {
      $path = file_directory_temp() . '/' . drupal_base64_encode(\Drupal\Component\Utility\Crypt::randomBytes(40)) . '_feeds_ex_jmespath_dir';
    }
    try {
      $this->jmesPath = new CompilerRuntime(array('dir' => $path));
    }
    catch (RuntimeException $e) {
      $this->jmesPath = new AstRuntime();
      $path = FALSE;
    }
    $this->setCompileDirectory($path);
  }

  /**
   * Returns the compilation directory.
   *
   * @return string
   *   The directory JmesPath uses to store generated code.
   */
  protected function getCompileDirectory() {
    if (!isset($this->compileDirectory)) {
      // @FIXME
// Could not extract the default value because it is either indeterminate, or
// not scalar. You'll need to provide a default value in
// config/install/feeds_ex.settings.yml and config/schema/feeds_ex.schema.yml.
$this->compileDirectory = \Drupal::config('feeds_ex.settings')->get('feeds_ex_jmespath_compile_dir');
    }

    return $this->compileDirectory;
  }

  /**
   * Sets the compilation directory.
   *
   * @param string $directory
   *   The directory JmesPath uses to store generated code.
   */
  protected function setCompileDirectory($directory) {
    if ($this->compileDirectory !== $directory) {
      $this->compileDirectory = $directory;
      \Drupal::configFactory()->getEditable('feeds_ex.settings')->set('feeds_ex_jmespath_compile_dir', $directory)->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function executeContext(FeedsSource $source, FeedsFetcherResult $fetcher_result) {
    $raw = $this->prepareRaw($fetcher_result);
    $parsed = JsonUtility::decodeJsonArray($raw);
    $parsed = $this->jmesPath->search($this->config['context']['value'], $parsed);
    if (!is_array($parsed)) {
      throw new RuntimeException(t('The context expression must return an object or array.'));
    }

    $state = $source->state(FEEDS_PARSE);
    if (!$state->total) {
      $state->total = count($parsed);
    }

    // @todo Consider using array slice syntax when it is supported.
    $start = (int) $state->pointer;
    $state->pointer = $start + $source->importer->getLimit();
    return array_slice($parsed, $start, $source->importer->getLimit());
  }

  /**
   * {@inheritdoc}
   */
  protected function cleanUp(FeedsSource $source, FeedsParserResult $result) {
    unset($this->jmesPath);
    // Calculate progress.
    $state = $source->state(FEEDS_PARSE);
    $state->progress($state->total, $state->pointer);
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
    if (!strlen($expression)) {
      return;
    }

    $parser = new AstRuntime();

    try {
      $parser->search($expression, array());
    }
    catch (SyntaxErrorException $e) {
      // Remove newlines after nl2br() to make testing easier.
      return str_replace("\n", '', nl2br(check_plain(trim($e->getMessage()))));
    }
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

    require_once \Drupal::root() . '/' . $path;
  }

}
