<?php

namespace Drupal\feeds_ex\Messenger;

/**
 * Uses drupal_set_message() to show messages.
 */
class Messenger implements MessengerInterface {

  /**
   * {@inheritdoc}
   */
  public function setMessage($message = NULL, $type = 'status', $repeat = TRUE) {
    drupal_set_message($message, $type, $repeat);
  }

}
