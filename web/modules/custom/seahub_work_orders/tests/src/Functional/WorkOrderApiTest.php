<?php

declare(strict_types=1);

namespace Drupal\Tests\seahub_work_orders\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Functional tests for Work Order API endpoint.
 *
 * Verifies:
 * - Filtering by status
 * - Filtering by assigned_to
 * - Combined filters
 * - Soft delete default exclusion
 * - Escape hatch (include_deleted)
 * - Pagination behavior
 * - Empty result cases
 *
 * @group seahub_work_orders
 */
final class WorkOrderApiTest extends BrowserTestBase {

  /**
   * Required modules for entity + field types.
   */
  protected static $modules = [
    'system',
    'user',
    'options', // Required for list_string field type.
    'seahub_work_orders',
  ];

  /**
   * Required for BrowserTestBase.
   */
  protected $defaultTheme = 'stark';

  /**
   * Log in a user with permission to access the API.
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalLogin(
      $this->drupalCreateUser(['access work order api'])
    );
  }

  /**
   * Helper method to create work orders.
   */
  private function createWorkOrder(array $values): void {
    $this->container
      ->get('entity_type.manager')
      ->getStorage('work_order')
      ->create($values)
      ->save();
  }

  /**
   * Ensures filtering by status works correctly.
   */
  public function testStatusFilter(): void {
    $this->createWorkOrder(['title' => 'Open WO', 'status' => 'open']);
    $this->createWorkOrder(['title' => 'Done WO', 'status' => 'done']);

    $this->drupalGet('/api/v1/work-orders', [
      'query' => ['status' => 'open'],
    ]);

    $this->assertSession()->statusCodeEquals(200);

    $response = json_decode($this->getSession()->getPage()->getContent(), TRUE);

    // Only "open" records should be returned.
    $this->assertCount(1, $response['data']);
    $this->assertEquals('Open WO', $response['data'][0]['title']);
  }

  /**
   * Ensures filtering by assigned user works correctly.
   */
  public function testAssignedToFilter(): void {
    $user1 = $this->drupalCreateUser();
    $user2 = $this->drupalCreateUser();

    $this->createWorkOrder([
      'title' => 'User1 WO',
      'status' => 'open',
      'assigned_to' => $user1->id(),
    ]);

    $this->createWorkOrder([
      'title' => 'User2 WO',
      'status' => 'open',
      'assigned_to' => $user2->id(),
    ]);

    $this->drupalGet('/api/v1/work-orders', [
      'query' => ['assigned_to' => $user1->id()],
    ]);

    $this->assertSession()->statusCodeEquals(200);

    $response = json_decode($this->getSession()->getPage()->getContent(), TRUE);

    // Only records assigned to user1 should be returned.
    $this->assertCount(1, $response['data']);
    $this->assertEquals($user1->id(), $response['data'][0]['assigned_to']);
  }

  /**
   * Ensures multiple filters can be combined.
   */
  public function testCombinedFilters(): void {
    $user = $this->drupalCreateUser();

    $this->createWorkOrder([
      'title' => 'Match WO',
      'status' => 'open',
      'assigned_to' => $user->id(),
    ]);

    $this->createWorkOrder([
      'title' => 'Wrong Status',
      'status' => 'done',
      'assigned_to' => $user->id(),
    ]);

    $this->drupalGet('/api/v1/work-orders', [
      'query' => [
        'status' => 'open',
        'assigned_to' => $user->id(),
      ],
    ]);

    $this->assertSession()->statusCodeEquals(200);

    $response = json_decode($this->getSession()->getPage()->getContent(), TRUE);

    // Only the matching record should be returned.
    $this->assertCount(1, $response['data']);
    $this->assertEquals('Match WO', $response['data'][0]['title']);
  }

  /**
   * Ensures soft-deleted records are excluded by default.
   */
  public function testDeletedExcludedByDefault(): void {
    $this->createWorkOrder([
      'title' => 'Deleted WO',
      'status' => 'open',
      'deleted_at' => time(),
    ]);

    $this->drupalGet('/api/v1/work-orders');

    $this->assertSession()->statusCodeEquals(200);

    $response = json_decode($this->getSession()->getPage()->getContent(), TRUE);

    // Soft-deleted record should not be returned.
    $this->assertCount(0, $response['data']);
  }

  /**
   * Ensures the escape hatch includes deleted records when requested.
   */
  public function testIncludeDeleted(): void {
    $this->createWorkOrder([
      'title' => 'Deleted WO',
      'status' => 'open',
      'deleted_at' => time(),
    ]);

    $this->drupalGet('/api/v1/work-orders', [
      'query' => ['include_deleted' => 1],
    ]);

    $this->assertSession()->statusCodeEquals(200);

    $response = json_decode($this->getSession()->getPage()->getContent(), TRUE);

    // Deleted record should now be returned.
    $this->assertCount(1, $response['data']);
  }

  /**
   * Ensures pagination limits results correctly.
   */
  public function testPagination(): void {
    for ($i = 1; $i <= 3; $i++) {
      $this->createWorkOrder([
        'title' => 'WO ' . $i,
        'status' => 'open',
      ]);
    }

    $this->drupalGet('/api/v1/work-orders', [
      'query' => [
        'page' => 1,
        'limit' => 2,
      ],
    ]);

    $this->assertSession()->statusCodeEquals(200);

    $response = json_decode($this->getSession()->getPage()->getContent(), TRUE);

    // Only 2 results should be returned.
    $this->assertCount(2, $response['data']);
  }

  /**
   * Ensures API returns empty array when no records match.
   */
  public function testNoResults(): void {
    $this->createWorkOrder([
      'title' => 'Only Open',
      'status' => 'open',
    ]);

    $this->drupalGet('/api/v1/work-orders', [
      'query' => ['status' => 'done'],
    ]);

    $this->assertSession()->statusCodeEquals(200);

    $response = json_decode($this->getSession()->getPage()->getContent(), TRUE);

    // No matching records should be returned.
    $this->assertCount(0, $response['data']);
  }

}
