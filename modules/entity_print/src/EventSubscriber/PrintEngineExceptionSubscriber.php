<?php

namespace Drupal\entity_print\EventSubscriber;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Drupal\entity_print\PrintEngineException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class PrintEngineExceptionSubscriber implements EventSubscriberInterface {

  /**
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  public function __construct(RouteMatchInterface $routeMatch, EntityTypeManagerInterface $entityTypeManager) {
    $this->routeMatch = $routeMatch;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Handles print exceptions.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent $event
   *   The exception event.
   */
  public function handleException(GetResponseForExceptionEvent $event) {
    $exception = $event->getException();
    if ($exception instanceof PrintEngineException) {
      // Build a safe markup string using Xss::filter() so that the instructions
      // for installing dependencies can contain quotes.
      drupal_set_message(new FormattableMarkup('Error generating document: ' . Xss::filter($exception->getMessage()), []), 'error');

      $entity_type = $this->routeMatch->getParameter('entity_type');
      $entity_id = $this->routeMatch->getParameter('entity_id');
      if ($entity = $this->entityTypeManager->getStorage($entity_type)->load($entity_id)) {
        $event->setResponse(new RedirectResponse($entity->toUrl()->toString()));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      KernelEvents::EXCEPTION => 'handleException',
    ];
  }

}
