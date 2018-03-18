<?php

namespace Drupal\Tests\feeds_ex\Functional;

use Drupal\Tests\feeds\Functional\FeedsBrowserTestBase;

/**
 * Base class for Feeds extensible parser functional tests.
 */
abstract class FeedsExBrowserTestBase extends FeedsBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['feeds_ex'];

}
