## Introduction

Islandora DataCite DOI is a set of plugins for the DGI Actions module to support
minting of DataCite DOI identifiers via Drupal Context Actions.

## Requirements

This module requires the following modules/libraries:

* [DGI Actions](https://github.com/discoverygarden/dgi_actions)

## Installation

Install as you would any other Drupal module.

## Configuration

DGI Actions uses seveeral types of configuration entities that need to be set up. See the
README.md file in that module for step-by-step instructions.

This module provides the DataCite DOI Service Data Type entity type. You will need to create an
instance of this entity with your Datacite API credentials and DOI prefix. See the
[Datacite API guide](https://support.datacite.org/docs/mds-api-guide) for more info.

## Usage

When DOIs are minted, they are set to "Findable" in DataCite, which makes them available in the DataCite search.

### Example Action

![image](https://github.com/user-attachments/assets/cb612230-af00-4d59-afe3-afd2009f4191)

### Example Context

![image](https://github.com/user-attachments/assets/32d74029-b3c0-48a3-a19a-c9e93d1f6481)

![image](https://github.com/user-attachments/assets/03d81c1a-04a5-4873-a65a-7547ee690cb8)

![image](https://github.com/user-attachments/assets/1f0abc64-a2eb-4bee-9f13-88b5b282e25d)

![image](https://github.com/user-attachments/assets/6a788882-7f64-4049-aab3-cff001ae3165)

### Example Data Profile

![image](https://github.com/user-attachments/assets/6631b9ce-045b-4115-8301-830ad4edaef5)

### Example Identifier

![image](https://github.com/user-attachments/assets/04830449-c8f4-4cc0-a5ec-c244ef47f692)

### Example Service Data

![image](https://github.com/user-attachments/assets/6792d9c6-7459-4514-b28f-725a07ef8c91)

## Troubleshooting/Issues


## Maintainers/Sponsors

[Robertson Library UPEI](https://github.com/roblib)

## Development

If you would like to contribute to this module, please check out our helpful [Documentation for Developers](https://github.com/Islandora/islandora/wiki#wiki-documentation-for-developers) info, as well as our [Developers](http://islandora.ca/developers) section on the Islandora.ca site.

## License

GPL version 3. See LICENSE.txt
