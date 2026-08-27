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
      'contributor' => NULL,
      'dateIssued' => NULL,
      'language' => NULL,
      'version' => NULL,
      'rights' => NULL,
      'abstract' => NULL,
      'subject' => NULL,
      'hostname' => NULL,
      'hostissn' => NULL,
      'hostissue' => NULL,
      'hostvolume' => NULL,
      'hoststartpage' => NULL,
      'hostendpage' => NULL,
      'note' => NULL,
      'identical' => NULL,
      'identifiers' => [],
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
    $form['contributor'] = [
      '#title' => $this->t('Contributor(s)'),
      '#description' => $this->t('Contributor(s) of the object, e.g. a typed relation field to a person taxonomy term. The relation type is mapped to a DataCite contributorType (unrecognized types fall back to "Other"). If the term has a URL field called field_orcid, that value is automatically pulled as well.'),
      '#type' => 'select',
      '#options' => $available_fields,
      '#empty_option' => $this->t('- None -'),
      '#default_value' => $this->configuration['contributor'],
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
    $form['version'] = [
      '#title' => $this->t('Version'),
      '#description' => $this->t('Version number of the resource, e.g. "1.0".'),
      '#type' => 'select',
      '#options' => $available_fields,
      '#empty_option' => $this->t('- None -'),
      '#default_value' => $this->configuration['version'],
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
    $form['subject'] = [
      '#title' => $this->t('Subject(s)'),
      '#description' => $this->t('Subject(s) for the resource.'),
      '#type' => 'select',
      '#options' => $available_fields,
      '#empty_option' => $this->t('- None -'),
      '#default_value' => $this->configuration['subject'],
    ];
    $form['hostname'] = [
      '#title' => $this->t('Host Journal Name'),
      '#description' => $this->t('The name of the journal that contains the resource. This must be present for the other host fields to be sent to DataCite.'),
      '#type' => 'select',
      '#options' => $available_fields,
      '#empty_option' => $this->t('- None -'),
      '#default_value' => $this->configuration['hostname'],
    ];
    $form['hostissn'] = [
      '#title' => $this->t('Host Journal ISSN'),
      '#description' => $this->t('The ISSN of the journal that contains the resource.'),
      '#type' => 'select',
      '#options' => $available_fields,
      '#empty_option' => $this->t('- None -'),
      '#default_value' => $this->configuration['hostissn'],
    ];
    $form['hostvolume'] = [
      '#title' => $this->t('Host Journal Volume'),
      '#description' => $this->t('The volume of the journal that contains the resource.'),
      '#type' => 'select',
      '#options' => $available_fields,
      '#empty_option' => $this->t('- None -'),
      '#default_value' => $this->configuration['hostvolume'],
    ];
    $form['hostissue'] = [
      '#title' => $this->t('Host Journal Issue'),
      '#description' => $this->t('The issue of the journal that contains the resource.'),
      '#type' => 'select',
      '#options' => $available_fields,
      '#empty_option' => $this->t('- None -'),
      '#default_value' => $this->configuration['hostissue'],
    ];
    $form['hoststartpage'] = [
      '#title' => $this->t('Host Journal Start Page'),
      '#description' => $this->t('The first page in the journal that contains the resource.'),
      '#type' => 'select',
      '#options' => $available_fields,
      '#empty_option' => $this->t('- None -'),
      '#default_value' => $this->configuration['hoststartpage'],
    ];
    $form['hostendpage'] = [
      '#title' => $this->t('Host Journal End Page'),
      '#description' => $this->t('The end page in the journal that contains the resource.'),
      '#type' => 'select',
      '#options' => $available_fields,
      '#empty_option' => $this->t('- None -'),
      '#default_value' => $this->configuration['hostendpage'],
    ];
    $form['note'] = [
      '#title' => $this->t('Other Description'),
      '#description' => $this->t('A description with it\'s type set to other.'),
      '#type' => 'select',
      '#options' => $available_fields,
      '#empty_option' => $this->t('- None -'),
      '#default_value' => $this->configuration['note'],
    ];
    $form['identical'] = [
      '#title' => $this->t('Is Identical To DOI'),
      '#description' => $this->t('DOI field for DOI of identical object. For example, a publisher\'s DOI'),
      '#type' => 'select',
      '#options' => $available_fields,
      '#empty_option' => $this->t('- None -'),
      '#default_value' => $this->configuration['identical'],
    ];
    $form['identifiers'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Identifiers'),
      '#prefix' => '<div id="identifiers-wrapper">',
      '#suffix' => '</div>',
    ];

    $identifier_count = $form_state->get('identifier_count');
    $identifier_values = $form_state->get('identifier_values');

    if ($identifier_count === NULL || $identifier_values === NULL) {
      $saved = $this->configuration['identifiers'] ?? [];
      $identifier_values = !empty($saved) ? $saved : [[]];
      $form_state->set('identifier_values', $identifier_values);
      $form_state->set('identifier_count', count($identifier_values));
      $identifier_count = count($identifier_values);
    }

    $form['identifiers'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Identifiers'),
      '#prefix' => '<div id="identifiers-wrapper">',
      '#suffix' => '</div>',
    ];

    for ($i = 0; $i < $identifier_count; $i++) {
      $saved_value = $identifier_values[$i] ?? [];
      $form['identifiers'][$i] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Identifier @num', ['@num' => $i + 1]),
      ];
      $form['identifiers'][$i]['identifier_type'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Type'),
        '#default_value' => $saved_value['identifier_type'] ?? '',
      ];
      $form['identifiers'][$i]['identifier_value'] = [
        '#type' => 'select',
        '#title' => $this->t('Value'),
        '#options' => $available_fields,
        '#empty_option' => $this->t('- None -'),
        '#default_value' => $saved_value['identifier_value'] ?? '',
      ];
      if ($identifier_count > 1) {
        $form['identifiers'][$i]['remove_identifier'] = [
          '#type' => 'button',
          '#value' => $this->t('Remove'),
          '#name' => 'remove_identifier_' . $i,
          '#ajax' => [
            'callback' => [$this, 'addIdentifierCallback'],
            'wrapper' => 'identifiers-wrapper',
            'event' => 'click',
          ],
          '#executes_submit_callback' => TRUE,
          '#submit' => [[$this, 'removeIdentifierSubmit']],
          '#limit_validation_errors' => [['data', 'identifiers']],
        ];
      }
    }

    $form['identifiers']['add_identifier'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add another identifier'),
      '#submit' => [[$this, 'addIdentifierSubmit']],
      '#ajax' => [
        'callback' => [$this, 'addIdentifierCallback'],
        'wrapper' => 'identifiers-wrapper',
      ],
      '#limit_validation_errors' => [['data', 'identifiers']],
    ];

    return $form;
  }


  public function addIdentifierSubmit(array &$form, FormStateInterface $form_state): void {
    $existing = $form_state->getValue(['data', 'identifiers']) ?? [];
    $values = [];
    foreach ($existing as $key => $item) {
      if (is_int($key)) {
        $values[] = [
          'identifier_type' => $item['identifier_type'] ?? '',
          'identifier_value' => $item['identifier_value'] ?? '',
        ];
      }
    }
    $values[] = [];
    $form_state->set('identifier_values', $values);
    $form_state->set('identifier_count', count($values));
    $form_state->setRebuild();
  }

  /**
   * AJAX callback for the "Add another identifier" button.
   */
  public function addIdentifierCallback(array &$form, FormStateInterface $form_state): array {
    return $form['entity_fieldset']['bundle_fieldset_container']['bundle_fieldset']['dataprofile_fieldset_container']['dataprofile_fieldset']['dataprofile_fields_fieldset_container']['fields_fieldset']['data']['identifiers'];
  }

  /**
   * Submit handler for the "Remove" identifier button.
   */
  public function removeIdentifierSubmit(array &$form, FormStateInterface $form_state): void {
    $trigger = $form_state->getTriggeringElement();
    $index = (int) str_replace('remove_identifier_', '', $trigger['#name']);

    $existing = $form_state->getValue(['data', 'identifiers']) ?? [];
    $values = [];
    foreach ($existing as $key => $item) {
      if (is_int($key)) {
        $values[] = [
          'identifier_type' => $item['identifier_type'] ?? '',
          'identifier_value' => $item['identifier_value'] ?? '',
        ];
      }
    }

    unset($values[$index]);
    $values = array_values($values);

    $user_input = $form_state->getUserInput();
    unset($user_input['data']['identifiers']);
    $form_state->setUserInput($user_input);

    $form_state->set('identifier_values', $values);
    $form_state->set('identifier_count', count($values));
    $form_state->setRebuild();
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
    $this->configuration['contributor'] = $form_state->getValue('contributor');
    $this->configuration['dateIssued'] = $form_state->getValue('dateIssued');
    $this->configuration['language'] = $form_state->getValue('language');
    $this->configuration['version'] = $form_state->getValue('version');
    $this->configuration['rights'] = $form_state->getValue('rights');
    $this->configuration['abstract'] = $form_state->getValue('abstract');
    $this->configuration['subject'] = $form_state->getValue('subject');
    $this->configuration['hostname'] = $form_state->getValue('hostname');
    $this->configuration['hostissn'] = $form_state->getValue('hostissn');
    $this->configuration['hostissue'] = $form_state->getValue('hostissue');
    $this->configuration['hostvolume'] = $form_state->getValue('hostvolume');
    $this->configuration['hoststartpage'] = $form_state->getValue('hoststartpage');
    $this->configuration['hostendpage'] = $form_state->getValue('hostendpage');
    $this->configuration['note'] = $form_state->getValue('note');
    $this->configuration['identical'] = $form_state->getValue('identical');

    $identifier_count = $form_state->get('identifier_count') ?? 1;
    $identifiers = [];
    for ($i = 0; $i < $identifier_count; $i++) {
      $type = $form_state->getValue(['identifiers', $i, 'identifier_type']);
      $value = $form_state->getValue(['identifiers', $i, 'identifier_value']);
      if (!empty($value)) {
        $identifiers[] = [
          'identifier_type' => $type,
          'identifier_value' => $value,
        ];
      }
    }
    $this->configuration['identifiers'] = $identifiers;
  }

}
