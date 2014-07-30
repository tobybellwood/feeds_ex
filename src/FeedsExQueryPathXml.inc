<?php

/**
 * @file
 * Contains FeedsExXml.
 */

/**
 * Parses XML documents with QueryPath.
 */
class FeedsExQueryPathXml extends FeedsExXml {

  /**
   * The QueryPath object used for parsing.
   *
   * @var QueryPath
   */
  protected $queryPath;

  /**
   * Options passed to QueryPath.
   *
   * @var array
   */
  protected $queryPathOptions = array(
    'ignore_parser_warnings' => TRUE,
    'use_parser' => 'xml',
    'strip_low_ascii' => FALSE,
    'replace_entities' => FALSE,
    'omit_xml_declaration' => TRUE,
  );

  /**
   * {@inheritdoc}
   */
  protected function setUp(FeedsSource $source, FeedsFetcherResult $fetcher_result) {
    $document = $this->prepareDocument($source, $fetcher_result);

    $this->queryPath = new QueryPath($document, NULL, $this->queryPathOptions);
  }

  /**
   * {@inheritdoc}
   */
  protected function cleanUp(FeedsSource $source, FeedsParserResult $result) {
    // Try to free up some memory. There shouldn't be any other references to
    // $this->queryPath.
    unset($this->queryPath);
  }

  /**
   * {@inheritdoc}
   */
  protected function executeContext() {
    return $this->queryPath->find($this->config['context']['value']);
  }

  /**
   * {@inheritdoc}
   */
  protected function executeSourceExpression($machine_name, $expression, $row) {
    $result = new QueryPath($row, $expression, $this->queryPathOptions);

    if ($result->size() == 0) {
      return;
    }

    if (strlen($this->config['sources'][$machine_name]['attribute'])) {
      return $result->attr($this->config['sources'][$machine_name]['attribute']);
    }

    $return = array();
    if (!empty($this->config['sources'][$machine_name]['raw'])) {
      foreach ($result as $node) {
        $return[] = $node->xml();
      }
    }
    else {
      foreach ($result as $node) {
        $return[] = $node->text();
      }
    }

    // Return a single value if there's only one value.
    return count($return) === 1 ? $return[0] : $return;
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
  protected function configFormTableHeader() {
    return array(
      'attribute' => t('Attribute'),
    ) + parent::configFormTableHeader();
  }

  /**
   * {@inheritdoc}
   */
  protected function configFormTableColumn(array &$form_state, $column_name, array $source) {
    switch ($column_name) {
      case 'attribute':
        return array(
          '#type' => 'textfield',
          '#title' => t('Attribute name'),
          '#title_display' => 'invisible',
          '#default_value' => !empty($source['attribute']) ? $source['attribute'] : '',
          '#size' => 10,
        );

      default:
        return parent::configFormTableColumn($form_state, $column_name, $source);
    }
  }

}
