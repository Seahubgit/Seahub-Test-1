<?php

declare(strict_types=1);

namespace Drupal\seahub_work_orders;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Database\Query\SelectInterface;

/**
 * Storage handler for Work Orders.
 *
 * NOTE: This is intentionally incomplete. Candidates must implement:
 * - default filtering to exclude soft-deleted entities (deleted_at IS NULL)
 * - an escape hatch to include deleted items (e.g. via query tag).
 */
final class WorkOrderStorage extends SqlContentEntityStorage {

  /**
   * Query tag used to explicitly include soft-deleted items.
   */
  public const TAG_INCLUDE_DELETED = 'seahub_work_orders_include_deleted';

  /**
   * {@inheritdoc}
   */
  public function getQuery($conjunction = 'AND'): QueryInterface {
    return parent::getQuery($conjunction);
  }

  /**
   * {@inheritdoc}
   */
  protected function buildQuery($ids, $revision_id = FALSE): SelectInterface {
    $query = parent::buildQuery($ids, $revision_id);

    // If the query does NOT explicitly ask to include deleted items,
    // exclude soft-deleted records by default.
    if (!$query->hasTag(self::TAG_INCLUDE_DELETED)) {
      // Base table alias is usually the entity table.
      // Using baseTable property directly as getBaseTable() is not standard in SqlContentEntityStorage.
      $query->condition($this->baseTable . '.deleted_at', NULL, 'IS NULL');
    }

    return $query;
  }

}
