<?php

/**
 * @file
 * Contains \Drupal\feeds_ex\QueryPathHtml.
 */

namespace Drupal\feeds_ex;

/**
 * Parses HTML documents with QueryPath.
 *
 * @todo Make convertEncoding() into a helper function so that they aren't \
 *   copied in 2 places.
 */
class QueryPathHtml extends QueryPathXml {

  /**
   * {@inheritdoc}
   */
  protected $encoderClass = 'HtmlEncoder';

  /**
   * {@inheritdoc}
   */
  protected function setUp(FeedsSource $source, FeedsFetcherResult $fetcher_result) {
    // Change some parser settings.
    $this->queryPathOptions['use_parser'] = 'html';
  }

  /**
   * {@inheritdoc}
   */
  protected function getRawValue(QueryPath $node) {
    return $node->html();
  }

  /**
   * {@inheritdoc}
   */
  protected function convertEncoding($data, $encoding = 'UTF-8') {
    return XmlUtility::convertHtmlEncoding($data, $this->config['source_encoding']);
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareDocument(FeedsSource $source, FeedsFetcherResult $fetcher_result) {
    $raw = $this->prepareRaw($fetcher_result);
    if ($this->config['use_tidy'] && extension_loaded('tidy')) {
      $raw = tidy_repair_string($raw, $this->getTidyConfig(), 'utf8');
    }
    return XmlUtility::createHtmlDocument($raw);
  }

  /**
   * {@inheritdoc}
   */
  protected function getTidyConfig() {
    return array(
      'merge-divs' => FALSE,
      'merge-spans' => FALSE,
      'join-styles' => FALSE,
      'drop-empty-paras' => FALSE,
      'wrap' => 0,
      'tidy-mark' => FALSE,
      'escape-cdata' => TRUE,
      'word-2000' => TRUE,
    );
  }

}
