<?php

/**
 * @file
 * Contains FeedsExJsonUtility.
 */

/**
 * Various helpers for handling JSON.
 */
class FeedsExJsonUtility {

  /**
   * Translates an error message.
   *
   * @param int $error
   *   The JSON error.
   *
   * @return string
   *   The JSON parsing error message.
   */
  public static function translateError($error) {
    switch ($error) {
      case JSON_ERROR_NONE:
        return;

      case JSON_ERROR_DEPTH:
        return 'Maximum stack depth exceeded';

      case JSON_ERROR_STATE_MISMATCH:
        return 'Underflow or the modes mismatch';

      case JSON_ERROR_CTRL_CHAR:
        return 'Unexpected control character found';

      case JSON_ERROR_SYNTAX:
        return 'Syntax error, malformed JSON';

      case JSON_ERROR_UTF8:
        return 'Malformed UTF-8 characters, possibly incorrectly encoded';

      default:
        return 'Unknown error';
    }
  }

  /**
   * Decodes a JSON string into an array.
   *
   * @param string $json
   *   A JSON string.
   *
   * @return array
   *   A PHP array.
   *
   * @throws RuntimeException
   *   Thrown if the encoded JSON does not result in an array.
   */
  public static function decodeJsonArray($json) {
    $parsed = drupal_json_decode($json);

    if (!is_array($parsed)) {
      throw new RuntimeException(t('The JSON is invalid.'));
    }

    return $parsed;
  }

}
