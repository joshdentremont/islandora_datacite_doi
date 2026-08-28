<?php

namespace Drupal\islandora_datacite_doi\Plugin\Action;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\dgi_actions\Plugin\Action\HttpActionMintTrait;
use Drupal\dgi_actions\Plugin\Action\MintIdentifier;
use Drupal\dgi_actions\Utility\IdentifierUtils;
use Drupal\islandora_datacite_doi\Utility\DataciteDOITrait;
use Drupal\taxonomy\Entity\Term;
use GuzzleHttp\ClientInterface;
use http\Exception\BadMessageException;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Mints a Datacite DOI.
 *
 * @Action(
 *   id = "dgi_actions_mint_datacite_doi",
 *   label = @Translation("Mint a Datacite DOI"),
 *   type = "entity"
 * )
 */
class MintDataciteDOI extends MintIdentifier {

  use DataciteDOITrait;
  use HttpActionMintTrait;

  /**
   * Constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger.
   * @param \Drupal\dgi_actions\Utility\IdentifierUtils $utils
   *   Identifier utils.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \GuzzleHttp\ClientInterface $client
   *   The HTTP client to be used for the request.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LoggerInterface $logger, IdentifierUtils $utils, EntityTypeManagerInterface $entity_type_manager, ClientInterface $client) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $logger, $utils, $entity_type_manager);
    $this->client = $client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.channel.dgi_actions'),
      $container->get('dgi_actions.utils'),
      $container->get('entity_type.manager'),
      $container->get('http_client')
    );
  }

  /**
   * Gets the External URL of the Entity.
   *
   * @return string
   *   Entity's external URL as a string.
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   * @throws \Drupal\Core\Entity\Exception\UndefinedLinkTemplateException
   */
  public function getExternalUrl(): string {
    return $this->entity->toUrl()->setAbsolute()->toString(TRUE)->getGeneratedUrl();
  }


  /**
   * {@inheritdoc}
   */
  protected function getRequestHeaders(): array {
    return [
      'Content-Type' => 'application/xml;charset=UTF-8',
    ];
  }

  protected function getDOIRequestHeaders(): array {
    return [
      'Content-Type' => 'text/plain;charset=UTF-8',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getRequestType(): string {
    return 'PUT';
  }

  /**
   * @inheritDoc
   */
  protected function getFieldData(): array {

    $data = [];
    $data_profile = $this->getIdentifier()->getDataProfile();
    if ($data_profile) {
      foreach ($data_profile->getData() as $key => $field) {
        // Deal with identifiers being an array
        if ($key === 'datacite.identifiers') {
          if ($field[0]['identifier_type'] && $field[0]['identifier_value']) {
            $data['datacite.identifiers'] = [];
            foreach ($field as $identifier) {
              $entity_field = $this->entity->get($identifier['identifier_value']);
              if (!$entity_field->isEmpty()) {
                $data['datacite.identifiers'][$identifier['identifier_type']] = $entity_field->getValue();
              }
            }
          }
        }
        // Deal with funder being a paragraph reference.
        else if ($key === 'datacite.funder') {
          if ($this->entity->hasField($field)) {
            $entity_field = $this->entity->get($field);
            if (!$entity_field->isEmpty()) {
              $funders = [];
              foreach ($entity_field->referencedEntities() as $paragraph) {
                if (!$paragraph->hasField('field_funder_name') || $paragraph->get('field_funder_name')->isEmpty()) {
                  continue;
                }
                $funder = ['value' => $paragraph->get('field_funder_name')->getString()];
                if ($paragraph->hasField('field_funder_reference_number') && !$paragraph->get('field_funder_reference_number')->isEmpty()) {
                  $funder['award_number'] = $paragraph->get('field_funder_reference_number')->getString();
                }
                $funders[] = $funder;
              }
              if (!empty($funders)) {
                $data[$key] = $funders;
              }
            }
          }
        }
        // Deal with related identifiers being a repeatable set of
        // profile-level relation/identifier-type values plus one Drupal
        // field selection for the identifier's value.
        else if ($key === 'datacite.relatedIdentifiers') {
          $relatedIdentifiers = [];
          foreach ($field as $rid) {
            if (empty($rid['relation_type']) || empty($rid['identifier_type']) || empty($rid['identifier_value']) || !$this->entity->hasField($rid['identifier_value'])) {
              continue;
            }
            $entity_field = $this->entity->get($rid['identifier_value']);
            if ($entity_field->isEmpty()) {
              continue;
            }
            $relatedIdentifiers[] = [
              'relation_type' => $rid['relation_type'],
              'identifier_type' => $rid['identifier_type'],
              'resource_type_general' => $rid['resource_type_general'] ?? '',
              'value' => $entity_field->getString(),
            ];
          }
          if (!empty($relatedIdentifiers)) {
            $data[$key] = $relatedIdentifiers;
          }
        }
        // Deal with related items being a repeatable set of Drupal field
        // selections plus profile-level relation/type/identifier-type values.
        else if ($key === 'datacite.relatedItems') {
          $relatedItems = [];
          foreach ($field as $ri) {
            if (empty($ri['relation_type']) || empty($ri['related_item_type'])) {
              continue;
            }
            $entry = [
              'relation_type' => $ri['relation_type'],
              'related_item_type' => $ri['related_item_type'],
              'related_identifier_type' => $ri['related_identifier_type'] ?? '',
              'number_type' => $ri['number_type'] ?? '',
              'contributor_type' => $ri['contributor_type'] ?? '',
            ];
            foreach (['identifier_value', 'creators', 'title', 'publication_year', 'volume', 'issue', 'number', 'first_page', 'last_page', 'publisher', 'edition', 'contributors'] as $sub_key) {
              $field_name = $ri[$sub_key] ?? '';
              if (empty($field_name) || !$this->entity->hasField($field_name)) {
                continue;
              }
              $entity_field = $this->entity->get($field_name);
              if ($entity_field->isEmpty()) {
                continue;
              }
              if ($sub_key === 'creators' || $sub_key === 'contributors') {
                $entry[$sub_key] = array_column($entity_field->getValue(), 'value');
              }
              else {
                $entry[$sub_key] = $entity_field->getString();
              }
            }
            $relatedItems[] = $entry;
          }
          if (!empty($relatedItems)) {
            $data[$key] = $relatedItems;
          }
        }
        else if ($this->entity->hasField($field)) {
          $entity_field = $this->entity->get($field);
          if (!$entity_field->isEmpty()) {
            $data[$key] = $entity_field->getValue();
            // Add data for taxonomy terms
            foreach ($data[$key] as &$field_item) {
              if (array_key_exists('target_id', $field_item)) {
                $term = Term::load($field_item['target_id']);
                $field_item['value'] = $term->label();
                // Pull ROR and ORCID if they exist
                if ($term && $term->hasField('field_ror') && !$term->get('field_ror')->isEmpty()) {
                  $field_item['ror'] = $term->get('field_ror')->uri;
                }
                if ($term && $term->hasField('field_orcid') && !$term->get('field_orcid')->isEmpty()) {
                  $field_item['orcid'] = $term->get('field_orcid')->uri;
                }
              }
            }
          }
        }
      }
    }
    return $data;
  }


  /**
   * @inheritDoc
   */
  protected function mint(): string {
    $data = $this->getFieldData();
    $request = $this->doiMetadataRequest($data);
    if (is_null($request)) {
      return '';
    }
    $doi = $this->getIdentifierFromResponse($request);

    $entity = $this->getEntity();
    if ($entity->get('status')->getString()) {
      $this->registerDoiUrlRequest($doi);
    }
    return $doi;
  }

  /**
   * {@inheritdoc}
   */
  protected function getIdentifierFromResponse(ResponseInterface $response): string {
    $body = $response->getBody()->getContents();
    if (substr($body, 0, 4) == "OK (") {
      $doi = substr($body, 4, -1);

      $this->logger->info('Datacite DOI minted for @type/@id: @doi.', [
        '@type' => $this->getEntity()->getEntityTypeId(),
        '@id' => $this->getEntity()->id(),
        '@doi' => $doi,
      ]);
      return $doi;
    }
    throw new BadMessageException("DOI not found in response body.");
  }

}
