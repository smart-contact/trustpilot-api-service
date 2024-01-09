# PHP Trustpilot API Service

[![Latest Version on Packagist](https://img.shields.io/packagist/v/smart-contact/trustpilot-api-service.svg?style=flat-square)](https://packagist.org/packages/smart-contact/trustpilot-api-service)
[![Total Downloads](https://img.shields.io/packagist/dt/smart-contact/trustpilot-api-service.svg?style=flat-square)](https://packagist.org/packages/smart-contact/trustpilot-api-service)
![GitHub Actions](https://github.com/smart-contact/trustpilot-api-service/actions/workflows/main.yml/badge.svg)

PHP Service class for trustpilot APIs.
## Installation

You can install the package via composer:

```bash
composer require smart-contact/trustpilot-api-service
```

## Usage
To use this class you have to pass this information: business unit ID, API KEY, API Secret, your username and password.

All the available methods try to follow the same name as the API documentation.
All 'GET' requests, accept a param as query params and use the same keys as documentation.
All 'POST' requests, accept 2 params, data and options(optional), same as query params, all keys are equal to the documentation.

```php
use SmartContact/TrustpilotApiService/TrustpilotApiService;

$trustpilotService = new TrustpilotApiService();

$trustpilotService->init([
  'business_unit_id' => '123456789',
  'api_key' => 'abcdefghijklmnopqrstuvwxyz',
  'api_secret' => '123456789abcdefghi',
  'username' => 'user@trustpilot.com',
  'password' => 'P4ssw0rd'
]);

//authenticate to get a valid token
$trustpilotService->authenticate(); // if you use the same instance, once you have authenticated the service will automatically refresh the token when it is expired

//get invitation templates
$data = $trustpilotService->getInvitationTemplates();

var_dump($data['templates']);

```

### Testing

```bash
composer test
```

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Credits

-   [Federico Mameli](https://github.com/smart-contact)
-   [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## PHP Package Boilerplate

This package was generated using the [PHP Package Boilerplate](https://laravelpackageboilerplate.com) by [Beyond Code](http://beyondco.de/).
