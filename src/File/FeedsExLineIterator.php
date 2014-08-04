<?php

/**
 * @file
 * Contains FeedsExLineIterator.
 */

/**
 * Text lines from file iterator.
 */
class FeedsExLineIterator extends SplFileObject {

  /**
   * The position to start in the file.
   *
   * @var int
   */
  protected $startLine = 0;

  /**
   * The number of lines to read.
   *
   * @var init
   */
  protected $lineLimit;

  /**
   * The number of lines that have been read.
   *
   * @var init
   */
  protected $linesRead = 0;

  /**
   * Implements Iterator::rewind().
   */
  public function rewind() {
    $this->setFlags(self::SKIP_EMPTY | self::READ_AHEAD);

    parent::rewind();
    if ($this->startLine) {
      $this->seek($this->startLine);
    }
    $this->linesRead = 0;
  }

  /**
   * Implements Iterator::next().
   */
  public function next() {
    $this->linesRead++;
    parent::next();
  }

  /**
   * Implements Iterator::valid().
   */
  public function valid() {
    return (!$this->lineLimit || $this->linesRead < $this->lineLimit) && parent::valid();
  }

  /**
   * Sets the number of lines to read.
   *
   * @param int $limit
   *   The number of lines to read.
   */
  public function setLineLimit($limit) {
    $this->lineLimit = (int) $limit;
  }

  /**
   * Returns the line position in the file.
   *
   * @return int
   *   The line position in the file.
   */
  public function getLinePosition() {
    return $this->linesRead + $this->startLine;
  }

  /**
   * Sets the starting line.
   *
   * @param int $line_num
   *   The line to start parsing on.
   */
  public function setStartLine($line_num) {
    $this->startLine = (int) $line_num;
  }

}
