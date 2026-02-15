<?php

declare(strict_types=1);

namespace Drupal\seahub_work_orders\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for Work Orders admin listing.
 */
final class WorkOrderListController extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $instance = parent::create($container);
    // EntityTypeManager is already available via parent class
    return $instance;
  }

  /**
   * Displays the admin listing of work orders.
   */
  public function listing(Request $request): array {
    /** @var \Drupal\seahub_work_orders\WorkOrderStorage $storage */
    $storage = $this->entityTypeManager()->getStorage('work_order');

    // Get filter parameters from query string.
    $status = $request->query->get('status', '');
    $assigned_to = $request->query->get('assigned_to', '');
    $include_deleted = (bool) $request->query->get('include_deleted', FALSE);

    // Build the query.
    $query = $storage->getQuery()
      ->accessCheck(TRUE)
      ->sort('created', 'DESC');

    // Apply filters.
    if ($status !== '') {
      $query->condition('status', $status);
    }
    if ($assigned_to !== '') {
      $query->condition('assigned_to', (int) $assigned_to);
    }

    // Add escape hatch tag if needed.
    if ($include_deleted) {
      $query->addTag(\Drupal\seahub_work_orders\WorkOrderStorage::TAG_INCLUDE_DELETED);
    }

    // Execute query.
    $ids = $query->execute();
    $work_orders = $ids ? $storage->loadMultiple($ids) : [];

    // Build table rows.
    $rows = [];
    foreach ($work_orders as $work_order) {
      /** @var \Drupal\seahub_work_orders\Entity\WorkOrder $work_order */
      $assigned_user = $work_order->get('assigned_to')->entity;

      $rows[] = [
        'status' => [
          'data' => [
            '#markup' => $this->t('@status', ['@status' => $work_order->get('status')->value]),
          ],
        ],
        'assigned_to' => [
          'data' => [
            '#markup' => $assigned_user
              ? $assigned_user->getDisplayName()
              : $this->t('Unassigned'),
          ],
        ],
        'created' => [
          'data' => [
            '#markup' => \Drupal::service('date.formatter')->format(
              $work_order->get('created')->value,
              'medium'
            ),
          ],
        ],
      ];
    }

    // Build the render array.
    $build = [];

    // Add filters form.
    $build['filters'] = $this->buildFiltersForm($request);

    // Add table.
    $build['table'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Status'),
        $this->t('Assigned To'),
        $this->t('Created'),
      ],
      '#rows' => $rows,
      '#empty' => $this->t('No work orders found.'),
    ];

    return $build;
  }

  /**
   * Builds the filters form.
   */
  private function buildFiltersForm(Request $request): array {
    return $this->formBuilder()->getForm('Drupal\seahub_work_orders\Form\WorkOrderFilterForm');
  }

}
