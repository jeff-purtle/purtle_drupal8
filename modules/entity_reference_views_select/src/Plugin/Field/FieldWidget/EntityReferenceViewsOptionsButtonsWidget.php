<?php

namespace Drupal\entity_reference_views_select\Plugin\Field\FieldWidget;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsWidgetBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\views\ViewExecutableFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Plugin implementation of the 'erviews_options_buttons' widget.
 *
 * @FieldWidget(
 *   id = "erviews_options_buttons",
 *   label = @Translation("Entity Reference Views Check boxes/radio buttons"),
 *   field_types = {
 *     "entity_reference",
 *   },
 *   multiple_values = TRUE
 * )
 */
class EntityReferenceViewsOptionsButtonsWidget extends OptionsWidgetBase implements ContainerFactoryPluginInterface {

  /**
   * @var RendererInterface $renderer
   */
  protected $renderer;

  /**
   * @var ViewExecutableFactory $view_factory
   */
  protected $view_factory;

  /**
   * @var EntityStorageInterface $view_loader
   */
  protected $view_loader;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('entity_type.manager')->getStorage('view'),
      $container->get('views.executable'),
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, EntityStorageInterface $view_loader, ViewExecutableFactory $view_factory, RendererInterface $renderer) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);

    $this->view_loader = $view_loader;
    $this->view_factory = $view_factory;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $options = $this->getOptions($items->getEntity());
    $selected = $this->getSelectedOptions($items);
    if ($this->getFieldSettings()['handler'] == 'views') {
      $view = $this->view_factory->get($this->view_loader->load($this->getFieldSettings()['handler_settings']['view']['view_name']));
      $view->execute($this->getFieldSettings()['handler_settings']['view']['display_name']);
      foreach ($view->result as $row) {
        $row_output = $view->style_plugin->view->rowPlugin->render($row);
        $options[$row->_entity->id()] = $options[$row->_entity->id()]->create($this->renderer->render($row_output));
      }
    }
    // If required and there is one single option, preselect it.
    if ($this->required && count($options) == 1) {
      reset($options);
      $selected = array(key($options));
    }

    if ($this->multiple) {
      $element += array(
        '#type' => 'checkboxes',
        '#default_value' => $selected,
        '#options' => $options,
      );
    }
    else {
      $element += array(
        '#type' => 'radios',
        // Radio buttons need a scalar value. Take the first default value, or
        // default to NULL so that the form element is properly recognized as
        // not having a default value.
        '#default_value' => $selected ? reset($selected) : NULL,
        '#options' => $options,
      );
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEmptyLabel() {
    if (!$this->required && !$this->multiple) {
      return t('N/A');
    }
  }

}
