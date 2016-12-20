<?php

namespace Drupal\entity_print_views\Plugin\views\area;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\entity_print\Plugin\ExportTypeManagerInterface;
use Drupal\views\Plugin\views\area\AreaPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Views area handler for a Print button.
 *
 * @ingroup views_area_handlers
 *
 * @ViewsArea("entity_print_views_link")
 */
class EntityPrintViewsLink extends AreaPluginBase {

  /**
   * The export type manager.
   *
   * @var \Drupal\entity_print\Plugin\ExportTypeManagerInterface
   */
  protected $exportTypeManager;

  /**
   * Constructs a new Entity instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\entity_print\Plugin\ExportTypeManagerInterface $export_type_manager
   *   The export type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ExportTypeManagerInterface $export_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->exportTypeManager = $export_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.entity_print.export_type')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $form['export_type'] = array(
      '#type' => 'select',
      '#title' => $this->t('Export Type'),
      '#options' => $this->exportTypeManager->getFormOptions(),
      '#required' => TRUE,
      '#default_value' => $this->options['export_type'],
    );
    $form['link_text'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Link text'),
      '#required' => TRUE,
      '#default_value' => $this->options['link_text'],
    );
  }

  /**
   * {@inheritdoc}
   */
  public function render($empty = FALSE) {
    $route_params = [
      'export_type' => !empty($this->options['export_type']) ? $this->options['export_type'] : 'pdf',
      'view_name' => $this->view->storage->id(),
      'display_id' => $this->view->current_display,
    ];

    return [
      '#type' => 'link',
      '#title' => $this->options['link_text'],
      '#url' => Url::fromRoute('entity_print_views.view', $route_params, [
        'query' => $this->view->getExposedInput() + ['view_args' => $this->view->args],
      ]),
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['export_type'] = ['default' => 'pdf'];
    $options['link_text'] = ['default' => 'View PDF'];
    return $options;
  }

}
