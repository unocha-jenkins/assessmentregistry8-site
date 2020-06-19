<?php

namespace Drupal\ocha_persons\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Url;
use Drupal\ocha_persons\Entity\PersonEntityInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class PersonEntityController.
 *
 *  Returns responses for Person entity routes.
 */
class PersonEntityController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->dateFormatter = $container->get('date.formatter');
    $instance->renderer = $container->get('renderer');
    return $instance;
  }

  /**
   * Displays a Person entity revision.
   *
   * @param int $person_entity_revision
   *   The Person entity revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionShow($person_entity_revision) {
    $person_entity = $this->entityTypeManager()->getStorage('person_entity')
      ->loadRevision($person_entity_revision);
    $view_builder = $this->entityTypeManager()->getViewBuilder('person_entity');

    return $view_builder->view($person_entity);
  }

  /**
   * Page title callback for a Person entity revision.
   *
   * @param int $person_entity_revision
   *   The Person entity revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionPageTitle($person_entity_revision) {
    $person_entity = $this->entityTypeManager()->getStorage('person_entity')
      ->loadRevision($person_entity_revision);
    return $this->t('Revision of %title from %date', [
      '%title' => $person_entity->label(),
      '%date' => $this->dateFormatter->format($person_entity->getRevisionCreationTime()),
    ]);
  }

  /**
   * Generates an overview table of older revisions of a Person entity.
   *
   * @param \Drupal\ocha_persons\Entity\PersonEntityInterface $person_entity
   *   A Person entity object.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function revisionOverview(PersonEntityInterface $person_entity) {
    $account = $this->currentUser();
    $person_entity_storage = $this->entityTypeManager()->getStorage('person_entity');

    $build['#title'] = $this->t('Revisions for %title', ['%title' => $person_entity->label()]);

    $header = [$this->t('Revision'), $this->t('Operations')];
    $revert_permission = (($account->hasPermission("revert all person entity revisions") || $account->hasPermission('administer person entity entities')));
    $delete_permission = (($account->hasPermission("delete all person entity revisions") || $account->hasPermission('administer person entity entities')));

    $rows = [];

    $vids = $person_entity_storage->revisionIds($person_entity);

    $latest_revision = TRUE;

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\ocha_persons\PersonEntityInterface $revision */
      $revision = $person_entity_storage->loadRevision($vid);
      $username = [
        '#theme' => 'username',
        '#account' => $revision->getRevisionUser(),
      ];

      // Use revision link to link to revisions that are not active.
      $date = $this->dateFormatter->format($revision->getRevisionCreationTime(), 'short');
      if ($vid != $person_entity->getRevisionId()) {
        $link = $this->l($date, new Url('entity.person_entity.revision', [
          'person_entity' => $person_entity->id(),
          'person_entity_revision' => $vid,
        ]));
      }
      else {
        $link = $person_entity->link($date);
      }

      $row = [];
      $column = [
        'data' => [
          '#type' => 'inline_template',
          '#template' => '{% trans %}{{ date }} by {{ username }}{% endtrans %}{% if message %}<p class="revision-log">{{ message }}</p>{% endif %}',
          '#context' => [
            'date' => $link,
            'username' => $this->renderer->renderPlain($username),
            'message' => [
              '#markup' => $revision->getRevisionLogMessage(),
              '#allowed_tags' => Xss::getHtmlTagList(),
            ],
          ],
        ],
      ];
      $row[] = $column;

      if ($latest_revision) {
        $row[] = [
          'data' => [
            '#prefix' => '<em>',
            '#markup' => $this->t('Current revision'),
            '#suffix' => '</em>',
          ],
        ];
        foreach ($row as &$current) {
          $current['class'] = ['revision-current'];
        }
        $latest_revision = FALSE;
      }
      else {
        $links = [];
        if ($revert_permission) {
          $links['revert'] = [
            'title' => $this->t('Revert'),
            'url' => Url::fromRoute('entity.person_entity.revision_revert', [
              'person_entity' => $person_entity->id(),
              'person_entity_revision' => $vid,
            ]),
          ];
        }

        if ($delete_permission) {
          $links['delete'] = [
            'title' => $this->t('Delete'),
            'url' => Url::fromRoute('entity.person_entity.revision_delete', [
              'person_entity' => $person_entity->id(),
              'person_entity_revision' => $vid,
            ]),
          ];
        }

        $row[] = [
          'data' => [
            '#type' => 'operations',
            '#links' => $links,
          ],
        ];
      }

      $rows[] = $row;
    }

    $build['person_entity_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];

    return $build;
  }

}
