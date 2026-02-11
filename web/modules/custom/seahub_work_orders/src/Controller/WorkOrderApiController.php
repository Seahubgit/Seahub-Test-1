<?php

declare(strict_types=1);

namespace Drupal\seahub_work_orders\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\seahub_work_orders\WorkOrderStorage;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * API controller for Work Orders.
 */
final class WorkOrderApiController extends ControllerBase {

  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
  ) {
    $this->entityTypeManager = $entityTypeManager;
  }

  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('entity_type.manager'),
    );
  }

  /**
   * Lists work orders as JSON.
   *
   * Query params:
   * - status: draft|open|done
   * - assigned_to: uid
   * - page: int (1-based)
   * - limit: int (default 20, max 100)
   */
  public function list(Request $request): JsonResponse {
    $status = (string) $request->query->get('status', '');
    $assigned_to = (string) $request->query->get('assigned_to', '');
    $page = max(1, (int) $request->query->get('page', 1));
    $limit = (int) $request->query->get('limit', 20);
    $limit = max(1, min(100, $limit));
    $offset = ($page - 1) * $limit;

    /** @var \Drupal\seahub_work_orders\WorkOrderStorage $storage */
    $storage = $this->entityTypeManager->getStorage('work_order');

    $query = $storage->getQuery()
      ->accessCheck(TRUE)
      ->sort('created', 'DESC')
      ->range($offset, $limit);

    if ($status !== '') {
      $query->condition('status', $status);
    }
    if ($assigned_to !== '') {
      $query->condition('assigned_to', (int) $assigned_to);
    }

    // Default behavior relies on WorkOrderStorage::getQuery() to exclude deleted.
    $ids = $query->execute();
    $entities = $ids ? $storage->loadMultiple($ids) : [];

    $data = [];
    foreach ($entities as $entity) {
      /** @var \Drupal\seahub_work_orders\Entity\WorkOrder $entity */
      $data[] = [
        'id' => (int) $entity->id(),
        'title' => (string) $entity->label(),
        'status' => (string) $entity->get('status')->value,
        'assigned_to' => $entity->get('assigned_to')->target_id ? (int) $entity->get('assigned_to')->target_id : NULL,
        'deleted_at' => $entity->get('deleted_at')->value ? (int) $entity->get('deleted_at')->value : NULL,
        'created' => (int) $entity->getCreatedTime(),
      ];
    }

    return new JsonResponse([
      'page' => $page,
      'limit' => $limit,
      'count' => count($data),
      'data' => $data,
    ]);
  }

}
