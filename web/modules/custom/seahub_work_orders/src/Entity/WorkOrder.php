<?php

declare(strict_types=1);

namespace Drupal\seahub_work_orders\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerTrait;

/**
 * Defines the Work Order content entity.
 *
 * @ContentEntityType(
 *   id = "work_order",
 *   label = @Translation("Work Order"),
 *   label_collection = @Translation("Work Orders"),
 *   handlers = {
 *     "storage" = "Drupal\seahub_work_orders\WorkOrderStorage",
 *     "access" = "Drupal\Core\Entity\EntityAccessControlHandler",
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData"
 *   },
 *   base_table = "work_order",
 *   admin_permission = "administer seahub work orders",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "title",
 *     "uuid" = "uuid",
 *     "owner" = "uid"
 *   }
 * )
 */
final class WorkOrder extends ContentEntityBase {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setRequired(TRUE)
      ->setSettings([
        'max_length' => 255,
      ]);

    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Status'))
      ->setRequired(TRUE)
      ->setDefaultValue('draft')
      ->setSettings([
        'allowed_values' => [
          'draft' => 'Draft',
          'open' => 'Open',
          'done' => 'Done',
        ],
      ]);

    $fields['assigned_to'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Assigned to'))
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default');

    // Soft delete timestamp. NULL means "active".
    $fields['deleted_at'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Deleted at'))
      ->setDescription(t('Soft delete timestamp; NULL means active.'))
      ->setRequired(FALSE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'));

    return $fields;
  }

}
