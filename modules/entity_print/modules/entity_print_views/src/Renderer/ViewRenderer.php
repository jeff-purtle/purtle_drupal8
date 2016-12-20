<?php

namespace Drupal\entity_print_views\Renderer;

use Drupal\Core\Entity\EntityInterface;
use Drupal\entity_print\Renderer\RendererBase;

/**
 * Providers a renderer for Views.
 */
class ViewRenderer extends RendererBase {

  /**
   * {@inheritdoc}
   */
  public function render(EntityInterface $view) {
    /** @var \Drupal\views\Entity\View $view */
    $executable = $view->getExecutable();
    $render = $executable->render();

    // We must remove ourselves from all areas otherwise it will cause an
    // infinite loop when rendering.
    foreach (['header', 'footer', 'empty'] as $area_type) {
      $handlers = &$executable->display_handler->getHandlers($area_type);
      unset($handlers['area_entity_print_views']);
    }

    $render['#pre_render'][] = [static::class, 'preRender'];

    return $render;
  }

  /**
   * {@inheritdoc}
   */
  public function getFilename(array $views) {
    $filenames = [];
    foreach ($views as $view) {
      $filenames[] = $this->sanitizeFilename($view->getExecutable()->getTitle());
    }
    return implode('-', $filenames);
  }

  /**
   * Pre render callback for the view.
   */
  public static function preRender(array $element) {
    // Remove the exposed filters, we don't every want them on the PDF.
    $element['#exposed'] = [];
    return $element;
  }

}
