<?php

/**
 * @file
 * Contains \Drupal\feeds_ex\Feeds\Parser\ParserBase.
 */

namespace Drupal\feeds_ex\Feeds\Parser;

use \EmptyException;
use \Exception;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\Plugin\Type\ConfigurablePluginBase;
use Drupal\feeds\Plugin\Type\FeedPluginFormInterface;
use Drupal\feeds\Plugin\Type\Parser\ParserInterface;
use Drupal\feeds\Result\FetcherResultInterface;
use Drupal\feeds\Result\ParserResult;
use Drupal\feeds\Result\ParserResultInterface;
use Drupal\feeds\StateInterface;
use Drupal\feeds_ex\Encoder\EncoderInterface;

/**
 * The Feeds extensible parser.
 */
abstract class ParserBase extends ConfigurablePluginBase implements FeedPluginFormInterface, ParserInterface {

  /**
   * The object used to display messages to the user.
   *
   * @var MessengerInterface
   */
  protected $messenger;

  /**
   * The class used as the text encoder.
   *
   * @var string
   */
  protected $encoderClass = '\Drupal\feeds_ex\Encoder\TextEncoder';

  /**
   * The encoder used to convert encodings.
   *
   * @var EncoderInterface
   */
  protected $encoder;

  /**
   * Returns rows to be parsed.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   Source information.
   * @param \Drupal\feeds\Result\FetcherResultInterface $fetcher_result
   *   The result returned by the fetcher.
   * @param \Drupal\feeds\StateInterface $state
   *   The state object.
   *
   * @return array|Traversable
   *   Some iterable that returns rows.
   */
  abstract protected function executeContext(FeedInterface $feed, FetcherResultInterface $fetcher_result, StateInterface $state);

  /**
   * Executes a single source expression.
   *
   * @param string $machine_name
   *   The source machine name being executed.
   * @param string $expression
   *   The expression to execute.
   * @param mixed $row
   *   The row to execute on.
   *
   * @return scalar|[]scalar
   *   Either a scalar, or a list of scalars. If null, the value will be
   *   ignored.
   */
  abstract protected function executeSourceExpression($machine_name, $expression, $row);

  /**
   * Validates an expression.
   *
   * @param string &$expression
   *   The expression to validate.
   *
   * @return string|null
   *   Return the error string, or null if validation was passed.
   */
  abstract protected function validateExpression(&$expression);

  /**
   * Returns the errors after parsing.
   *
   * @return array
   *   A structured array array with keys:
   *   - message: The error message.
   *   - variables: The variables for the message.
   *   - severity: The severity of the message.
   *
   * @see watchdog()
   */
  abstract protected function getErrors();

  /**
   * Allows subclasses to prepare for parsing.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed we are parsing for.
   * @param \Drupal\feeds\Result\FetcherResultInterface $fetcher_result
   *   The result of the fetching stage.
   * @param \Drupal\feeds\StateInterface $state
   *   The state object.
   */
  protected function setUp(FeedInterface $feed, FetcherResultInterface $fetcher_result, StateInterface $state) {
  }

  /**
   * Allows subclasses to cleanup after parsing.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed we are parsing for.
   * @param \Drupal\feeds\Result\ParserResultInterface $parser_result
   *   The result of parsing.
   * @param \Drupal\feeds\StateInterface $state
   *   The state object.
   */
  protected function cleanUp(FeedInterface $feed, ParserResultInterface $parser_result, StateInterface $state) {
  }

  /**
   * Starts internal error handling.
   *
   * Subclasses can override this to being error handling.
   */
  protected function startErrorHandling() {
  }

  /**
   * Stops internal error handling.
   *
   * Subclasses can override this to end error handling.
   */
  protected function stopErrorHandling() {
  }

  /**
   * Loads the necessary library.
   *
   * Subclasses can override this to load the necessary library. It will be
   * called automatically.
   *
   * @throws RuntimeException
   *   Thrown if the library does not exist.
   */
  protected function loadLibrary() {
  }

  /**
   * Returns whether or not this parser uses a context query.
   *
   * Sub-classes can return false here if they don't require a user-configured
   * context query.
   *
   * @return bool
   *   True if the parser uses a context query and false if not.
   */
  protected function hasConfigurableContext() {
    return TRUE;
  }

  /**
   * Reuturns the list of table headers.
   *
   * @return array
   *   A list of header names keyed by the form keys.
   */
  protected function configFormTableHeader() {
    return array();
  }

  /**
   * Returns a form element for a specific column.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   * @param array $values
   *   The individual source item values.
   * @param string $column
   *   The name of the column.
   * @param string $machine_name
   *   The machine name of the source.
   *
   * @return array
   *   A single form element.
   */
  protected function configFormTableColumn(FormStateInterface $form_state, array $values, $column, $machine_name) {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function parse(FeedInterface $feed, FetcherResultInterface $fetcher_result, StateInterface $state) {
    $this->loadLibrary();
    $this->startErrorHandling();
    $result = new ParserResult();
    // Set link.
    $fetcher_config = $feed->getConfigFor($feed->importer->fetcher);
    $result->link = is_string($fetcher_config['source']) ? $fetcher_config['source'] : '';

    try {
      $this->setUp($feed, $fetcher_result, $state);
      $this->parseItems($feed, $fetcher_result, $result, $state);
      $this->cleanUp($feed, $result, $state);
    }
    catch (EmptyException $e) {
      // The feed is empty.
      $this->getMessenger()->setMessage(t('The feed is empty.'), 'warning', FALSE);
    }
    catch (Exception $exception) {
      // Do nothing. Store for later.
    }

    // Display and log errors.
    $errors = $this->getErrors();
    $this->printErrors($errors, $this->config['display_errors'] ? RfcLogLevel::DEBUG : RfcLogLevel::ERROR);
    $this->logErrors($feed, $errors);

    $this->stopErrorHandling();

    if (isset($exception)) {
      throw $exception;
    }

    return $result;
  }

  /**
   * Performs the actual parsing.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed source.
   * @param \Drupal\feeds\Result\FetcherResultInterface $fetcher_result
   *   The fetcher result.
   * @param \Drupal\feeds\Result\ParserResultInterface $result
   *   The parser result object to populate.
   * @param \Drupal\feeds\StateInterface $state
   *   The state object.
   */
  protected function parseItems(FeedInterface $feed, FetcherResultInterface $fetcher_result, ParserResultInterface $result, StateInterface $state) {
    $expressions = $this->prepareExpressions();
    $variable_map = $this->prepareVariables($expressions);

    foreach ($this->executeContext($feed, $fetcher_result, $state) as $row) {
      if ($item = $this->executeSources($row, $expressions, $variable_map)) {
        $result->items[] = $item;
      }
    }
  }

  /**
   * Prepares the expressions for parsing.
   *
   * At this point we just remove empty expressions.
   *
   * @return array
   *   A map of machine name to expression.
   */
  protected function prepareExpressions() {
    $expressions = array();
    foreach ($this->config['sources'] as $machine_name => $source) {
      if (strlen($source['value'])) {
        $expressions[$machine_name] = $source['value'];
      }
    }

    return $expressions;
  }

  /**
   * Prepares the variable map used to substitution.
   *
   * @param array $expressions
   *   The expressions being parsed.
   *
   * @return array
   *   A map of machine name to variable name.
   */
  protected function prepareVariables(array $expressions) {
    $variable_map = array();
    foreach ($expressions as $machine_name => $expression) {
      $variable_map[$machine_name] = '$' . $machine_name;
    }
    return $variable_map;
  }

  /**
   * Executes the source expressions.
   *
   * @param mixed $row
   *   A single item returned from the context expression.
   * @param array $expressions
   *   A map of machine name to expression.
   * @param array $variable_map
   *   A map of machine name to varible name.
   *
   * @return array
   *   The fully-parsed item array.
   */
  protected function executeSources($row, array $expressions, array $variable_map) {
    $item = array();
    $variables = array();

    foreach ($expressions as $machine_name => $expression) {
      // Variable substitution.
      $expression = strtr($expression, $variables);

      $result = $this->executeSourceExpression($machine_name, $expression, $row);

      if (!empty($this->config['sources'][$machine_name]['debug'])) {
        $this->debug($result, $machine_name);
      }

      if ($result === NULL) {
        $variables[$variable_map[$machine_name]] = '';
        continue;
      }

      $item[$machine_name] = $result;
      $variables[$variable_map[$machine_name]] = is_array($result) ? reset($result) : $result;
    }

    return $item;
  }

  /**
   * Prints errors to the screen.
   *
   * @param array $errors
   *   A list of errors as returned by stopErrorHandling().
   * @param int $severity
   *   (optional) Limit to only errors of the specified severity. Defaults to
   *   RfcLogLevel::ERROR.
   *
   * @see watchdog()
   */
  protected function printErrors(array $errors, $severity = RfcLogLevel::ERROR) {
    foreach ($errors as $error) {
      if ($error['severity'] > $severity) {
        continue;
      }
      $this->getMessenger()->setMessage(t($error['message'], $error['variables']), $error['severity'] <= RfcLogLevel::ERROR ? 'error' : 'warning', FALSE);
    }
  }

  /**
   * Logs errors.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed source being importerd.
   * @param array $errors
   *   A list of errors as returned by stopErrorHandling().
   * @param int $severity
   *   (optional) Limit to only errors of the specified severity. Defaults to
   *   RfcLogLevel::ERROR.
   *
   * @see watchdog()
   */
  protected function logErrors(FeedInterface $feed, array $errors, $severity = RfcLogLevel::ERROR) {
    foreach ($errors as $error) {
      if ($error['severity'] > $severity) {
        continue;
      }

      $feed->log('feeds_ex', $error['message'], $error['variables'], $error['severity']);
    }
  }

  /**
   * Prepares the raw string for parsing.
   *
   * @param \Drupal\feeds\Result\FetcherResultInterface $fetcher_result
   *   The fetcher result.
   *
   * @return string
   *   The prepared raw string.
   */
  protected function prepareRaw(FetcherResultInterface $fetcher_result) {
    $raw = trim($this->getEncoder()->convertEncoding($fetcher_result->getRaw()));

    if (!strlen($raw)) {
      throw new EmptyException();
    }

    return $raw;
  }

  /**
   * Renders our debug messages into a list.
   *
   * @param mixed $data
   *   The result of an expression. Either a scalar or a list of scalars.
   * @param string $machine_name
   *   The source key that produced this query.
   */
  protected function debug($data, $machine_name) {
    $name = $machine_name;
    if ($this->config['sources'][$machine_name]['name']) {
      $name = $this->config['sources'][$machine_name]['name'];
    }

    $output = '<strong>' . $name . ':</strong>';
    $data = is_array($data) ? $data : array($data);
    foreach ($data as $key => $value) {
      $data[$key] = check_plain($value);
    }
    $output .= _theme('item_list', array('items' => $data));
    $this->getMessenger()->setMessage($output);
  }

  /**
   * {@inheritdoc}
   */
  public function getMappingSources() {
    return parent::getMappingSources() + $this->config['sources'];
  }

  /**
   * {@inheritdoc}
   */
  public function configDefaults() {
    return array(
      'sources' => array(),
      'context' => array(
        'value' => '',
      ),
      'display_errors' => FALSE,
      'source_encoding' => array('auto'),
      'debug_mode' => FALSE,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function configForm(FormStateInterface $form_state) {
    $form = array(
      '#tree' => TRUE,
      '#theme' => 'feeds_ex_configuration_table',
      '#prefix' => '<div id="feeds-ex-configuration-wrapper">',
      '#suffix' => '</div>',
    );

    if ($this->hasConfigurableContext()) {
      $form['context']['name'] = array(
        '#type' => 'markup',
        '#markup' => t('Context'),
      );
      $form['context']['value'] = array(
        '#type' => 'textfield',
        '#title' => t('Context value'),
        '#title_display' => 'invisible',
        '#default_value' => $this->config['context']['value'],
        '#size' => 50,
        '#required' => TRUE,
        // We're hiding the title, so add a little hint.
        '#description' => '<span class="form-required">*</span>',
        '#attributes' => array('class' => array('feeds-ex-context-value')),
        '#maxlength' => 1024,
      );
    }

    $form['sources'] = array(
      '#id' => 'feeds-ex-source-table',
    );

    $max_weight = 0;
    foreach ($this->config['sources'] as $machine_name => $source) {
      $form['sources'][$machine_name]['name'] = array(
        '#type' => 'textfield',
        '#title' => t('Name'),
        '#title_display' => 'invisible',
        '#default_value' => $source['name'],
        '#size' => 20,
      );
      $form['sources'][$machine_name]['machine_name'] = array(
        '#title' => t('Machine name'),
        '#title_display' => 'invisible',
        '#markup' => $machine_name,
      );
      $form['sources'][$machine_name]['value'] = array(
        '#type' => 'textfield',
        '#title' => t('Value'),
        '#title_display' => 'invisible',
        '#default_value' => $source['value'],
        '#size' => 50,
        '#maxlength' => 1024,
      );

      foreach ($this->configFormTableHeader() as $column => $name) {
        $form['sources'][$machine_name][$column] = $this->configFormTableColumn($form_state, $source, $column, $machine_name);
      }

      $form['sources'][$machine_name]['debug'] = array(
        '#type' => 'checkbox',
        '#title' => t('Debug'),
        '#title_display' => 'invisible',
        '#default_value' => $source['debug'],
      );
      $form['sources'][$machine_name]['remove'] = array(
        '#type' => 'checkbox',
        '#title' => t('Remove'),
        '#title_display' => 'invisible',
      );
      $form['sources'][$machine_name]['weight'] = array(
        '#type' => 'textfield',
        '#default_value' => $source['weight'],
        '#size' => 3,
        '#attributes' => array('class' => array('feeds-ex-source-weight')),
      );
      $max_weight = $source['weight'];
    }

    $form['add']['name'] = array(
      '#type' => 'textfield',
      '#title' => t('Add new source'),
      '#id' => 'edit-sources-add-name',
      '#description' => t('Name'),
      '#size' => 20,
    );
    $form['add']['machine_name'] = array(
      '#title' => t('Machine name'),
      '#title_display' => 'invisible',
      '#type' => 'machine_name',
      '#machine_name' => array(
        'exists' => 'feeds_ex_source_exists',
        'source' => array('add', 'name'),
        'standalone' => TRUE,
        'label' => '',
      ),
      '#field_prefix' => '<span dir="ltr">',
      '#field_suffix' => '</span>&lrm;',
      '#feeds_importer' => $this->id,
      '#required' => FALSE,
      '#maxlength' => 32,
      '#size' => 15,
      '#description' => t('A unique machine-readable name containing letters, numbers, and underscores.'),
    );
    $form['add']['value'] = array(
      '#type' => 'textfield',
      '#description' => t('Value'),
      '#title' => '&nbsp;',
      '#size' => 50,
      '#maxlength' => 1024,
    );
    foreach ($this->configFormTableHeader() as $column => $name) {
      $form['add'][$column] = $this->configFormTableColumn($form_state, array(), $column, '');
    }
    $form['add']['debug'] = array(
      '#type' => 'checkbox',
      '#title' => t('Debug'),
      '#title_display' => 'invisible',
    );
    $form['add']['weight'] = array(
      '#type' => 'textfield',
      '#default_value' => ++$max_weight,
      '#size' => 3,
      '#attributes' => array('class' => array('feeds-ex-source-weight')),
    );
    $form['display_errors'] = array(
      '#type' => 'checkbox',
      '#title' => t('Display errors'),
      '#description' => t('Display all error messages after parsing. Fatal errors will always be displayed.'),
      '#default_value' => $this->config['display_errors'],
    );
    $form['debug_mode'] = array(
      '#type' => 'checkbox',
      '#title' => t('Enable debug mode'),
      '#description' => t('Displays the configuration form on the feed source page to ease figuring out the expressions. Any values entered on that page will be saved here.'),
      '#default_value' => $this->config['debug_mode'],
    );

    $form = $this->getEncoder()->configForm($form, $form_state);

    $form['#attached']['drupal_add_tabledrag'][] = array(
      'feeds-ex-source-table',
      'order',
      'sibling',
      'feeds-ex-source-weight',
    );
    $form['#attached']['css'][] = drupal_get_path('module', 'feeds_ex') . '/feeds_ex.css';
    $form['#header'] = $this->getFormHeader();

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function configFormValidate(&$values) {
    // Throwing an exception during validation shows a nasty error to users.
    try {
      $this->loadLibrary();
    }
    catch (RuntimeException $e) {
      $this->getMessenger()->setMessage($e->getMessage(), 'error', FALSE);
      return;
    }

    // @todo We should do this in Feeds automatically.
    $values += $this->configDefaults();

    // Remove sources.
    foreach ($values['sources'] as $machine_name => $source) {
      if (!empty($source['remove'])) {
        unset($values['sources'][$machine_name]);
      }
    }

    // Validate context.
    if ($this->hasConfigurableContext()) {
      if ($message = $this->validateExpression($values['context']['value'])) {
        form_set_error('context', $message);
      }
    }

    // Validate expressions.
    foreach (array_keys($values['sources']) as $machine_name) {
      if ($message = $this->validateExpression($values['sources'][$machine_name]['value'])) {
        form_set_error('sources][' . $machine_name . '][value', $message);
      }
    }

    // Add new source.
    if (strlen($values['add']['machine_name']) && strlen($values['add']['name'])) {
      if ($message = $this->validateExpression($values['add']['value'])) {
        form_set_error('add][value', $message);
      }
      else {
        $values['sources'][$values['add']['machine_name']] = $values['add'];
      }
    }

    // Rebuild sources to keep the configuration values clean.
    $columns = $this->getFormHeader();
    unset($columns['remove'], $columns['machine_name']);
    $columns = array_keys($columns);

    foreach ($values['sources'] as $machine_name => $source) {
      $new_value = array();
      foreach ($columns as $column) {
        $new_value[$column] = $source[$column];
      }
      $values['sources'][$machine_name] = $new_value;
    }

    // Sort by weight.
    uasort($values['sources'], 'ctools_plugin_sort');

    // Let the encoder do its thing.
    $this->getEncoder()->configFormValidate($values);
  }

  /**
   * {@inheritdoc}
   */
  public function hasConfigForm() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function sourceDefaults() {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function sourceForm($source_config) {
    if (!$this->hasSourceConfig()) {
      return array();
    }
    $form_state = array();

    $form = $this->configForm($form_state);
    $form['add']['machine_name']['#machine_name']['source'] = array(
      'feeds',
      get_class($this),
      'add',
      'name',
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function sourceFormValidate(&$source_config) {
    $this->configFormValidate($source_config);
  }

  /**
   * {@inheritdoc}
   */
  public function sourceSave(FeedInterface $feed) {
    $config = $feed->getConfigFor($this);
    $feed->setConfigFor($this, array());

    if ($this->hasSourceConfig() && $config) {
      $this->setConfig($config);
      $this->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function hasSourceConfig() {
    return !empty($this->config['debug_mode']);
  }

  /**
   * Returns the configuration form table header.
   *
   * @return array
   *   The header array.
   */
  protected function getFormHeader() {
    $header = array(
      'name' => t('Name'),
      'machine_name' => t('Machine name'),
      'value' => t('Value'),
    );
    $header += $this->configFormTableHeader();
    $header += array(
      'debug' => t('Debug'),
      'remove' => t('Remove'),
      'weight' => t('Weight'),
    );

    return $header;
  }

  /**
   * Sets the messenger to be used to display messages.
   *
   * @param MessengerInterface $messenger
   *   The messenger.
   *
   * @return $this
   *   The parser object.
   */
  public function setMessenger(MessengerInterface $messenger) {
    $this->messenger = $messenger;
    return $this;
  }

  /**
   * Returns the messenger.
   *
   * @return MessengerInterface
   *   The messenger.
   */
  public function getMessenger() {
    if (!isset($this->messenger)) {
      $this->messenger = new Messenger();
    }
    return $this->messenger;
  }

  /**
   * Sets the encoder.
   *
   * @param \Drupal\feeds_ex\Encoder\EncoderInterface $encoder
   *   The encoder.
   *
   * @return $this
   *   The parser object.
   */
  public function setEncoder(EncoderInterface $encoder) {
    $this->encoder = $encoder;
    return $this;
  }

  /**
   * Returns the encoder.
   *
   * @return \Drupal\feeds_ex\Encoder\EncoderInterface
   *   The encoder object.
   */
  public function getEncoder() {
    if (!isset($this->encoder)) {
      $class = $this->encoderClass;
      $this->encoder = new $class($this->config['source_encoding']);
    }
    return $this->encoder;
  }

}
