<?php

/**
 * @file
 * Contains FeedsExBase.
 */

/**
 * The Feeds extensible parser.
 */
abstract class FeedsExBase extends FeedsParser {

  /**
   * Allows subclasses to prepare for parsing.
   *
   * @param FeedsSource $source
   *   The feed source.
   * @param FeedsFetcherResult $fetcher_result
   *   The result of the fetching stage.
   */
  protected abstract function setUp(FeedsSource $source, FeedsFetcherResult $fetcher_result);

  /**
   * Allows subclasses to cleanup.
   *
   * @param FeedsSource $source
   *   The feed source.
   * @param FeedsParserResult $parser_result
   *   The result of parsing.
   */
  protected abstract function cleanUp(FeedsSource $source, FeedsParserResult $parser_result);

  /**
   * Executes the context expression.
   *
   * @return array|Traversable
   *   A list of un-parsed feed items.
   */
  protected abstract function executeContext();

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
   * @param string $expressions
   *   The expression to validate.
   *
   * @return string|null
   *   Return the error string, or null if validation was passed.
   */
  abstract protected function validateExpression(&$expression);

  /**
   * {@inheritdoc}
   */
  public function parse(FeedsSource $source, FeedsFetcherResult $fetcher_result) {
    $config = $this->getConfig();
    $result = new FeedsParserResult();

    // Set link.
    $fetcher_config = $source->getConfigFor($source->importer->fetcher);
    $result->link = $fetcher_config['source'];

    $expressions = $this->prepareExpressions();
    $variable_map = $this->prepareVariables($expressions);

    $this->setUp($source, $fetcher_result);

    foreach ($this->executeContext() as $row) {
      if ($item = $this->executeSources($row, $expressions, $variable_map)) {
        $result->items[] = $item;
      }
    }

    $this->cleanUp($source, $result);
    return $result;
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
    $output .= theme('item_list', array('items' => $data));
    drupal_set_message($output);
  }

  /**
   * {@inheritdoc}
   */
  public function getMappingSources() {
    $config = $this->getConfig();
    return parent::getMappingSources() + $config['sources'];
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
    );
  }

  /**
   * {@inheritdoc}
   */
  public function configForm(&$form_state) {
    $form = array(
      '#tree' => TRUE,
      '#theme' => 'feeds_ex_configuration_table',
      '#prefix' => '<div id="feeds-ex-configuration-wrapper">',
      '#suffix' => '</div>',
    );
    $config = $this->getConfig();

    $form['context']['name'] = array(
      '#type' => 'markup',
      '#markup' => t('Context'),
    );
    $form['context']['value'] = array(
      '#type' => 'textfield',
      '#title' => t('Value'),
      '#title_display' => 'invisible',
      '#default_value' => $config['context']['value'],
      '#size' => 50,
    );

    $form['sources'] = array(
      '#id' => 'feeds-ex-source-table',
    );

    $max_weight = 0;
    foreach ($config['sources'] as $machine_name => $source) {
      $form['sources'][$machine_name]['machine_name'] = array(
        '#title' => t('Machine name'),
        '#title_display' => 'invisible',
        '#markup' => $machine_name,
      );
      $form['sources'][$machine_name]['name'] = array(
        '#type' => 'textfield',
        '#title' => t('Name'),
        '#title_display' => 'invisible',
        '#default_value' => $source['name'],
        '#size' => 20,
      );
      $form['sources'][$machine_name]['value'] = array(
        '#type' => 'textfield',
        '#title' => t('Value'),
        '#title_display' => 'invisible',
        '#default_value' => $source['value'],
        '#size' => 50,
      );
      $form['sources'][$machine_name]['raw'] = array(
        '#type' => 'checkbox',
        '#title' => t('Raw value'),
        '#title_display' => 'invisible',
        '#default_value' => $source['raw'],
      );
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
    $form['add']['name'] = array(
      '#type' => 'textfield',
      '#title' => t('Add new source'),
      '#id' => 'edit-sources-add-name',
      '#description' => t('Name'),
      '#size' => 20,
    );
    $form['add']['value'] = array(
      '#type' => 'textfield',
      '#description' => t('Value'),
      '#title' => '&nbsp;',
      '#size' => 50,
    );
    $form['add']['raw'] = array(
      '#type' => 'checkbox',
      '#title' => t('Raw'),
      '#title_display' => 'invisible',
    );
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

    $form['#attached']['drupal_add_tabledrag'][] = array('feeds-ex-source-table', 'order', 'sibling', 'feeds-ex-source-weight');
    $form['#attached']['css'][] = drupal_get_path('module', 'feeds_ex') . '/feeds_ex.css';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function configFormValidate(&$values) {
    // Add new source.
    if (strlen($values['add']['machine_name']) && strlen($values['add']['name'])) {
      $values['sources'][$values['add']['machine_name']] = $values['add'];
    }

    // Remove sources.
    foreach ($values['sources'] as $machine_name => $source) {
      if (!empty($source['remove'])) {
        unset($values['sources'][$machine_name]);
      }
    }

    // Rebuild sources to keep the configuration values clean.
    foreach ($values['sources'] as $machine_name => $source) {
      $values['sources'][$machine_name] = array(
        'name' => $source['name'],
        'value' => $source['value'],
        'raw' => $source['raw'],
        'debug' => $source['debug'],
        'weight' => $source['weight'],
      );
    }

    // Sort by weight.
    uasort($values['sources'], 'ctools_plugin_sort');

    // Validate context.
    if ($message = $this->validateExpression($values['context']['value'])) {
      form_set_error('context', $message);
    }

    // Validate expressions.
    foreach (array_keys($values['sources']) as $machine_name) {
      if ($message = $this->validateExpression($values['sources'][$machine_name]['value'])) {
        form_set_error('sources][' . $machine_name . '][value', $message);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function hasConfigForm() {
    return TRUE;
  }

}
