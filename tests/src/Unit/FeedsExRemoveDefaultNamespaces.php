<?php

namespace Drupal\Tests\feeds_ex\Unit;

/**
 * @FIXME
 * Unit tests are now written for the PHPUnit framework. You will need to refactor
 * this test in order for it to work properly.
 */
class FeedsExRemoveDefaultNamespaces extends \Drupal\Tests\UnitTestCase {
  public static function getInfo() {
    return array(
      'name' => 'Strip default namespaces',
      'description' => 'Tests stripping default namespaces from XML.',
      'group' => 'Feeds EX',
    );
  }

  public function setUp() {
    parent::setUp();
    require_once \Drupal::root() . '/' . drupal_get_path('module', 'feeds_ex') . '/src/Text/Utility.php';
    require_once \Drupal::root() . '/' . drupal_get_path('module', 'feeds_ex') . '/src/Xml/Utility.php';
  }

  /**
   * Strip some namespaces out of XML.
   */
  public function test() {
    $this->check('<feed xmlns="http://www.w3.org/2005/Atom">bleep blorp</feed>', '<feed>bleep blorp</feed>');
    $this->check('<подача xmlns="http://www.w3.org/2005/Atom">bleep blorp</подача>', '<подача>bleep blorp</подача>');
    $this->check('<по.дача xmlns="http://www.w3.org/2005/Atom">bleep blorp</по.дача>', '<по.дача>bleep blorp</по.дача>');
    $this->check('<element other attrs xmlns="http://www.w3.org/2005/Atom">bleep blorp</element>', '<element other attrs>bleep blorp</element>');
    $this->check('<cat xmlns="http://www.w3.org/2005/Atom" other attrs>bleep blorp</cat>', '<cat other attrs>bleep blorp</cat>');
    $this->check('<飼料 thing="stuff" xmlns="http://www.w3.org/2005/Atom">bleep blorp</飼料>', '<飼料 thing="stuff">bleep blorp</飼料>');
    $this->check('<飼-料 thing="stuff" xmlns="http://www.w3.org/2005/Atom">bleep blorp</飼-料>', '<飼-料 thing="stuff">bleep blorp</飼-料>');
    $this->check('<self xmlns="http://www.w3.org/2005/Atom" />', '<self />');
    $this->check('<self attr xmlns="http://www.w3.org/2005/Atom"/>', '<self attr/>');
    $this->check('<a xmlns="http://www.w3.org/2005/Atom"/>', '<a/>');
    $this->check('<a xmlns="http://www.w3.org/2005/Atom"></a>', '<a></a>');
    $this->check('<a href="http://google.com" xmlns="http://www.w3.org/2005/Atom"></a>', '<a href="http://google.com"></a>');

    // Test invalid XML element names.
    $this->check('<1name href="http://google.com" xmlns="http://www.w3.org/2005/Atom"></1name>', '<1name href="http://google.com" xmlns="http://www.w3.org/2005/Atom"></1name>');

    // Test other namespaces.
    $this->check('<name href="http://google.com" xmlns:h="http://www.w3.org/2005/Atom"></name>', '<name href="http://google.com" xmlns:h="http://www.w3.org/2005/Atom"></name>');

    // Test multiple default namespaces.
    $this->check('<name xmlns="http://www.w3.org/2005/Atom"></name><name xmlns="http://www.w3.org/2005/Atom"></name>', '<name></name><name></name>');
  }

  /**
   * Checks that the input and output are equal.
   */
  protected function check($in, $out) {
    $this->assertEqual(FeedsExXmlUtility::removeDefaultNamespaces($in), $out);
  }

}