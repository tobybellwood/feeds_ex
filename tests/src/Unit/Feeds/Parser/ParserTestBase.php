<?php

namespace Drupal\Tests\feeds_ex\Unit\Feeds\Parser;

use Drupal\feeds\State;
use Drupal\Tests\feeds_ex\Unit\UnitTestBase;

/**
 * Base class for parser unit tests.
 */
abstract class ParserTestBase extends UnitTestBase {

  /**
   * The Feeds parser plugin.
   *
   * @var \Drupal\feeds_ex\Feeds\Parser\ParserBase
   */
  protected $parser;

  /**
   * The feed type entity.
   *
   * @var \Drupal\feeds\FeedTypeInterface
   */
  protected $feedType;

  /**
   * The feed entity.
   *
   * @var \Drupal\feeds\FeedInterface
   */
  protected $feed;

  /**
   * The state object.
   *
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
