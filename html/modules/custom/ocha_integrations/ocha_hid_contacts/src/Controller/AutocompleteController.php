<?php

namespace Drupal\ocha_hid_contacts\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Component\Utility\Tags;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Class AutocompleteController.
 */
class AutocompleteController extends ControllerBase {

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
      'count' => (int) $max_items,
      'entity_type_id' => $entity_type_id,
      'matching_method' => $matching_method,
      'uid' => (int) $uid,
    ]);

    if ($calculated_hash != $hash) {
      throw new AccessDeniedHttpException('Invalid parameters.');
    }

    if ($input = $request->query->get('q')) {
      $typed_string = Tags::explode($input);
      $typed_string = mb_strtolower(array_pop($typed_string));

      $options = ocha_hid_contacts_autocomplete($input);

      foreach ($options as $contact) {
        $label = $contact->name;
        if (isset($contact->organization->acronym)) {
          $label .= ' - ' . $contact->organization->acronym;
        }
        elseif (isset($contact->organization->name)) {
          $label .= ' - ' . $contact->organization->name;
        }

        $matches[] = [
          'value' => $label . ' (' . $contact->id . ')',
          'label' => $label,
        ];

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
      'count' => (int) $max_items,
      'entity_type_id' => $entity_type_id,
      'matching_method' => $matching_method,
      'uid' => (int) $uid,
    ]);

    if ($calculated_hash != $hash) {
      throw new AccessDeniedHttpException('Invalid parameters.');
    }

    if ($input = $request->query->get('q')) {
      $typed_string = Tags::explode($input);
      $typed_string = mb_strtolower(array_pop($typed_string));

      $options = ocha_hid_contacts_autocomplete($input);

      foreach ($options as $contact) {
        $label = $contact->name;
        if (isset($contact->organization->acronym)) {
          $label .= ' - ' . $contact->organization->acronym;
        }
        elseif (isset($contact->organization->name)) {
          $label .= ' - ' . $contact->organization->name;
        }

        $matches[] = [
          'id' => $contact->id,
          'text' => $label,
        ];

        if (count($matches) >= $max_items) {
          break;
        }
      }
    }

    return new JsonResponse(['results' => array_values($matches)]);
  }

}
