<?php

namespace Drupal\entity_reference_sorted\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceEntityFormatter;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldDefinitionInterface;

/**
 * Plugin implementation of the 'entity reference sorted' formatter.
 *
 * @FieldFormatter(
 *   id = "entity_reference_sorted",
 *   label = @Translation("Rendered entity (sorted)"),
 *   description = @Translation("Display the referenced entities rendered by entity_view(), sorted."),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class EntityReferenceSorted extends EntityReferenceEntityFormatter {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'sort_field' => 'title',
      'sort_asc' => 1,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);
    $elements['sort_field'] = [
      '#type' => 'select',
      '#options' => $this->getFieldOptions(),
      '#empty_option' => ['' => 'NONE (respect drag and drop weight)'],
      '#title' => t('Sort by'),
      '#default_value' => $this->getSetting('sort_field'),
      '#required' => FALSE,
    ];
    $elements['sort_asc'] = [
      '#type' => 'checkbox',
      '#title' => t('Sort Ascending'),
      '#default_value' => $this->getSetting('sort_asc'),
      '#required' => FALSE,
    ];
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    $summary[] = t('Sort by: @sort_field , @asc', ['@sort_field' => $this->getSetting('sort_field') ? $this->getSetting('sort_field') : 'NONE', '@asc' => $this->getSetting('sort_asc') ? 'ASC' : 'DESC']);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntitiesToView(EntityReferenceFieldItemListInterface $items, $langcode) {
    $entities = parent::getEntitiesToView($items, $langcode);
    if($this->getSetting('sort_field')){
      usort($entities, array($this, 'entitySort'));
    }
    return $entities;
  }

  public function entitySort($a, $b){
    $sort_asc = $this->getSetting('sort_asc') ? 1 : -1;
    $sort_field = $this->getSetting('sort_field');
    if(isset($a->{$sort_field}->value) && isset($b->{$sort_field}->value)){
      if($a->{$sort_field}->value == $b->{$sort_field}->value){
        return 0;
      }
      return ($a->{$sort_field}->value < $b->{$sort_field}->value) ? -$sort_asc : $sort_asc;
    }
    //Down here? At least one value doesn't exist.
    //values that exist are placed nearer the top of the list whether ASC or DESC.
    if(isset($a->{$sort_field}->value)){
      return -1;
    }elseif(isset($b->{$sort_field}->value)){
      return 1;
    }else{
      return 0;
    }
  }

  public function getFieldOptions(){
    $entity_type = $this->fieldDefinition->getFieldStorageDefinition()->getSetting('target_type');
    $bundles = \Drupal::service('entity_type.bundle.info')->getBundleInfo($entity_type);
    $fields = [];
    foreach($bundles as $bundle => $label){
      $fields = array_merge($fields, \Drupal::entityManager()->getFieldDefinitions($entity_type, $bundle));
    }
    $field_options = [];
    foreach($fields as $key => $field){
      $field_options[$key] = $key;
    }
    return $field_options;
  }

}
