<?php

namespace Drupal\islandora_datacite_doi\Plugin\DataProfile;

use Drupal\Core\Form\FormStateInterface;
use Drupal\dgi_actions\Plugin\DataProfileBase;

/**
 * Datacite Data profile.
 *
 * @DataProfile(
 *   id = "datacite",
 *   label = @Translation("Datacite"),
 *   description = @Translation("Datacite Data Profile for interacting with Datacite API.")
 * )
 */
class DataciteDataProfile extends DataProfileBase {

  /**
   * Datacite data profile constructor.
   *
   * @param array $configuration
   *   Array containing default configuration for the plugin.
   * @param string $plugin_id
   *   The ID of the plugin being instantiated.
   * @param array $plugin_definition
   *   Array describing the plugin definition.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function modifyData(array $data): array {
    $datacite_data = [];
    foreach ($data as $field => $value) {
      $datacite_data["datacite.$field"] = $value;
    }
    return $datacite_data;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'title' => NULL,
      'author' => NULL,
      'ror' => NULL,
      'publisher' => NULL,
      'year' => NULL,
      'rtypeGeneral' => NULL,
      'rtype' => NULL,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    // The available fields from the entity/bundle are passed through a
    // temporary value in the form state.
    $available_fields = $form_state->getTemporaryValue('available_fields');
    $form['title'] = [
      '#title' => $this->t('Title'),
      '#description' => $this->t('Title of the object being given a DOI.'),
      '#type' => 'select',
      '#options' => $available_fields,
      '#empty_option' => $this->t('- None -'),
      '#default_value' => $this->configuration['title'],
    ];
    $form['author'] = [
      '#title' => $this->t('Author'),
      '#description' => $this->t('Author of the object.'),
      '#type' => 'select',
      '#options' => $available_fields,
      '#empty_option' => $this->t('- None -'),
      '#default_value' => $this->configuration['author'],
    ];
    $form['ror'] = [
      '#title' => $this->t('ROR'),
      '#description' => $this->t('ROR of the publisher.'),
      '#type' => 'select',
      '#options' => $available_fields,
      '#empty_option' => $this->t('- None -'),
      '#default_value' => $this->configuration['ror'],
    ];
    $form['publisher'] = [
      '#title' => $this->t('Publisher'),
      '#description' => $this->t('Name of the publisher.'),
      '#type' => 'select',
      '#options' => $available_fields,
      '#empty_option' => $this->t('- None -'),
      '#default_value' => $this->configuration['publisher'],
    ];
    $form['year'] = [
      '#title' => $this->t('Year'),
      '#description' => $this->t('Year of publication. If field contains more than just a 4 digit year it will extract the first 4 digit number from the field. If the date is an EDTF date, like 199X, it will replace the X\'s with 0\'s.'),
      '#type' => 'select',
      '#options' => $available_fields,
      '#empty_option' => $this->t('- None -'),
      '#default_value' => $this->configuration['year'],
    ];
    $form['rtypeGeneral'] = [
      '#title' => $this->t('Resource Type General'),
      '#description' => $this->t('General resource type. If your selected type is not in Datacite\'s list, it will be set to "Other".'),
      '#type' => 'select',
      '#options' => $available_fields,
      '#empty_option' => $this->t('- None -'),
      '#default_value' => $this->configuration['rtypeGeneral'],
    ];
    $form['rtype'] = [
      '#title' => $this->t('Resource Type'),
      '#description' => $this->t('Resource type.'),
      '#type' => 'select',
      '#options' => $available_fields,
      '#empty_option' => $this->t('- None -'),
      '#default_value' => $this->configuration['rtype'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['title'] = $form_state->getValue('title');
    $this->configuration['author'] = $form_state->getValue('author');
    $this->configuration['ror'] = $form_state->getValue('ror');
    $this->configuration['publisher'] = $form_state->getValue('publisher');
    $this->configuration['year'] = $form_state->getValue('year');
    $this->configuration['rtypeGeneral'] = $form_state->getValue('rtypeGeneral');
    $this->configuration['rtype'] = $form_state->getValue('rtype');
  }

}
