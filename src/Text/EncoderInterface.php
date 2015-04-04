<?php

/**
 * @file
 * Contains \Drupal\feeds_ex\Text\EncoderInterface.
 */

namespace Drupal\feeds_ex\Text;

/**
 * Coverts text encodings.
 */
interface EncoderInterface {

  /**
   * Constructs a FeedsExEncoderInterface object.
   *
   * @param array $encoding_list
   *   The list of encodings to search through.
   */
  public function __construct(array $encoding_list);

  /**
   * Converts a string to UTF-8.
   *
   * @param string $data
   *   The string to convert.
   *
   * @return string
   *   The encoded string, or the original string if encoding failed.
   */
  public function convertEncoding($data);

  /**
   * Returns the configuration form to select encodings.
   *
   * @param array $form
   *   The current form.
   * @param array &$form_state
   *   The form state.
   *
   * @return array
   *   The modified form array.
   */
  public function configForm(array $form, array &$form_state);

  /**
   * Validates the encoding configuration form.
   *
   * @param array &$values
   *   The form values.
   */
  public function configFormValidate(array &$values);

}
