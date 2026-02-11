<?php

declare(strict_types=1);

namespace Drupal\seahub_work_orders\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Form for filtering work orders.
 */
final class WorkOrderFilterForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'seahub_work_orders_filter_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $request = $this->getRequest();

    $form['status'] = [
      '#type' => 'select',
      '#title' => $this->t('Status'),
      '#options' => [
        '' => $this->t('- Any -'),
        'draft' => $this->t('Draft'),
        'open' => $this->t('Open'),
        'done' => $this->t('Done'),
      ],
      '#default_value' => $request->query->get('status', ''),
    ];

    $form['assigned_to'] = [
      '#type' => 'number',
      '#title' => $this->t('Assigned To (User ID)'),
      '#default_value' => $request->query->get('assigned_to', ''),
      '#min' => 1,
    ];

    $form['include_deleted'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include deleted items'),
      '#default_value' => $request->query->get('include_deleted', '0'),
    ];

    $form['actions'] = [
      '#type' => 'actions',
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Filter'),
        '#button_type' => 'primary',
      ],
      'reset' => [
        '#type' => 'link',
        '#title' => $this->t('Reset'),
        '#url' => Url::fromRoute('seahub_work_orders.admin_list'),
        '#attributes' => ['class' => ['button']],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $values = $form_state->getValues();

    $query = [];
    if (!empty($values['status'])) {
      $query['status'] = $values['status'];
    }
    if (!empty($values['assigned_to'])) {
      $query['assigned_to'] = $values['assigned_to'];
    }
    if (!empty($values['include_deleted'])) {
      $query['include_deleted'] = '1';
    }

    $form_state->setRedirect('seahub_work_orders.admin_list', [], ['query' => $query]);
  }

}
