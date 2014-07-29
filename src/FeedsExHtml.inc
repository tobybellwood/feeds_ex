<?php

/**
 * @file
 * Contains FeedsExHtml.
 */

/**
 * Parses HTML documents with XPath.
 */
class FeedsExHtml extends FeedsExXpathXml {

  /**
   * Whether this version of PHP has the correct saveHTML() method.
   *
   * @var bool
   */
  protected $useSaveHTML;

  /**
   * {@inheritdoc}
   */
  public function __construct($id) {
    parent::__construct($id);
    // DOMDocument::saveHTML() cannot take $node as an argument prior to 5.3.6.
    $this->useSaveHTML = version_compare(phpversion(), '5.3.6', '>=');
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareDocument(FeedsSource $source, FeedsFetcherResult $fetcher_result) {
    $document = new DOMDocument();
    $document->strictErrorChecking = FALSE;
    $document->resolveExternals = FALSE;
    // Libxml specific.
    $document->substituteEntities = FALSE;
    $document->recover = TRUE;

    $options = LIBXML_NOENT | LIBXML_NONET | defined('LIBXML_COMPACT') ? LIBXML_COMPACT : 0;
    $success = $document->loadHTML($fetcher_result->getRaw());

    if (!$success) {
      // Always display errors on fatal.
      $errors = $this->stopErrorHandling();
      $this->printErrors($errors);
      $this->logErrors($source, $errors);
      throw new RuntimeException(t('There was an error parsing the HTML document.'));
    }

    return $document;
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

}
