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
   * Query parameters:
   * - status: draft|open|done
   * - assigned_to: uid
   * - page: int (1-based)
   * - limit: int (default 20, max 100)
   * - include_deleted: 1 (optional)
   */
  public function list(Request $request): JsonResponse {
    $status = (string) $request->query->get('status', '');
    $assigned_to = (string) $request->query->get('assigned_to', '');
    $include_deleted = (bool) $request->query->get('include_deleted', FALSE);

    $page = max(1, (int) $request->query->get('page', 1));
    $limit = max(1, min(100, (int) $request->query->get('limit', 20)));
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

    // Add escape hatch tag if requested.
    if ($include_deleted) {
      $query->addTag(WorkOrderStorage::TAG_INCLUDE_DELETED);
    }

    // Soft-delete filtering is applied in hook_entity_query_alter().
    $ids = $query->execute();
    $entities = $ids ? $storage->loadMultiple($ids) : [];

    $data = [];

    foreach ($entities as $entity) {
      /** @var \Drupal\seahub_work_orders\Entity\WorkOrder $entity */
      $data[] = [
        'id' => (int) $entity->id(),
        'title' => (string) $entity->label(),
        'status' => (string) $entity->get('status')->value,
        'assigned_to' => $entity->get('assigned_to')->target_id
          ? (int) $entity->get('assigned_to')->target_id
          : NULL,
        'deleted_at' => $entity->get('deleted_at')->value
          ? (int) $entity->get('deleted_at')->value
          : NULL,
        'created' => (int) $entity->get('created')->value,
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
