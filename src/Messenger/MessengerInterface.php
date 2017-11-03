<?php

namespace Drupal\feeds_ex\Messenger;

/**
 * Displays messages to the user.
 */
interface MessengerInterface {

  /**
   * Sets a message to display to the user.
   *
   * @param string $message
   *   The message.
   * @param string $type
   *   (optional) The type of message. Defaults to 'status'.
   * @param bool $repeat
   *   (optional) Whether to allow the message to repeat. Defaults to true.
   *
   * @see drupal_set_message()
   */
  public function setMessage($message = NULL, $type = 'status', $repeat = TRUE);

}
