<?php

namespace Drupal\ocha_assessments\EventSubscriber;

use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscribing an event.
 */
class CorsSubscriber implements EventSubscriberInterface {

  /**
   * Executes actions on the request event.
   */
  public function onKernelRequest(GetResponseEvent $event) {
    if (!$event->isMasterRequest()) {
      return;
    }

    $request = $event->getRequest();
    $method  = $request->getRealMethod();
    if (strtoupper($method) === 'OPTIONS') {
      $response = new Response();
      $event->setResponse($response);
    }
  }

  /**
   * Executes actions on the response event.
   */
  public function onKernelResponse(FilterResponseEvent $event) {
    $response = $event->getResponse();
    $host = '*';

    $referer = $event->getRequest()->server->get('HTTP_REFERER');
    if ($referer) {
      $parts = parse_url($referer);
      if (is_array($parts) && array_key_exists('scheme', $parts) && array_key_exists('host', $parts)) {
        $host = $parts['scheme'] . '://' . $parts['host'];
        if (isset($parts['port'])) {
          $host .= ':' . $parts['port'];
          $response->headers->set('Vary', 'Origin');
        }
      }
    }

    $response->headers->set('Access-Control-Allow-Origin', $host);
    $response->headers->set('Access-Control-Allow-Headers', '');
    $response->headers->set('Access-Control-Expose-Headers', '');
    $response->headers->set('Access-Control-Allow-Methods', 'GET');
    $response->headers->set('Access-Control-Max-Age', '120');
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      KernelEvents::REQUEST  => ['onKernelRequest', -10],
      KernelEvents::RESPONSE => ['onKernelResponse', -10],
    ];
  }

}
