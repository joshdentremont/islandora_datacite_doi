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
    // Available resource types from DataCite
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

    // Check if all mandatory fields are available.
    $missing = [];

    if (!array_key_exists("datacite.title", $data) || empty($data["datacite.title"]) || empty($data["datacite.title"][0]["value"])) {
      $missing[] = "Title";
    }

    if (!array_key_exists("datacite.author", $data) || empty($data["datacite.author"]) || empty($data["datacite.author"][0]["value"])) {
      $missing[] = "Author";
    }

    if (!array_key_exists("datacite.publisher", $data) || empty($data["datacite.publisher"]) || empty($data["datacite.publisher"][0]["value"])) {
      $missing[] = "Publisher";
    }

    if (!array_key_exists("datacite.year", $data) || empty($data["datacite.year"]) || empty($data["datacite.year"][0]["value"]) || !preg_match('/\b[\dX]{4}\b/', $data["datacite.year"][0]["value"])) {
      $missing[] = "Year";
    }

    if (!array_key_exists("datacite.rtypeGeneral", $data) || empty($data["datacite.rtypeGeneral"]) || empty($data["datacite.rtypeGeneral"][0]["value"])) {
      $missing[] = "Resource Type General";
    }

    // If any of the mandatory fields are missing, log a warning and return.
    if (!empty($missing)) {
      \Drupal::logger('islandora_datacite_doi')->warning("Could not mint DOI. Missing the following mandatory fields: " . implode(', ', $missing));
      return NULL;
    }

    // Create XML for Datacite
    $body = new \SimpleXMLElement('<resource xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns="http://datacite.org/schema/kernel-4" xsi:schemaLocation="http://datacite.org/schema/kernel-4 https://schema.datacite.org/meta/kernel-4/metadata.xsd"></resource>');

    // DOI prefix
    $body->addChild('identifier', $this->getPrefix())->addAttribute('identifierType', 'DOI');

    // Creator
    $creators = $body->addChild('creators');
    foreach ($data["datacite.author"] as $auth) {
      $creator = $creators->addChild('creator');
      $creator->addChild('creatorName', $auth["value"])->addAttribute('nameType', 'Personal');
      // Add ORCID if available
      if (array_key_exists("orcid", $auth)) {
        $id = $creator->addChild('nameIdentifier', $auth["orcid"]);
        $id->addAttribute('nameIdentifierScheme', 'ORCID');
        $id->addAttribute('schemeURI', 'https://orcid.org');
      }
    }

    // Title
    $body->addChild('titles')->addChild('title', $data["datacite.title"][0]["value"]);

    // Publisher
    $publisher = $body->addChild('publisher', $data["datacite.publisher"][0]["value"]);
    // Add ROR if available
    if (array_key_exists("ror", $data["datacite.publisher"][0])) {
      $publisher->addAttribute('publisherIdentifier', $data["datacite.publisher"][0]["ror"]);
      $publisher->addAttribute('publisherIdentifierScheme', 'ROR');
      $publisher->addAttribute('schemeURI', 'https://ror.org');
    }
    // Publication Year
    // If string or EDTF is given, extract just year swapping Xs for 0s
    $years = array();
    preg_match('/\b[\dX]{4}\b/', $data["datacite.year"][0]["value"], $years);
    $body->addChild('publicationYear', $years[0]);

    // Resource Type
    // Set to other if not in datacite's list
    $rtypeGeneral = $data["datacite.rtypeGeneral"][0]["value"];
    if (!in_array($rtypeGeneral, $availableTypes)) {
      $rtypeGeneral = "Other";
    }
    $body->addChild('resourceType', $data["datacite.rtype"][0]["value"])->addAttribute('resourceTypeGeneral', $rtypeGeneral);

    // The following fields are all optional for Datacite

    // Contributors
    $contributors = $body->addChild('contributors');

    // Hosting institution
    if (array_key_exists("datacite.hostInstitution", $data)) {
      $host = $contributors->addChild('contributor');
      $host->addAttribute('contributorType', 'HostingInstitution');
      $host->addChild('contributorName', $data["datacite.hostInstitution"][0]["value"])->addAttribute('nameType', 'Organizational');
      // Add ROR if available
      if (array_key_exists("ror", $data["datacite.hostInstitution"][0])) {
        $id = $host->addChild('nameIdentifier', $data["datacite.hostInstitution"][0]["ror"]);
        $id->addAttribute('nameIdentifierScheme', 'ROR');
        $id->addAttribute('schemeURI', 'https://ror.org');
      }
    }

    // Thesis Supervisor
    if (array_key_exists("datacite.supervisor", $data)) {
      foreach ($data["datacite.supervisor"] as $super) {
        $supervisor = $contributors->addChild('contributor');
        $supervisor->addAttribute('contributorType', 'Supervisor');
        $supervisor->addChild('contributorName', $super["value"])->addAttribute('nameType', 'Personal');
        // Add ORCID if available
        if (array_key_exists("orcid", $super)) {
          $id = $supervisor->addChild('nameIdentifier', $super["orcid"]);
          $id->addAttribute('nameIdentifierScheme', 'ORCID');
          $id->addAttribute('schemeURI', 'https://orcid.org');
        }
      }
    }

    // Dates
    $dates = $body->addChild('dates');

    // Date Issued
    if (array_key_exists("datacite.dateIssued", $data)) {
      $di = str_replace('X', '0', $data["datacite.dateIssued"][0]["value"]);
      $years = array();
      if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data["datacite.year"][0]["value"])) {
        preg_match('/\b[\dX]{4}\b/', $di, $years);
        $di = $years[0];
      }

      $date = $dates->addChild('date', $di)->addAttribute('dateType', 'Issued');
      if ($di !== $data["datacite.dateIssued"][0]["value"])
        $date->addAttribute('dateInformation', $data["datacite.dateIssued"]);
    }

    // Language
    if (array_key_exists("datacite.language", $data)) {
      $body->addChild('language', $data["datacite.language"][0]["value"]);
    }

    // Rights
    if (array_key_exists("datacite.rights", $data)) {
      $body->addchild('rightsList')->addChild('rights', $data["datacite.rights"][0]["value"]);
    }

    // Descriptions
    $descriptions = $body->addChild('descriptions');

    // Abstract
    if (array_key_exists("datacite.abstract", $data)) {
      $descriptions->addchild('description', $data["datacite.abstract"][0]["value"])->addAttribute('descriptionType', 'Abstract');
    }

    // Other Description (note)
    if (array_key_exists("datacite.note", $data)) {
      $descriptions->addchild('description', $data["datacite.note"][0]["value"])->addAttribute('descriptionType', 'Other');
    }

    // Subject(s)
    if (array_key_exists("datacite.subject", $data)) {
      $subjects = $body->addChild('subjects');
      foreach ($data["datacite.subject"] as $subject) {
        $subject = $subjects->addChild('subject', $subject["value"]);
      }
    }

    // Host Journal - Title must be present for the rest to be added
    if (array_key_exists("datacite.hostname", $data)) {
      $host = $body->addChild('relatedItems')->addChild('relatedItem');
      $host->addAttribute('relatedItemType', 'Journal');
      $host->addAttribute('relationType', 'IsPublishedIn');

      // Host ISSN
      if (array_key_exists("datacite.hostissn", $data)) {
        $host->addChild('relatedItemIdentifier', $data["datacite.hostissn"][0]["value"])->addAttribute('relatedItemIdentifierType', 'ISSN');
      }

      // Host title
      $host->addChild('titles')->addChild('title', $data["datacite.hostname"][0]["value"]);

      // Host Volume
      if (array_key_exists("datacite.hostvolume", $data)) {
        $host->addChild('volume', $data["datacite.hostvolume"][0]["value"]);
      }

      // Host Issue
      if (array_key_exists("datacite.hostissue", $data)) {
        $host->addChild('issue', $data["datacite.hostissue"][0]["value"]);
      }

      // Host Start Page
      if (array_key_exists("datacite.hoststartpage", $data)) {
        $host->addChild('firstPage', $data["datacite.hoststartpage"][0]["value"]);
      }

      // Host End Page
      if (array_key_exists("datacite.hostendpage", $data)) {
        $host->addChild('lastPage', $data["datacite.hostendpage"][0]["value"]);
      }
    }

    return new Request($this->getRequestType(), $this->getUri(), $this->getRequestHeaders(), $body->asXML());
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
      if (is_null($request)) {
        return NULL;
      }

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
