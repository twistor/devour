<?php

/**
 * @file
 * Contains \Devour\Transporter\Guzzle.
 */

namespace Devour\Transporter;

use Devour\Source\SourceInterface;
use Guzzle\Http\Client;

/**
 * A transport that returns a stream via HTTP.
 */
class Guzzle extends Client implements TransporterInterface {

  protected $streamToFile = TRUE;

  /**
   * {@inheritdoc}
   */
  public function transport(SourceInterface $source) {
    $request = $this->get($source->getSource());

    // Guzzle use's php://temp as a temporary file. That is awesome, but for our
    // multiprocessing, we need a real file path.
    // @todo Make this configurable.
    if ($this->streamToFile) {
      $request->setResponseBody(tempnam(sys_get_temp_dir(), 'devour_'));
    }

    return $request->send()->getBody();
  }

  /**
   * {@inheritdoc}
   */
  public static function fromConfiguration(array $configuration) {
    return new static(NULL, $configuration);
  }

}
