<?php

declare(strict_types=1);

namespace Drupal\Tests\seahub_work_orders\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\seahub_work_orders\WorkOrderStorage;

/**
 * Tests soft-delete behavior for Work Orders.
 */
final class WorkOrderSoftDeleteTest extends KernelTestBase {

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
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('work_order');
  }

  public function testSoftDeletedAreExcludedByDefault(): void {
    $storage = $this->container->get('entity_type.manager')->getStorage('work_order');
    self::assertInstanceOf(WorkOrderStorage::class, $storage);

    $active = $storage->create([
      'title' => 'Active 1',
      'status' => 'open',
      'deleted_at' => NULL,
    ]);
    $active->save();

    $deleted = $storage->create([
      'title' => 'Deleted 1',
      'status' => 'open',
      'deleted_at' => time(),
    ]);
    $deleted->save();

    // By default, the storage query must exclude soft-deleted.
    $ids = $storage->getQuery()->accessCheck(FALSE)->execute();

    // Normalize IDs to integers (EntityQuery returns string IDs).
    $ids = array_map('intval', array_values($ids));

    // Expected: only the active item is returned.
    self::assertEquals([(int) $active->id()], $ids, 'Soft-deleted work orders must be excluded by default.');

    // When explicitly tagged, deleted items must be included.
    $ids_including_deleted = $storage->getQuery()
      ->accessCheck(FALSE)
      ->addTag(WorkOrderStorage::TAG_INCLUDE_DELETED)
      ->execute();

    // Normalize IDs to integers.
    $ids_including_deleted = array_map('intval', array_values($ids_including_deleted));

    sort($ids_including_deleted);
    $expected = [(int) $active->id(), (int) $deleted->id()];
    sort($expected);

    self::assertEquals($expected, $ids_including_deleted, 'Tagged queries must include soft-deleted work orders.');
  }

}
