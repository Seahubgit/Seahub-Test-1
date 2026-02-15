<?php

declare(strict_types=1);

namespace Drupal\Tests\seahub_work_orders\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests the Work Order API endpoint.
 *
 * @group seahub_work_orders
 */
final class WorkOrderApiTest extends KernelTestBase {

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
    $this->installConfig(['system']);
  }

  /**
   * Tests that the API excludes soft-deleted work orders by default.
   */
  public function testApiExcludesSoftDeletedByDefault(): void {
    $storage = $this->container->get('entity_type.manager')->getStorage('work_order');

    // Create an active work order.
    $active = $storage->create([
      'status' => 'open',
      'deleted_at' => NULL,
    ]);
    $active->save();

    // Create a soft-deleted work order.
    $deleted = $storage->create([
      'status' => 'open',
      'deleted_at' => time(),
    ]);
    $deleted->save();

    // Create the API controller.
    $controller = $this->container
      ->get('controller_resolver')
      ->getController(Request::create('/api/work-orders'));

    // If controller resolver doesn't work, create it manually.
    if (!$controller) {
      $controller = \Drupal\seahub_work_orders\Controller\WorkOrderApiController::create($this->container);
      $controller = [$controller, 'list'];
    }

    // Create a request.
    $request = Request::create('/api/work-orders', 'GET');

    // Call the controller.
    $response = call_user_func($controller, $request);

    // Decode the JSON response.
    $data = json_decode($response->getContent(), TRUE);

    // Assert that only the active work order is returned.
    self::assertCount(1, $data['data'], 'API should return only 1 work order.');
    self::assertEquals((int) $active->id(), $data['data'][0]['id'], 'API should return the active work order.');
    self::assertNull($data['data'][0]['deleted_at'], 'Returned work order should not be deleted.');
  }

  /**
   * Tests API filtering by status.
   */
  public function testApiFilterByStatus(): void {
    $storage = $this->container->get('entity_type.manager')->getStorage('work_order');

    // Create work orders with different statuses.
    $open = $storage->create(['status' => 'open']);
    $open->save();

    $draft = $storage->create(['status' => 'draft']);
    $draft->save();

    $done = $storage->create(['status' => 'done']);
    $done->save();

    // Test filtering by 'open' status.
    $controller = \Drupal\seahub_work_orders\Controller\WorkOrderApiController::create($this->container);
    $request = Request::create('/api/work-orders', 'GET', ['status' => 'open']);
    $response = $controller->list($request);
    $data = json_decode($response->getContent(), TRUE);

    self::assertCount(1, $data['data'], 'Should return 1 open work order.');
    self::assertEquals('open', $data['data'][0]['status']);
  }

  /**
   * Tests API pagination.
   */
  public function testApiPagination(): void {
    $storage = $this->container->get('entity_type.manager')->getStorage('work_order');

    // Create 15 work orders.
    for ($i = 0; $i < 15; $i++) {
      $work_order = $storage->create(['status' => 'open']);
      $work_order->save();
    }

    // Test first page with limit 5.
    $controller = \Drupal\seahub_work_orders\Controller\WorkOrderApiController::create($this->container);
    $request = Request::create('/api/work-orders', 'GET', ['page' => 0, 'limit' => 5]);
    $response = $controller->list($request);
    $data = json_decode($response->getContent(), TRUE);

    self::assertCount(5, $data['data'], 'Should return 5 work orders on first page.');
    self::assertEquals(15, $data['meta']['total'], 'Total should be 15.');
    self::assertEquals(3, $data['meta']['total_pages'], 'Should have 3 pages.');

    // Test second page.
    $request = Request::create('/api/work-orders', 'GET', ['page' => 1, 'limit' => 5]);
    $response = $controller->list($request);
    $data = json_decode($response->getContent(), TRUE);

    self::assertCount(5, $data['data'], 'Should return 5 work orders on second page.');
    self::assertEquals(1, $data['meta']['page'], 'Page should be 1.');
  }

}
