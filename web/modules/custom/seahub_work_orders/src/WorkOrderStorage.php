<?php

declare(strict_types=1);

namespace Drupal\seahub_work_orders;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Entity\Query\QueryInterface;

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

}
