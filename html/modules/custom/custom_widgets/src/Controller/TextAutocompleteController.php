<?php

namespace Drupal\custom_widgets\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Component\Utility\Tags;
use Drupal\Component\Utility\Unicode;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Class TextAutocompleteController.
 */
class TextAutocompleteController extends ControllerBase {

  /**
   * Handleautocomplete.
   *
   * @return string
   *   Return Hello string.
   */
  public function handleAutocomplete(Request $request, $field_name = NULL, $max_items = 15, $entity_type_id = NULL, $matching_method = 'contains', $uid = '', $hash = '') {
    $matches = [];
    $entity_type_id = $entity_type_id ?: $request->query->get('entity_type_id') ?: 'node';

    // Check hash.
    $calculated_hash = custom_widgets_calculate_hash([
      'field_name' => $field_name,
      'count' => $max_items,
      'entity_type_id' => $entity_type_id,
      'matching_method' => $matching_method,
      'uid' => $uid,
    ]);

    if ($calculated_hash != $hash) {
      throw new AccessDeniedHttpException('Invalid parameters.');
    }

    if ($input = $request->query->get('q')) {
      $typed_string = Tags::explode($input);
      $typed_string = Unicode::strtolower(array_pop($typed_string));

      $options = custom_widgets_get_allowed_options($entity_type_id, $field_name);

      foreach ($options as $key => $option) {
        if ($matching_method == 'contains') {
          if (stripos($option, $input) !== FALSE) {
            $matches[] = [
              'value' => $option . ' (' . $key . ')',
              'label' => $option,
            ];
          }
        }
        else {
          if (stripos($option, $input) === 0) {
            $matches[] = [
              'value' => $option . ' (' . $key . ')',
              'label' => $option,
            ];
          }
        }

        if (count($matches) >= $max_items) {
          break;
        }
      }
    }

    return new JsonResponse($matches);
  }

  /**
   * Handleautocomplete.
   *
   * @return string
   *   Return Hello string.
   */
  public function handleAutocompleteSelect2(Request $request, $field_name = NULL, $max_items = 15, $entity_type_id = NULL, $matching_method = 'contains', $uid = '', $hash = '') {
    $matches = [];
    $entity_type_id = $entity_type_id ?: $request->query->get('entity_type_id') ?: 'node';

    // Check hash.
    $calculated_hash = custom_widgets_calculate_hash([
      'field_name' => $field_name,
      'count' => $max_items,
      'entity_type_id' => $entity_type_id,
      'matching_method' => $matching_method,
      'uid' => $uid,
    ]);

    if ($calculated_hash != $hash) {
      throw new AccessDeniedHttpException('Invalid parameters.');
    }

    if ($input = $request->query->get('q')) {
      $typed_string = Tags::explode($input);
      $typed_string = Unicode::strtolower(array_pop($typed_string));

      $options = custom_widgets_get_allowed_options($entity_type_id, $field_name);

      foreach ($options as $key => $option) {
        if ($matching_method == 'contains') {
          if (stripos($option, $typed_string) !== FALSE) {
            $matches[$key] = [
              'id' => $key,
              'text' => $option,
            ];
          }
        }
        else {
          if (stripos($option, $typed_string) === 0) {
            $matches[$key] = [
              'id' => $key,
              'text' => $option,
            ];
          }
        }

        if (count($matches) >= $max_items) {
          break;
        }
      }
    }

    return new JsonResponse(['results' => array_values($matches)]);
  }

}
