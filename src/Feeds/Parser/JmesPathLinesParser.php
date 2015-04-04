<?php

/**
 * @file
 * Contains \Drupal\feeds_ex\Feeds\Parser\JmesPathLinesParser.
 */

namespace Drupal\feeds_ex\Feeds\Parser;

/**
 * Defines a JSON Lines parser using JMESPath.
 *
 * @FeedsParser(
 *   id = "jmespathlines",
 *   title = @Translation("JSON Lines JMESPath"),
 *   description = @Translation("Parse JSON Lines with JMESPath.")
 * )
 */
class JmesPathLinesParser extends JmesPathParser {

  /**
   * The file iterator.
   *
   * @var LineIterator
   */
  protected $iterator;

  /**
   * {@inheritdoc}
   */
  protected function hasConfigurableContext() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp(FeedsSource $source, FeedsFetcherResult $fetcher_result) {
    parent::setUp($source, $fetcher_result);
    $this->iterator = new LineIterator($fetcher_result->getFilePath());

    if (!$this->iterator->getSize()) {
      throw new EmptyException();
    }

    $this->iterator->setLineLimit($source->importer->getLimit());

    $state = $source->state(FEEDS_PARSE);
    if (!$state->total) {
      $state->total = $this->iterator->getSize();
    }

    $this->iterator->setStartPosition((int) $state->pointer);
  }

  /**
   * {@inheritdoc}
   */
  protected function parseItems(FeedsSource $source, FeedsFetcherResult $fetcher_result, FeedsParserResult $result) {
    $expressions = $this->prepareExpressions();
    $variable_map = $this->prepareVariables($expressions);

    foreach ($this->iterator as $row) {
      $row = $this->getEncoder()->convertEncoding($row);
      try {
        $row = JsonUtility::decodeJsonArray($row);
      }
      catch (RuntimeException $e) {
        // An array wasn't returned. Skip this item.
        continue;
      }

      if ($item = $this->executeSources($row, $expressions, $variable_map)) {
        $result->items[] = $item;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function cleanUp(FeedsSource $source, FeedsParserResult $result) {
    $source->state(FEEDS_PARSE)->pointer = $this->iterator->ftell();
    unset($this->iterator);
    parent::cleanUp($source, $result);
  }

  /**
   * {@inheritdoc}
   */
  protected function executeSourceExpression($machine_name, $expression, $row) {
    // Row is a JSON string.
    $result = $this->jmesPath->search($expression, $row);

    if (is_scalar($result)) {
      return $result;
    }

    // Return a single value if there's only one value.
    return count($result) === 1 ? reset($result) : $result;
  }

}
