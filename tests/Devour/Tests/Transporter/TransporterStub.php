<?php

/**
 * @file
 * Contains \Devour\Tests\Transporter\TransporterStub.
 */

namespace Devour\Tests\Transporter;

use Devour\Payload\FilePayload;
use Devour\Source\SourceInterface;
use Devour\Transporter\TransporterInterface;

/**
 * A stub transporter implementation.
 */
class TransporterStub implements TransporterInterface {

  /**
   * {@inheritdoc}
   */
  public function transport(SourceInterface $source) {
    return new FilePayload('');
  }

}
