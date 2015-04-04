<?php

/**
 * @file
 * Contains \Drupal\feeds_ex\FeedsExMessenger.
 */

namespace Drupal\feeds_ex;

/**
 * Uses drupal_set_message() to show messages.
 */
class FeedsExMessenger implements FeedsExMessengerInterface {

  /**
   * {@inheritdoc}
   */
  public function setMessage($message = NULL, $type = 'status', $repeat = TRUE) {
    drupal_set_message($message, $type, $repeat);
  }

}
