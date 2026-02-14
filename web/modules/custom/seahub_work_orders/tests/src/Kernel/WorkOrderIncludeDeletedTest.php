<?php

declare(strict_types=1);

namespace Drupal\Tests\seahub_work_orders\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\seahub_work_orders\Entity\WorkOrder;
use Drupal\seahub_work_orders\WorkOrderStorage;

/**
 * Tests that soft-deleted work orders can be included via query tag.
 *
 * @group seahub_work_orders
 */
final class WorkOrderIncludeDeletedTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'text',
    'options',
    'seahub_work_orders',
  ];

  /**
   * Tests that deleted items are included when the tag is present.
   */
  public function testIncludeDeletedWithTag(): void {
    // Install entity schema.
    $this->installEntitySchema('user');
    $this->installEntitySchema('work_order');

    // Create a normal (active) work order.
    $active = WorkOrder::create([
      'title' => 'Active work order',
      'status' => 'open',
    ]);
    $active->save();

    // Create a soft-deleted work order.
    $deleted = WorkOrder::create([
      'title' => 'Deleted work order',
      'status' => 'done',
      'deleted_at' => time(),
    ]);
    $deleted->save();

    // Build a query that explicitly includes deleted items.
    $query = \Drupal::entityQuery('work_order')->accessCheck(FALSE);
    $query->addTag(WorkOrderStorage::TAG_INCLUDE_DELETED);
    $ids = $query->execute();

    // We should get BOTH work orders.
    $this->assertCount(2, $ids, 'Both active and deleted work orders are returned when tag is used.');
  }

}
