<?php

namespace Drupal\entity_print_views\Controller;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\entity_print\PrintBuilderInterface;
use Drupal\entity_print\PrintEngineException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\entity_print\Plugin\EntityPrintPluginManagerInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Controller class for printing Views.
 */
class ViewPrintController extends ControllerBase {

  /**
   * The plugin manager for our Print engines.
   *
   * @var \Drupal\entity_print\Plugin\EntityPrintPluginManagerInterface
   */
  protected $pluginManager;

  /**
   * The Print builder.
   *
   * @var \Drupal\entity_print\PrintBuilderInterface
   */
  protected $printBuilder;

  /**
   * The Entity Type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $currentRequest;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityPrintPluginManagerInterface $plugin_manager, PrintBuilderInterface $print_builder, EntityTypeManagerInterface $entity_type_manager, Request $current_request, AccountInterface $current_user) {
    $this->pluginManager = $plugin_manager;
    $this->printBuilder = $print_builder;
    $this->entityTypeManager = $entity_type_manager;
    $this->currentRequest = $current_request;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.entity_print.print_engine'),
      $container->get('entity_print.print_builder'),
      $container->get('entity_type.manager'),
      $container->get('request_stack')->getCurrentRequest(),
      $container->get('current_user')
    );
  }

  /**
   * Print an entity to the selected format.
   *
   * @param string $export_type
   *   The export type.
   * @param string $view_name
   *   The view name
   * @param string $display_id
   *   The view display to render.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response object on error otherwise the Print is sent.
   */
  public function viewPrint($export_type, $view_name, $display_id) {
    // Create the Print engine plugin.
    $config = $this->config('entity_print.settings');

    /** @var \Drupal\views\Entity\View $view */
    $view = $this->entityTypeManager->getStorage('view')->load($view_name);
    $executable = $view->getExecutable();
    $executable->setDisplay($display_id);

    if ($args = $this->currentRequest->query->get('view_args')) {
      $executable->setArguments($args);
    }

    try {
      $print_engine = $this->pluginManager->createSelectedInstance($export_type);
    }
    catch (PrintEngineException $e) {
      // Build a safe markup string using Xss::filter() so that the instructions
      // for installing dependencies can contain quotes.
      drupal_set_message(new FormattableMarkup('Error generating Print: ' . Xss::filter($e->getMessage()), []), 'error');

      $url = $executable->hasUrl(NULL, $display_id) ? $executable->getUrl(NULL, $display_id)->toString() : Url::fromUserInput('<front>');
      return new RedirectResponse($url);
    }

    return (new StreamedResponse(function() use ($view, $print_engine, $config) {
      // The printed document is sent straight to the browser.
      $this->printBuilder->deliverPrintable([$view], $print_engine, $config->get('force_download'), $config->get('default_css'));
    }))->send();
  }

  /**
   * Validate that the current user has access.
   *
   * We need to validate that the user is allowed to access this entity also the
   * print version.
   *
   * @param string $export_type
   *   The export type.
   * @param string $view_name
   *   The view name
   * @param string $display_id
   *   The view display to render.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result object.
   *
   * @TODO, improve permissions in https://www.drupal.org/node/2759553
   */
  public function checkAccess($export_type, $view_name, $display_id) {
    $view = $this->entityTypeManager->getStorage('view')->load($view_name)->getExecutable();
    $account = $this->currentUser();

    // Check the Entity Print Views permission.
    $result = AccessResult::allowedIfHasPermission($account, 'entity print views access');

    // Also check the permissions defined by the view.
    return $result->isAllowed() && $view->access($display_id, $account) ? $result : AccessResult::forbidden();
  }

}
