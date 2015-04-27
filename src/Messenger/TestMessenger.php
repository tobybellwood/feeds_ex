<?php

/**
 * @file
 * Contains \Drupal\feeds_ex\Messenger\TestMessenger.
 */

namespace Drupal\feeds_ex\Messenger;

/**
 * Stores messages without calling drupal_set_mesage().
 */
class TestMessenger implements MessengerInterface {

  /**
   * The messages that have been set.
   *
   * @var array
   */
  protected $messages = array();

  /**
   * {@inheritdoc}
   */
  public function setMessage($message = NULL, $type = 'status', $repeat = TRUE) {
    $this->messages[] = [
      'message' => $message,
      'type' => $type,
      'repeat' => $repeat,
    ];
  }

  /**
   * Returns the messages.
   *
   * This is used to inspect messages that have been set.
   *
   * @return array
   *   A list of message arrays.
   */
  public function getMessages() {
    return $this->messages;
  }

}
