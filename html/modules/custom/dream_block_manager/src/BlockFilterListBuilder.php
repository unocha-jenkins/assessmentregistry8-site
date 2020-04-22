<?php

namespace Drupal\dream_block_manager;

use Drupal\block\BlockListBuilder;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

/**
 * Extends BlockListBuilder to add our elements only show certain blocks.
 */
class BlockFilterListBuilder extends BlockListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $form['#attached']['library'][] = 'dream_block_manager/dream_block_manager.admin';

    $form['block_filter'] = [
      '#type' => 'textfield',
      '#title' => 'Filter',
      '#weight' => -100,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if (!empty($form_state->getValue('blocks'))) {
      parent::submitForm($form, $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function buildBlocksForm() {
    $form = parent::buildBlocksForm();

    // Add our colums.
    $this->addExtraColumns($form);

    return $form;
  }

  /**
   * Add machine name and path to table.
   */
  protected function addExtraColumns(&$form) {
    // Add machine name.
    $form['#header'] = array_merge(
      array_slice($form['#header'], 0, 1),
      ['machine_name' => 'Machine name'],
      array_slice($form['#header'], 1)
    );

    foreach ($form as $key => $block) {
      // Only act on blocks.
      if (!empty($block['operations']['#links']['edit']['url'])) {
        $form[$key] = array_merge(
          array_slice($form[$key], 0, 2),
          ['machine_name' => ['#plain_text' => $key]],
          array_slice($form[$key], 2)
        );
      }
    }

    // Add path.
    $entity_ids = [];
    foreach (array_keys($form) as $row_key) {
      if (strpos($row_key, 'region-') !== 0) {
        $entity_ids[] = $row_key;
      }
    }
    $entities = $this->storage->loadMultipleOverrideFree($entity_ids);

    if (!empty($entities)) {
      /** @var Block $block */
      foreach ($entities as $block) {
        if (!empty($form[$block->id()])) {
          $row = &$form[$block->id()];

          $block_path = '';
          $visibility = $block->getVisibility();
          if (isset($visibility['request_path'])) {
            $block_path = $visibility['request_path']['pages'];
          }

          foreach (Element::children($row) as $i => $child) {
            $row[$child]['#weight'] = $i;
          }
          $row['path'] = [
            '#markup' => $block_path,
            '#weight' => 1.5,
          ];
          $row['#sorted'] = FALSE;
        }
      }

      // Adjust header.
      array_splice($form['#header'], 2, 0, [$this->t('Path')]);
      // Increase colspan.
      foreach (Element::children($form) as $child) {
        foreach (Element::children($form[$child]) as $gchild) {
          if (isset($form[$child][$gchild]['#wrapper_attributes']['colspan'])) {
            $form[$child][$gchild]['#wrapper_attributes']['colspan'] =
              $form[$child][$gchild]['#wrapper_attributes']['colspan'] + 1;
          }
        }
      }
    }
  }

}
