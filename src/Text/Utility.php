<?php

/**
 * @file
 * Contains FeedsExTextEncoder.
 */

/**
 * Generic text encoder.
 */
class FeedsExTextEncoder implements FeedsExEncoderInterface {

  /**
   * Whether the current system handles mb_* functions.
   *
   * @var bool
   */
  protected $isMultibyte = FALSE;

  /**
   * The set of encodings compatible with UTF-8.
   *
   * @param array
   */
  protected static $utf8Compatible = array('utf-8', 'us-ascii', 'ascii');

  /**
   * The list of encodings to search for.
   *
   * @var array
   */
  protected $encodingList;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $encoding_list) {
    $this->encodingList = $encoding_list;
    $this->isMultibyte = $GLOBALS['multibyte'] == UNICODE_MULTIBYTE;
  }

  /**
   * {@inheritdoc}
   */
  public function setMultibyte($is_multibyte) {
    $this->isMultibyte = (bool) $is_multibyte;
  }

  /**
   * Detects the encoding of a string.
   *
   * @param string $data
   *   The string to guess the encoding for.
   *
   * @return string|bool
   *   Returns the encoding, or false if one could not be detected.
   *
   * @todo Add other methods of encoding detection.
   */
  protected function detectEncoding($data) {
    if ($this->isMultibyte) {
      return mb_detect_encoding($data, $this->encodingList, TRUE);
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function convertEncoding($data) {
    if (!$detected = $this->detectEncoding($data)) {
      return $data;
    }
    return $this->doConvert($data, $detected);
  }

  /**
   * Performs the actual encoding conversion.
   *
   * @param string $data
   *   The data to convert.
   * @param string $source_encoding
   *   The detected encoding.
   *
   * @return string
   *   The encoded string.
   */
  protected function doConvert($data, $source_encoding) {
    if (in_array(strtolower($source_encoding), self::$utf8Compatible)) {
      return $data;
    }
    $converted = drupal_convert_to_utf8($data, $source_encoding);
    if ($converted === FALSE) {
      return $data;
    }
    return $converted;
  }

}
