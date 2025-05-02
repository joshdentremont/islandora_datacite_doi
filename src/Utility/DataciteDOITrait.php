<?php

namespace Drupal\islandora_datacite_doi\Utility;

use Drupal\Core\Entity\EntityInterface;
use Drupal\dgi_actions\Plugin\Action\HttpActionTrait;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;

use GuzzleHttp\Psr7\Response;
use function DI\string;

/**
 * Utilities when interacting with Datacite's DOI and Metadata Service APIs.
 */
trait DataciteDOITrait {

  use HttpActionTrait;

  /**
   * Identifier entity describing the operation to be done.
   *
   * @var \Drupal\dgi_actions\Entity\IdentifierInterface
   */
  protected $identifier;

  /**
   * Current actioned Entity.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entity;

  /**
   * Constructs the auth parameters for Guzzle to connect to Datacite's API.
   *
   * @return array
   *   Authorization parameters to be passed to Guzzle.
   */
  protected function getAuthorizationParams(): array {
    return [
      $this->getIdentifier()->getServiceData()->getData()['username'],
      $this->getIdentifier()->getServiceData()->getData()['password'],
    ];
  }

  /**
   * Gets the entity being used.
   */
  public function getEntity(): EntityInterface {
    return $this->entity;
  }

  /**
   * Gets the DOI prefix.
   */
  public function getPrefix(): string {
    return $this->getIdentifier()->getServiceData()->getData()['prefix'];
  }

  /**
   * Returns the Datacite MDS API endpoint.
   *
   * @return string
   *   The URL to be used for DOI MDS requests.
   */
  protected function getUri(): string {
    $host = rtrim($this->getIdentifier()->getServiceData()->getData()['host_mds'], '/');

    // If an identifier already exists, attach it to the URI to update the metadata.
    $existing_doi = $this->getDOI();

    $url_slug = $existing_doi ? $existing_doi : $this->getPrefix();

    return "{$host}/{$url_slug}";
  }

  /**
   * Construct a URL for DOI registration.
   * @return false|string
   *   The registration URL, or FALSE if no DOI is set.
   */
  public function getDOIRegistrationUri() {
    $host = getDOIHost();

    // If an identifier already exists, attach it to the URI to update the metadata.
    $existing_doi = $this->getDOI();
    if (!empty($existing_doi)) {
      return "{$host}/{$existing_doi}";
    }
    else {
      // We can't register a non-existant DOI.
      return FALSE;
    }

  }

  private function getDOIHost() {
    return rtrim($this->getIdentifier()->getServiceData()->getData()['host_doi'], '/');
  }

  protected function buildMetadataRequest(array $data) {
    // Available resource types from Datacite
    $availableTypes = [
      "Audiovisual",
      "Award",
      "Book",
      "Book chapter",
      "Collection",
      "Computational notebook",
      "Conference paper",
      "Conference proceeding",
      "Data paper",
      "Dataset",
      "Dissertation",
      "Event",
      "Image",
      "Instrument",
      "Interactive resource",
      "Journal",
      "Journal article",
      "Model",
      "Output management plan",
      "Peer review",
      "Physical object",
      "Preprint",
      "Project",
      "Report",
      "Service",
      "Software",
      "Sound",
      "Standard",
      "Study registration",
      "Text",
      "Workflow",
      "Other"
    ];

    // Create XML for Datacite
    $body = new \SimpleXMLElement('<resource xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns="http://datacite.org/schema/kernel-4" xsi:schemaLocation="http://datacite.org/schema/kernel-4 https://schema.datacite.org/meta/kernel-4/metadata.xsd"></resource>');

    // DOI prefix
    $body->addChild('identifier', $this->getPrefix())->addAttribute('identifierType', 'DOI');

    // Creator
    $body->addChild('creators')->addChild('creator')->addChild('creatorName', $data["datacite.author"])->addAttribute('nameType', 'Personal');

    // Title
    $body->addChild('titles')->addChild('title', $data["datacite.title"]);

    // Publisher
    $publisher = $body->addChild('publisher', $data["datacite.publisher"]);

    // ROR
    if (array_key_exists("datacite.ror", $data)) {
      $publisher->addAttribute('publisherIdentifier', $data["datacite.ror"]);
      $publisher->addAttribute('publisherIdentifierScheme', 'ROR');
      $publisher->addAttribute('schemeURI', 'https://ror.org/');
    }

    // Publication Year
    // If string or EDTF is given, extract just year swapping Xs for 0s
    $years = array();
    preg_match('/\b[\dX]{4}\b/', $data["datacite.year"], $years);
    $body->addChild('publicationYear', $years[0]);

    // Resource Type
    // Set to other if not in datacite's list
    $rtypeGeneral = $data["datacite.rtypeGeneral"];
    if (!in_array($rtypeGeneral, $availableTypes))
      $rtypeGeneral = "Other";
    $body->addChild('resourceType', $data["datacite.rtype"])->addAttribute('resourceTypeGeneral', $rtypeGeneral);

    return new Request($this->getRequestType(), $this->getUri(), $this->getRequestHeaders(), $body->ASXML());
  }

  /**
   * @{@inheritdoc }
   */
  protected function getRequestParams(): array {
    return [
      'auth' => $this->getAuthorizationParams(),
    ];
  }

  /**
   * Helper that wraps the normal requests to get more verbosity for errors.
   */
  protected function doiMetadataRequest($data) {
    try {
      $request = $this->buildMetadataRequest($data);

      return $this->sendRequest($request);
    } catch (RequestException $e) {
      // Wrap the exception with a bit of extra info for verbosity's sake.
      $message = $e->getMessage();
      $response = $e->getResponse();

      throw new RequestException($message, $e->getRequest(), $response, $e);
    }
  }

  protected function registerDoiUrlRequest($doi) {
    try {
      $request = $this->buildDOIRequest($doi);

      return $this->sendRequest($request);
    } catch (RequestException $e) {
      // Wrap the exception with a bit of extra info for verbosity's sake.
      $message = $e->getMessage();
      $response = $e->getResponse();

      throw new RequestException($message, $e->getRequest(), $response, $e);
    }

  }

  /**
   * @return mixed
   */
  protected function buildDOIRequest($doi) {
    $entity_url = $this->getExternalUrl();
    $body = sprintf("doi=%s\nurl=%s\n", $doi, $entity_url);

    return new Request($this->getRequestType(), $this->getDOIHost() . '/' . $doi, $this->getDOIRequestHeaders(), $body);
  }

  /**
   * Retrieves the DOI identifier.
   *
   * @return string
   *   The existing DOI for the entity
   */
  protected function getDOI(): string {
    $existing_doi = '';
    $identifier = $this->getIdentifier();
    $field = $identifier->get('field');
    if (!empty($field) && $this->entity->hasField($field)) {
      $existing_doi = $this->entity->get($field)->getString();
    }
    return $existing_doi;
  }

}
