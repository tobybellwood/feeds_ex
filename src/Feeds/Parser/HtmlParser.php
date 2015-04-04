<?php

/**
 * @file
 * Contains \Drupal\feeds_ex\Feeds\Parser\HtmlParser.
 */

namespace Drupal\feeds_ex\Feeds\Parser;

/**
 * Defines a HTML parser using XPath.
 *
 * @FeedsParser(
 *   id = "html",
 *   title = @Translation("HTML"),
 *   description = @Translation("Parse HTML with XPath.")
 * )
 */
class HtmlParser extends XmlParser {

  /**
   * Whether this version of PHP has the correct saveHTML() method.
   *
   * @var bool
   */
  protected $useSaveHTML;

  /**
   * {@inheritdoc}
   */
  protected $encoderClass = 'HtmlEncoder';

  /**
   * {@inheritdoc}
   */
  public function __construct($id) {
    parent::__construct($id);
    // DOMDocument::saveHTML() cannot take $node as an argument prior to 5.3.6.
    $this->useSaveHTML = version_compare(PHP_VERSION, '5.3.6', '>=');
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
  protected function getRaw(DOMNode $node) {
    if ($this->useSaveHTML) {
      return $node->ownerDocument->saveHTML($node);
    }
    return $node->ownerDocument->saveXML($node, LIBXML_NOEMPTYTAG);
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
