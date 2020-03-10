<?php

namespace Drupal\ocha_integrations\Plugin\jsonapi\FieldEnhancer;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\jsonapi_extras\Plugin\ResourceFieldEnhancerBase;
use Shaper\Util\Context;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Add value to output.
 *
 * @ResourceFieldEnhancer(
 *   id = "ocha_integrations_list_text",
 *   label = @Translation("Add label for list text fields"),
 *   description = @Translation("Add label for list text fields.")
 * )
 */
class ListTextEnhancer extends ResourceFieldEnhancerBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function doUndoTransform($data, Context $context) {
    \Drupal::logger('ocha_integrations')->notice('doUndoTransform');
    \Drupal::logger('ocha_integrations')->notice('<pre>' . print_r(array_keys((array)$context), true) . '</pre>');
    \Drupal::logger('ocha_integrations')->notice('<pre>' . print_r($context->field_item_object, true) . '</pre>');

    return 'test'; //$data;
  }

  /**
   * {@inheritdoc}
   */
  protected function doTransform($value, Context $context) {
    \Drupal::logger('ocha_integrations')->notice('doTransform');
    \Drupal::logger('ocha_integrations')->notice('<pre>' . print_r($value, true) . '</pre>');
    return 'test'; //$value;
  }

    /**
   * {@inheritdoc}
   */
  public function getOutputJsonSchema() {
    return [
      'type' => 'string',
    ];
  }

}
