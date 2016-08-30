<?php
/**
 * @file
 * Contains Drupal\push_notifications\PushNotificationStorageSchema.
 */

namespace Drupal\push_notifications;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema;

/**
 * Defines the push_notification schema handler.
 */
class PushNotificationStorageSchema extends SqlContentEntityStorageSchema {

  /**
   * {@inheritdoc}
   */
  protected function getSharedTableFieldSchema(FieldStorageDefinitionInterface $storage_definition, $table_name, array $column_mapping) {
    $schema = parent::getSharedTableFieldSchema($storage_definition, $table_name, $column_mapping);
    $field_name = $storage_definition->getName();

    if ($table_name == 'push_notifications') {
      switch ($field_name) {
        case 'title':
        case 'langcode':
        case 'created':
          $this->addSharedTableFieldIndex($storage_definition, $schema, TRUE);
          break;
      }
    }

    if ($table_name == 'push_notifications') {
      switch ($field_name) {
        case 'langcode':
        case 'created':
        case 'user_id':
          // Improves the performance of the indexes defined
          // in getEntitySchema().
          $schema['fields'][$field_name]['not null'] = TRUE;
          break;
      }
    }

    return $schema;
  }
}