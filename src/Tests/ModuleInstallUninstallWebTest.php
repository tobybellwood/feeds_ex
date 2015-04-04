<?php

/**
 * @file
 * Contains \Drupal\feeds_ex\Tests\ModuleInstallUninstallWebTest.
 */

namespace Drupal\feeds_ex\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests module installation and uninstallation.
 *
 * @group feeds_ex
 */
class ModuleInstallUninstallWebTest extends WebTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = array('feeds_ex');

  /**
   * Test installation and uninstallation.
   */
  protected function testInstallationAndUninstallation() {
    /** @var \Drupal\Core\Extension\ModuleInstallerInterface $module_installer */
    $module_installer = \Drupal::service('module_installer');
    $module_handler = \Drupal::moduleHandler();
    $this->assertTrue($module_handler->moduleExists('feeds_ex'));

    $module_installer->uninstall(array('feeds_ex'));
    $this->assertFalse($module_handler->moduleExists('feeds_ex'));
  }
}
