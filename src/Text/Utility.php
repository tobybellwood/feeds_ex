<?php

/**
 * @file
 * Contains FeedsExTextUtility.
 */

/**
 * Generic text utilities.
 */
class FeedsExTextUtility {

  /**
   * Whether the current system handles mb_* functions.
   *
   * @var bool
   */
  protected static $isMultibyte = FALSE;

  /**
   * The set of encodings compatible with UTF-8.
   *
   * @param array
   */
  public static $utf8Compatible = array('utf-8', 'us-ascii', 'ascii');

  /**
   * Sets the multibyte handling.
   *
   * @param bool $is_multibyte
   *   Whether this parser should assume multibyte handling exists.
   */
  public static function setMultibyte($is_multibyte) {
    self::$isMultibyte = (bool) $is_multibyte;
  }

  /**
   * Detects the encoding of a string.
   *
   * @param string $data
   *   The string to guess the encoding for.
   * @param array $encoding_list
   *   The list of encodings to search for.
   *
   * @return string|bool
   *   Returns the encoding, or false if one could not be detected.
   *
   * @todo Add other methods of encoding detection.
   */
  public static function detectEncoding($data, array $encoding_list) {
    if (self::$isMultibyte) {
      return mb_detect_encoding($data, $encoding_list, TRUE);
    }
    return FALSE;
  }

  /**
   * Converts a string to UTF-8.
   *
   * Requires the iconv, GNU recode or mbstring PHP extension.
   *
   * @param string $data
   *   The string to convert.
   * @param string $from_encoding
   *   The encoding to convert from.
   *
   * @return string
   *   The encoded string, or the original string if encoding failed.
   *
   * @see drupal_convert_to_utf8()
   */
  public static function convertEncoding($data, $from_encoding) {
    if (in_array(strtolower($from_encoding), self::$utf8Compatible)) {
      return $data;
    }
    $converted = drupal_convert_to_utf8($data, $from_encoding);
    if ($converted === FALSE) {
      return $data;
    }
    return $converted;
  }

}
