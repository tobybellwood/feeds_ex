<?php

/**
 * @file
 * Contains \Drupal\feeds_ex\Tests\TestFeedsSource.
 */

namespace Drupal\feeds_ex\Tests;

use Drupal\Core\Logger\RfcLogLevel;

/**
 * A FeedsSource class used during testing.
 */
class TestFeedsSource extends FeedsSource {

  /**
   * Log messages stored for later use.
   */
  protected $logMessages = array();

  /**
   * {@inheritdoc}
   */
  public function log($type, $message, $variables = array(), $severity = RfcLogLevel::NOTICE) {
    $this->logMessages[] = array(
      'type' => $type,
      'message' => $message,
      'variables' => $variables,
      'severity' => $severity,
    );
  }

  /**
   * Returns the list of the log messages.
   *
   * @return array
   *   A list of log messages.
   */
  public function getLogMessages() {
    return $this->logMessages;
  }

}
