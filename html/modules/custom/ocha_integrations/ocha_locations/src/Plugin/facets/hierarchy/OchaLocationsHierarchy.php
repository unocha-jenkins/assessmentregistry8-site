<?php

namespace Drupal\ocha_locations\Plugin\facets\hierarchy;

use Drupal\facets\Hierarchy\HierarchyPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * OchaLocationsHierarchy hierarchy.
 *
 * @FacetsHierarchy(
 *   id = "ocha_locations_hierarchy",
 *   label = @Translation("OCHA locations hierarchy"),
 *   description = @Translation("Hierarchy structure of OCHA locations.")
 * )
 */
class OchaLocationsHierarchy extends HierarchyPluginBase {

  /**
   * Static cache for the nested children.
   *
   * @var array
   */
  protected $nestedChildren = [];

  /**
   * Static cache for the term parents.
   *
   * @var array
   */
  protected $parents = [];

  /**
   * Constructs a Drupal\Component\Plugin\PluginBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getParentIds($id) {
    $location = ocha_locations_get_item($id);
    while ($location = ocha_locations_get_item($location->parent)) {
      $parents[$id][] = $location->id;
    }

    return isset($parents[$id]) ? $parents[$id] : [];
  }

  /**
   * {@inheritdoc}
   */
  public function getNestedChildIds($id) {
    if (isset($this->nestedChildren[$id])) {
      return $this->nestedChildren[$id];
    }

    $location = ocha_locations_get_item($id);
    $children = array_keys($location->children);

    $subchilds = [];
    foreach ($children as $child) {
      $subchilds = array_merge($subchilds, $this->getNestedChildIds($child));
    }
    return $this->nestedChildren[$id] = array_merge($children, $subchilds);
  }

  /**
   * {@inheritdoc}
   */
  public function getChildIds(array $ids) {
    $parents = [];
    foreach ($ids as $id) {
      $location = ocha_locations_get_item($id);
      $parents[$id] = array_keys($location->children);
    }
    $parents = array_filter($parents);
    return $parents;
  }

  /**
   * Returns the parent tid for a given tid, or false if no parent exists.
   *
   * @param int $id
   *   Id.
   *
   * @return int|false
   *   Returns FALSE if no parent is found, else parent tid.
   */
  protected function taxonomyGetParent($id) {
    if (isset($this->parents[$id])) {
      return $this->parents[$id];
    }

    $location = ocha_locations_get_item($id);
    $this->parents[$id] = $location->parent;

    return $this->parents[$id];
  }

}