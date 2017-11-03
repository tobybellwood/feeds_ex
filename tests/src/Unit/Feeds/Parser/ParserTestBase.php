<?php

namespace Drupal\Tests\feeds_ex\Unit\Feeds\Parser;

use Drupal\feeds\State;
use Drupal\Tests\feeds_ex\Unit\UnitTestBase;

/**
 * Base class for parser unit tests.
 */
abstract class ParserTestBase extends UnitTestBase {

  /**
   * @var \Drupal\feeds_ex\Feeds\Parser\ParserBase
   */
  protected $parser;

  /**
   * @var \Drupal\feeds\FeedTypeInterface
   */
  protected $feedType;

  /**
   * @var \Drupal\feeds\FeedInterface
   */
  protected $feed;

  /**
   * @var \Drupal\feeds\State
   */
  protected $state;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->feedType = $this->getMock('Drupal\feeds\FeedTypeInterface');

    $this->state = new State();

    $this->feed = $this->getMock('Drupal\feeds\FeedInterface');
    $this->feed->expects($this->any())
      ->method('getType')
      ->will($this->returnValue($this->feedType));
  }

}
