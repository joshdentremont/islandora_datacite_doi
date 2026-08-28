<?php

namespace Drupal\islandora_datacite_doi\Plugin\DataProfile;

use Drupal\Core\Form\FormStateInterface;
use Drupal\dgi_actions\Plugin\DataProfileBase;
use Drupal\islandora_datacite_doi\Utility\DataciteVocabularies;

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
   * Keys of the sub-fields stored for each repeatable related item entry.
   */
  const RELATED_ITEM_KEYS = [
    'relation_type',
    'related_item_type',
    'related_identifier_type',
    'identifier_value',
    'creators',
    'title',
    'publication_year',
    'volume',
    'issue',
    'number_type',
    'number',
    'first_page',
    'last_page',
    'publisher',
    'edition',
    'contributor_type',
    'contributors',
  ];

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
      'author' => NULL,
      'title' => NULL,
      'subtitle' => NULL,
      'publisher' => NULL,
      'year' => NULL,
      'rtypeGeneral' => NULL,
      'rtype' => NULL,
      'subject' => NULL,
      'hostInstitution' => NULL,
      'supervisor' => NULL,
      'contributor' => NULL,
      'dateIssued' => NULL,
      'language' => NULL,
      'identifiers' => [],
      'identical' => NULL,
      'size' => NULL,
      'format' => NULL,
      'version' => NULL,
      'rights' => NULL,
      'abstract' => NULL,
      'note' => NULL,
      'funder' => NULL,
      'relatedItems' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    // The available fields from the entity/bundle are passed through a
    // temporary value in the form state.
    $available_fields = $form_state->getTemporaryValue('available_fields');

    $form['author'] = [
      '#title' => $this->t('Author(s)'),
      '#description' => $this->t('Author(s) of the object. If author is a taxonomy term and the taxonomy has a URL field called field_orcid, that value is automatically pulled as well.'),
      '#type' => 'select',
      '#options' => $available_fields,
      '#empty_option' => $this->t('- None -'),
      '#default_value' => $this->configuration['author'],
      '#required' => TRUE,
    ];
    $form['title'] = [
      '#title' => $this->t('Title'),
      '#description' => $this->t('Title of the object being given a DOI.'),
      '#type' => 'select',
      '#options' => $available_fields,
      '#empty_option' => $this->t('- None -'),
      '#default_value' => $this->configuration['title'],
      '#required' => TRUE,
    ];
    $form['subtitle'] = [
      '#title' => $this->t('Subtitle'),
      '#description' => $this->t('Subtitle of the object being given a DOI.'),
      '#type' => 'select',
      '#options' => $available_fields,
      '#empty_option' => $this->t('- None -'),
      '#default_value' => $this->configuration['subtitle'],
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
    $form['subject'] = [
      '#title' => $this->t('Subject(s)'),
      '#description' => $this->t('Subject(s) for the resource.'),
      '#type' => 'select',
      '#options' => $available_fields,
      '#empty_option' => $this->t('- None -'),
      '#default_value' => $this->configuration['subject'],
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

    $form['identical'] = [
      '#title' => $this->t('Is Identical To DOI'),
      '#description' => $this->t('DOI field for DOI of identical object. For example, a publisher\'s DOI'),
      '#type' => 'select',
      '#options' => $available_fields,
      '#empty_option' => $this->t('- None -'),
      '#default_value' => $this->configuration['identical'],
    ];
    $form['size'] = [
      '#title' => $this->t('Size(s)'),
      '#description' => $this->t('Size(s) of the resource, e.g. "90 pages" or "1 MB".'),
      '#type' => 'select',
      '#options' => $available_fields,
      '#empty_option' => $this->t('- None -'),
      '#default_value' => $this->configuration['size'],
    ];
    $form['format'] = [
      '#title' => $this->t('Format(s)'),
      '#description' => $this->t('Technical format(s) of the resource, e.g. a MIME type like "application/pdf".'),
      '#type' => 'select',
      '#options' => $available_fields,
      '#empty_option' => $this->t('- None -'),
      '#default_value' => $this->configuration['format'],
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
    $form['note'] = [
      '#title' => $this->t('Other Description'),
      '#description' => $this->t('A description with it\'s type set to other.'),
      '#type' => 'select',
      '#options' => $available_fields,
      '#empty_option' => $this->t('- None -'),
      '#default_value' => $this->configuration['note'],
    ];
    $form['funder'] = [
      '#title' => $this->t('Funder(s)'),
      '#description' => $this->t('Paragraph field containing funder information. Each referenced paragraph should have a field_funder_name sub-field (the funder\'s name) and may have a field_funder_reference_number sub-field (the award/grant number).'),
      '#type' => 'select',
      '#options' => $available_fields,
      '#empty_option' => $this->t('- None -'),
      '#default_value' => $this->configuration['funder'],
    ];

    $relation_type_options = array_combine(DataciteVocabularies::RELATION_TYPES, DataciteVocabularies::RELATION_TYPES);
    $related_item_type_options = array_combine(DataciteVocabularies::RESOURCE_TYPES, DataciteVocabularies::RESOURCE_TYPES);
    $related_identifier_type_options = array_combine(DataciteVocabularies::IDENTIFIER_TYPES, DataciteVocabularies::IDENTIFIER_TYPES);
    $number_type_options = array_combine(DataciteVocabularies::NUMBER_TYPES, DataciteVocabularies::NUMBER_TYPES);
    $contributor_type_options = array_combine(DataciteVocabularies::CONTRIBUTOR_TYPES, DataciteVocabularies::CONTRIBUTOR_TYPES);

    $form['relatedItems'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Related Items'),
      '#prefix' => '<div id="related-items-wrapper">',
      '#suffix' => '</div>',
    ];

    $related_item_count = $form_state->get('related_item_count');
    $related_item_values = $form_state->get('related_item_values');

    if ($related_item_count === NULL || $related_item_values === NULL) {
      $saved = $this->configuration['relatedItems'] ?? [];
      $related_item_values = !empty($saved) ? $saved : [[]];
      $form_state->set('related_item_values', $related_item_values);
      $form_state->set('related_item_count', count($related_item_values));
      $related_item_count = count($related_item_values);
    }

    $form['relatedItems'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Related Items'),
      '#prefix' => '<div id="related-items-wrapper">',
      '#suffix' => '</div>',
    ];

    for ($i = 0; $i < $related_item_count; $i++) {
      $saved_value = $related_item_values[$i] ?? [];
      $form['relatedItems'][$i] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Related Item @num', ['@num' => $i + 1]),
      ];
      $form['relatedItems'][$i]['relation_type'] = [
        '#type' => 'select',
        '#title' => $this->t('Relation Type'),
        '#description' => $this->t('How the resource relates to this related item, e.g. "IsPublishedIn" for a journal, "Reviews" for a book being reviewed.'),
        '#options' => $relation_type_options,
        '#empty_option' => $this->t('- None -'),
        '#default_value' => $saved_value['relation_type'] ?? '',
      ];
      $form['relatedItems'][$i]['related_item_type'] = [
        '#type' => 'select',
        '#title' => $this->t('Related Item Type'),
        '#options' => $related_item_type_options,
        '#empty_option' => $this->t('- None -'),
        '#default_value' => $saved_value['related_item_type'] ?? '',
      ];
      $form['relatedItems'][$i]['related_identifier_type'] = [
        '#type' => 'select',
        '#title' => $this->t('Related Identifier Type'),
        '#description' => $this->t('The type of the identifier selected below, e.g. "ISSN" or "DOI".'),
        '#options' => $related_identifier_type_options,
        '#empty_option' => $this->t('- None -'),
        '#default_value' => $saved_value['related_identifier_type'] ?? '',
      ];
      $form['relatedItems'][$i]['identifier_value'] = [
        '#type' => 'select',
        '#title' => $this->t('Identifier'),
        '#options' => $available_fields,
        '#empty_option' => $this->t('- None -'),
        '#default_value' => $saved_value['identifier_value'] ?? '',
      ];
      $form['relatedItems'][$i]['creators'] = [
        '#type' => 'select',
        '#title' => $this->t('Creator(s)'),
        '#description' => $this->t('Field holding the related item\'s creator(s). If the field has multiple values, each becomes a separate creator.'),
        '#options' => $available_fields,
        '#empty_option' => $this->t('- None -'),
        '#default_value' => $saved_value['creators'] ?? '',
      ];
      $form['relatedItems'][$i]['title'] = [
        '#type' => 'select',
        '#title' => $this->t('Title'),
        '#options' => $available_fields,
        '#empty_option' => $this->t('- None -'),
        '#default_value' => $saved_value['title'] ?? '',
      ];
      $form['relatedItems'][$i]['publication_year'] = [
        '#type' => 'select',
        '#title' => $this->t('Publication Year'),
        '#options' => $available_fields,
        '#empty_option' => $this->t('- None -'),
        '#default_value' => $saved_value['publication_year'] ?? '',
      ];
      $form['relatedItems'][$i]['volume'] = [
        '#type' => 'select',
        '#title' => $this->t('Volume'),
        '#options' => $available_fields,
        '#empty_option' => $this->t('- None -'),
        '#default_value' => $saved_value['volume'] ?? '',
      ];
      $form['relatedItems'][$i]['issue'] = [
        '#type' => 'select',
        '#title' => $this->t('Issue'),
        '#options' => $available_fields,
        '#empty_option' => $this->t('- None -'),
        '#default_value' => $saved_value['issue'] ?? '',
      ];
      $form['relatedItems'][$i]['number_type'] = [
        '#type' => 'select',
        '#title' => $this->t('Number Type'),
        '#description' => $this->t('What kind of number is selected below, e.g. article or report number.'),
        '#options' => $number_type_options,
        '#empty_option' => $this->t('- None -'),
        '#default_value' => $saved_value['number_type'] ?? '',
      ];
      $form['relatedItems'][$i]['number'] = [
        '#type' => 'select',
        '#title' => $this->t('Number'),
        '#options' => $available_fields,
        '#empty_option' => $this->t('- None -'),
        '#default_value' => $saved_value['number'] ?? '',
      ];
      $form['relatedItems'][$i]['first_page'] = [
        '#type' => 'select',
        '#title' => $this->t('First Page'),
        '#options' => $available_fields,
        '#empty_option' => $this->t('- None -'),
        '#default_value' => $saved_value['first_page'] ?? '',
      ];
      $form['relatedItems'][$i]['last_page'] = [
        '#type' => 'select',
        '#title' => $this->t('Last Page'),
        '#options' => $available_fields,
        '#empty_option' => $this->t('- None -'),
        '#default_value' => $saved_value['last_page'] ?? '',
      ];
      $form['relatedItems'][$i]['publisher'] = [
        '#type' => 'select',
        '#title' => $this->t('Publisher'),
        '#options' => $available_fields,
        '#empty_option' => $this->t('- None -'),
        '#default_value' => $saved_value['publisher'] ?? '',
      ];
      $form['relatedItems'][$i]['edition'] = [
        '#type' => 'select',
        '#title' => $this->t('Edition'),
        '#options' => $available_fields,
        '#empty_option' => $this->t('- None -'),
        '#default_value' => $saved_value['edition'] ?? '',
      ];
      $form['relatedItems'][$i]['contributor_type'] = [
        '#type' => 'select',
        '#title' => $this->t('Contributor Type'),
        '#description' => $this->t('Contributor type applied to every value in the Contributor(s) field below.'),
        '#options' => $contributor_type_options,
        '#empty_option' => $this->t('- None -'),
        '#default_value' => $saved_value['contributor_type'] ?? '',
      ];
      $form['relatedItems'][$i]['contributors'] = [
        '#type' => 'select',
        '#title' => $this->t('Contributor(s)'),
        '#description' => $this->t('Field holding the related item\'s contributor(s). If the field has multiple values, each becomes a separate contributor.'),
        '#options' => $available_fields,
        '#empty_option' => $this->t('- None -'),
        '#default_value' => $saved_value['contributors'] ?? '',
      ];
      if ($related_item_count > 1) {
        $form['relatedItems'][$i]['remove_related_item'] = [
          '#type' => 'button',
          '#value' => $this->t('Remove'),
          '#name' => 'remove_related_item_' . $i,
          '#ajax' => [
            'callback' => [$this, 'addRelatedItemCallback'],
            'wrapper' => 'related-items-wrapper',
            'event' => 'click',
          ],
          '#executes_submit_callback' => TRUE,
          '#submit' => [[$this, 'removeRelatedItemSubmit']],
          '#limit_validation_errors' => [['data', 'relatedItems']],
        ];
      }
    }

    $form['relatedItems']['add_related_item'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add another related item'),
      '#submit' => [[$this, 'addRelatedItemSubmit']],
      '#ajax' => [
        'callback' => [$this, 'addRelatedItemCallback'],
        'wrapper' => 'related-items-wrapper',
      ],
      '#limit_validation_errors' => [['data', 'relatedItems']],
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
   * Extracts the related item sub-field values from a submitted form item.
   */
  private function extractRelatedItemValues(array $item): array {
    $values = [];
    foreach (self::RELATED_ITEM_KEYS as $key) {
      $values[$key] = $item[$key] ?? '';
    }
    return $values;
  }

  public function addRelatedItemSubmit(array &$form, FormStateInterface $form_state): void {
    $existing = $form_state->getValue(['data', 'relatedItems']) ?? [];
    $values = [];
    foreach ($existing as $key => $item) {
      if (is_int($key)) {
        $values[] = $this->extractRelatedItemValues($item);
      }
    }
    $values[] = [];
    $form_state->set('related_item_values', $values);
    $form_state->set('related_item_count', count($values));
    $form_state->setRebuild();
  }

  /**
   * AJAX callback for the "Add another related item" button.
   */
  public function addRelatedItemCallback(array &$form, FormStateInterface $form_state): array {
    return $form['entity_fieldset']['bundle_fieldset_container']['bundle_fieldset']['dataprofile_fieldset_container']['dataprofile_fieldset']['dataprofile_fields_fieldset_container']['fields_fieldset']['data']['relatedItems'];
  }

  /**
   * Submit handler for the "Remove" related item button.
   */
  public function removeRelatedItemSubmit(array &$form, FormStateInterface $form_state): void {
    $trigger = $form_state->getTriggeringElement();
    $index = (int) str_replace('remove_related_item_', '', $trigger['#name']);

    $existing = $form_state->getValue(['data', 'relatedItems']) ?? [];
    $values = [];
    foreach ($existing as $key => $item) {
      if (is_int($key)) {
        $values[] = $this->extractRelatedItemValues($item);
      }
    }

    unset($values[$index]);
    $values = array_values($values);

    $user_input = $form_state->getUserInput();
    unset($user_input['data']['relatedItems']);
    $form_state->setUserInput($user_input);

    $form_state->set('related_item_values', $values);
    $form_state->set('related_item_count', count($values));
    $form_state->setRebuild();
  }


  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['author'] = $form_state->getValue('author');
    $this->configuration['title'] = $form_state->getValue('title');
    $this->configuration['subtitle'] = $form_state->getValue('subtitle');
    $this->configuration['publisher'] = $form_state->getValue('publisher');
    $this->configuration['year'] = $form_state->getValue('year');
    $this->configuration['rtypeGeneral'] = $form_state->getValue('rtypeGeneral');
    $this->configuration['rtype'] = $form_state->getValue('rtype');
    $this->configuration['subject'] = $form_state->getValue('subject');
    $this->configuration['hostInstitution'] = $form_state->getValue('hostInstitution');
    $this->configuration['supervisor'] = $form_state->getValue('supervisor');
    $this->configuration['contributor'] = $form_state->getValue('contributor');
    $this->configuration['dateIssued'] = $form_state->getValue('dateIssued');
    $this->configuration['language'] = $form_state->getValue('language');

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

    $this->configuration['identical'] = $form_state->getValue('identical');
    $this->configuration['size'] = $form_state->getValue('size');
    $this->configuration['format'] = $form_state->getValue('format');
    $this->configuration['version'] = $form_state->getValue('version');
    $this->configuration['rights'] = $form_state->getValue('rights');
    $this->configuration['abstract'] = $form_state->getValue('abstract');
    $this->configuration['note'] = $form_state->getValue('note');
    $this->configuration['funder'] = $form_state->getValue('funder');

    $related_item_count = $form_state->get('related_item_count') ?? 1;
    $relatedItems = [];
    for ($i = 0; $i < $related_item_count; $i++) {
      $item = $form_state->getValue(['relatedItems', $i]) ?? [];
      $entry = $this->extractRelatedItemValues($item);
      if (!empty($entry['relation_type']) && !empty($entry['related_item_type'])) {
        $relatedItems[] = $entry;
      }
    }
    $this->configuration['relatedItems'] = $relatedItems;
  }

}
