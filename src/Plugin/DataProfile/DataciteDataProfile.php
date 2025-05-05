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
      'publisher' => NULL,
      'year' => NULL,
      'rtypeGeneral' => NULL,
      'rtype' => NULL,
      'hostInstitution' => NULL,
      'supervisor' => NULL,
      'dateIssued' => NULL,
      'language' => NULL,
      'rights' => NULL,
      'abstract' => NULL,
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
      '#required' => TRUE,
    ];
    $form['author'] = [
      '#title' => $this->t('Author(s)'),
      '#description' => $this->t('Author(s) of the object. If author is a taxonomy term and the taxonomy has a URL field called field_orcid, that value is automatically pulled as well.'),
      '#type' => 'select',
      '#options' => $available_fields,
      '#empty_option' => $this->t('- None -'),
      '#default_value' => $this->configuration['author'],
      '#required' => TRUE,
    ];
    $form['publisher'] = [
      '#title' => $this->t('Publisher'),
      '#description' => $this->t('Name of the publisher. If publisher is a taxonomy term and the taxonomy has a URL field called field_ror, that value is automatically pulled as well.'),
      '#type' => 'select',
      '#options' => $available_fields,
      '#empty_option' => $this->t('- None -'),
      '#default_value' => $this->configuration['publisher'],
      '#required' => TRUE,
    ];
    $form['year'] = [
      '#title' => $this->t('Year'),
      '#description' => $this->t('Year of publication. If field contains more than just a 4 digit year it will extract the first 4 digit number from the field. If the date is an EDTF date, like 199X, it will replace the X\'s with 0\'s.'),
      '#type' => 'select',
      '#options' => $available_fields,
      '#empty_option' => $this->t('- None -'),
      '#default_value' => $this->configuration['year'],
      '#required' => TRUE,
    ];
    $form['rtypeGeneral'] = [
      '#title' => $this->t('Resource Type General'),
      '#description' => $this->t('General resource type. If your selected type is not in DataCite\'s list, it will be set to "Other".'),
      '#type' => 'select',
      '#options' => $available_fields,
      '#empty_option' => $this->t('- None -'),
      '#default_value' => $this->configuration['rtypeGeneral'],
      '#required' => TRUE,
    ];
    $form['rtype'] = [
      '#title' => $this->t('Resource Type'),
      '#description' => $this->t('Resource type.'),
      '#type' => 'select',
      '#options' => $available_fields,
      '#empty_option' => $this->t('- None -'),
      '#default_value' => $this->configuration['rtype'],
    ];
    $form['hostInstitution'] = [
      '#title' => $this->t('Hosting Institution'),
      '#description' => $this->t('Name of the host institution. If host is a taxonomy term and the taxonomy has a URL field called field_ror, that value is automatically pulled as well.'),
      '#type' => 'select',
      '#options' => $available_fields,
      '#empty_option' => $this->t('- None -'),
      '#default_value' => $this->configuration['hostInstitution'],
    ];
    $form['supervisor'] = [
      '#title' => $this->t('Thesis Supervisor(s)'),
      '#description' => $this->t('Name of the thesis/dissertation supervisor(s). If supervisor is a taxonomy term and the taxonomy has a URL field called field_orcid, that value is automatically pulled as well.'),
      '#type' => 'select',
      '#options' => $available_fields,
      '#empty_option' => $this->t('- None -'),
      '#default_value' => $this->configuration['supervisor'],
    ];
    $form['dateIssued'] = [
      '#title' => $this->t('Date Issued'),
      '#description' => $this->t('Issue Date for the object. X\'s in date will be reaplced with 0\'s. If the date still does not match the format YYYY-MM-DD, the first 4 digit number will be used, and the full text of this field will be added to the dateInformation attribute.'),
      '#type' => 'select',
      '#options' => $available_fields,
      '#empty_option' => $this->t('- None -'),
      '#default_value' => $this->configuration['dateIssued'],
    ];
    $form['language'] = [
      '#title' => $this->t('Language'),
      '#description' => $this->t('The primary language of the resource.'),
      '#type' => 'select',
      '#options' => $available_fields,
      '#empty_option' => $this->t('- None -'),
      '#default_value' => $this->configuration['language'],
    ];
    $form['rights'] = [
      '#title' => $this->t('Rights'),
      '#description' => $this->t('Rights information for the resource.'),
      '#type' => 'select',
      '#options' => $available_fields,
      '#empty_option' => $this->t('- None -'),
      '#default_value' => $this->configuration['rights'],
    ];
    $form['abstract'] = [
      '#title' => $this->t('Abstract'),
      '#description' => $this->t('A description with it\'s type set to abstract.'),
      '#type' => 'select',
      '#options' => $available_fields,
      '#empty_option' => $this->t('- None -'),
      '#default_value' => $this->configuration['abstract'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['title'] = $form_state->getValue('title');
    $this->configuration['author'] = $form_state->getValue('author');
    $this->configuration['publisher'] = $form_state->getValue('publisher');
    $this->configuration['year'] = $form_state->getValue('year');
    $this->configuration['rtypeGeneral'] = $form_state->getValue('rtypeGeneral');
    $this->configuration['rtype'] = $form_state->getValue('rtype');
    $this->configuration['hostInstitution'] = $form_state->getValue('hostInstitution');
    $this->configuration['supervisor'] = $form_state->getValue('supervisor');
    $this->configuration['dateIssued'] = $form_state->getValue('dateIssued');
    $this->configuration['language'] = $form_state->getValue('language');
    $this->configuration['rights'] = $form_state->getValue('rights');
    $this->configuration['abstract'] = $form_state->getValue('abstract');

  }

}
