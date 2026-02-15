<?php

declare(strict_types=1);

namespace Drupal\seahub_work_orders\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * API controller for Work Orders.
 */
final class WorkOrderApiController extends ControllerBase {

  /**
   * Returns a list of work orders.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response.
   */
  public function list(Request $request): JsonResponse {
    /** @var \Drupal\seahub_work_orders\WorkOrderStorage $storage */
    $storage = $this->entityTypeManager()->getStorage('work_order');

    // Get filter parameters.
    $status = $request->query->get('status', '');
    $assigned_to = $request->query->get('assigned_to', '');

    // Get pagination parameters.
    $page = max(0, (int) $request->query->get('page', 0));
    $limit = min(100, max(1, (int) $request->query->get('limit', 10)));

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

    // Note: Soft-deleted items are excluded by default
    // (handled in WorkOrderStorage::getQuery()).

    // Count total (before pagination).
    $count_query = clone $query;
    $total = count($count_query->execute());

    // Apply pagination.
    $query->range($page * $limit, $limit);

    // Execute query.
    $ids = $query->execute();
    $work_orders = $ids ? $storage->loadMultiple($ids) : [];

    // Build response data.
    $data = [];
    foreach ($work_orders as $work_order) {
      /** @var \Drupal\seahub_work_orders\Entity\WorkOrder $work_order */
      $assigned_user = $work_order->get('assigned_to')->entity;

      $data[] = [
        'id' => (int) $work_order->id(),
        'status' => $work_order->get('status')->value,
        'assigned_to' => $assigned_user ? [
          'id' => (int) $assigned_user->id(),
          'name' => $assigned_user->getDisplayName(),
        ] : null,
        'created' => (int) $work_order->get('created')->value,
        'deleted_at' => $work_order->get('deleted_at')->value
          ? (int) $work_order->get('deleted_at')->value
          : null,
      ];
    }

    // Build pagination metadata.
    $total_pages = $limit > 0 ? (int) ceil($total / $limit) : 0;

    return new JsonResponse([
      'data' => $data,
      'meta' => [
        'page' => $page,
        'limit' => $limit,
        'total' => $total,
        'total_pages' => $total_pages,
      ],
    ]);
  }

}
