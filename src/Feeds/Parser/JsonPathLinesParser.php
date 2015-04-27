<?php

/**
 * @file
 * Contains \Drupal\feeds_ex\Feeds\Parser\JsonPathLinesParser.
 */

namespace Drupal\feeds_ex\Feeds\Parser;

use Drupal\feeds\Exception\EmptyFeedException;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\Result\FetcherResultInterface;
use Drupal\feeds\Result\ParserResultInterface;
use Drupal\feeds\StateInterface;

/**
 * Defines a JSON Lines parser using JSONPath.
 *
 * @FeedsParser(
 *   id = "jsonpathlines",
 *   title = @Translation("JSON Lines JSONPath"),
 *   description = @Translation("Parse JSON Lines with JSONPath.")
 * )
 */
class JsonPathLinesParser extends JsonPathParser {

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
  protected function setUp(FeedInterface $feed, FetcherResultInterface $fetcher_result, StateInterface $state) {
    $this->iterator = new LineIterator($fetcher_result->getFilePath());

    if (!$this->iterator->getSize()) {
      throw new EmptyFeedException();
    }

    $this->iterator->setLineLimit($feed->importer->getLimit());

    if (!$state->total) {
      $state->total = $this->iterator->getSize();
    }
    $this->iterator->setStartPosition((int) $state->pointer);
  }

  /**
   * {@inheritdoc}
   */
  protected function parseItems(FeedInterface $feed, FetcherResultInterface $fetcher_result, ParserResultInterface $result, StateInterface $state) {
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
  protected function cleanUp(FeedInterface $feed, ParserResultInterface $result, StateInterface $state) {
    $state->pointer = $this->iterator->ftell();
    unset($this->iterator);
    parent::cleanUp($feed, $result, $state);
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

}
