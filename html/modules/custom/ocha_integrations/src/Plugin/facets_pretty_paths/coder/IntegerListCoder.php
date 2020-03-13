<?php

namespace Drupal\ocha_integrations\Plugin\facets_pretty_paths\coder;

use Drupal\facets_pretty_paths\Coder\CoderPluginBase;

/**
 * Banana facets pretty paths coder.
 *
 * @FacetsPrettyPathsCoder(
 *   id = "ocha_integer_list_coder",
 *   label = @Translation("List item label + id"),
 *   description = @Translation("Use list item label with id, e.g. /color/<strong>blue-2</strong>")
 * )
 */
class IntegerListCoder extends CoderPluginBase {

  /**
   * Encode an id into an alias.
   *
   * @param string $id
   *   An entity id.
   *
   * @return string
   *   An alias.
   */
  public function encode($id) {
    $facet = $this->configuration['facet'];
    $field_name = $facet->getFieldIdentifier();

    // TODO: Get source from facet.
    $entityManager = \Drupal::service('entity_field.manager');
    $fields = $entityManager->getFieldStorageDefinitions('node');
    $options = options_allowed_values($fields[$field_name]);

    if (isset($options[$id])) {
      $label = \Drupal::service('pathauto.alias_cleaner')->cleanString($options[$id]);
      return $label . '-' . $id;
    }

    return $id;
  }

  /**
   * Decodes an alias back to an id.
   *
   * @param string $alias
   *   An alias.
   *
   * @return string
   *   An id.
   */
  public function decode($alias) {
    $exploded = explode('-', $alias);
    $id = array_pop($exploded);

    return $id;
  }

}
