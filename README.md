[![PHPUnit Test](https://img.shields.io/badge/PHPUnit%20Test-passing-green?style=for-the-badge)](https://packagist.org/packages/kevinoo/panorama-whois)
[![Codacy branch grade](https://img.shields.io/codacy/grade/896b8edc3bf64e46b80943dfede4bbe2/main?style=for-the-badge)](https://app.codacy.com/gh/kevinoo/panorama-whois/dashboard)
[![Packagist Downloads](https://img.shields.io/packagist/dt/kevinoo/panorama-whois?style=for-the-badge)](https://packagist.org/packages/kevinoo/panorama-whois)
[![Packagist PHP Version](https://img.shields.io/packagist/dependency-v/kevinoo/panorama-whois/php?style=for-the-badge)](https://packagist.org/packages/kevinoo/panorama-whois) 

# PanoramaWhois
PanoramaWhois is a powerful and versatile tool for retrieving Whois data from multiple sources in a single, comprehensive lookup. This API provides users with an extensive panorama of domain or IP information, aggregated through a cascade of checks across various Whois servers.

## Features
- **Multi-Source Lookup:** Obtain Whois data from diverse servers to ensure a thorough and accurate analysis.
- **Comprehensive Information:** Access detailed information about domains or IPs, combining results for a holistic view.
- **Easy Integration:** Seamless integration into your applications or services, making it convenient for developers to harness the power of PanoramaWhois.

## Installation

### Dependencies
- [Laravel 8.0+](https://github.com/laravel/laravel)

### Installation via Composer

Require the package via Composer:
```bash
composer require kevinoo/panorama-whois
```

#### Laravel

Add database support into `database.php`:
```php
<?php

return [
    // ...
    'connections' => [
        // Required
        'panorama-whois' => [
            'driver' => 'sqlite',
            'database' => 'path to database', // ex. dirname(__DIR__) .'/vendor/kevinoo/panorama-whois/database/panorama-whois.sqlite'
        ],
        // Optional
        'panorama-whois-cache' => [ 
            'driver' => 'sqlite',
            'database' => 'path to database', // ex. dirname(__DIR__) .'/vendor/kevinoo/panorama-whois/database/cache.sqlite'
        ],
        // ...
    ]
    // ...
];
```

Publish the configuration file:
```bash
php artisan vendor:publish --provider="kevinoo\PanoramaWhois\Support\PanoramaWhoisServiceProvider"
```

Review the configuration file:
```
config/panorama-whois.php
```

## Usage

- [PanoramaWhois](#panoramawhois)
    - [Installation](#installation)
        - [Dependencies](#dependencies)
        - [Installation via Composer](#installation-via-composer)
            - [Laravel](#laravel)
    - [Usage](#usage)
        - [Request Format](#request-format)
        - [Custom provider](#custom-provider-optional)
        - [Enable OCR parser](#enable-ocr-parser)
    - [Contributing](#contributing)
    - [License](#license)

### Request Format
```php
use \kevinoo\PanoramaWhois\PanoramaWhois;
// ...
PanoramaWhois::getWhoIS( domain_name );

// Example
PanoramaWhois::getWhoIS( 'facebook.com' );
```

### Custom provider (Optional)
To add a custom provider(s), add into your `config/panorama-whois.php` file
```php
// ...
    'whois_providers' => [
        kevinoo\PanoramaWhois\Providers\WhoIsCom::class,
        kevinoo\PanoramaWhois\Providers\PhpWhoisLibrary::class,
        kevinoo\PanoramaWhois\Providers\GARRServices::class,
        
        // CustomProviderClass::class
    ],
// ...
```

### Returns
```json
{
    "last_update": "2024-03-25T08:56:12+00:00",
    "registrar": {
        "code": "3237",
        "name": "RegistrarSafe, LLC",
        "url": "https://www.registrarsafe.com",
        "phone": "+1.6503087004",
        "email": "[https://www.whois.com/eimg/7/87/787d95e27790b1a17309e4c1b1bd81e4f46ae801.png]@registrarsafe.com",
        "address": null,
        "country": null,
        "whois_server": "whois.registrarsafe.com",
        "dns_security": false
    },
    "domain": {
        "code": null,
        "ip": null,
        "name": null,
        "is_registered": true,
        "created_at": "1997-03-29T05:00:00Z",
        "updated_at": "2023-04-26T19:04:19Z",
        "expiration_date": "2032-03-30T04:00:00Z",
        "dns": [
            {
                "ip": "185.89.219.12",
                "whois_server": "whois.ripe.net",
                "code": "NE1880-RIPE",
                "name": "glb-external-dns-anycast",
                "address": "4 GRAND CANAL SQUARE, GRAND CANAL HARBOUR, DUBLIN, IRELAND",
                "country": "IE",
                "phone": null,
                "email": null,
                "abuse_email": null,
                "created_at": "2022-05-19T14:20:14Z",
                "updated_at": "2022-05-19T14:20:14Z",
                "url": "D.NS.FACEBOOK.COM"
            },
            {
                "ip": "129.134.30.12",
                "whois_server": "whois.arin.net",
                "code": "THEFA-3",
                "name": "THEFA-3",
                "address": "1601 Willow Rd.",
                "country": "GBR",
                "phone": "+1-650-543-4800",
                "email": "noc@fb.com",
                "abuse_email": "noc@fb.com",
                "created_at": "2015-05-13",
                "updated_at": "2021-12-14",
                "url": "A.NS.FACEBOOK.COM"
            },
            {
                "ip": "129.134.31.12",
                "whois_server": "whois.arin.net",
                "code": "THEFA-3",
                "name": "THEFA-3",
                "address": "1601 Willow Rd.",
                "country": "NLD",
                "phone": "+1-650-543-4800",
                "email": "domain@facebook.com",
                "abuse_email": "domain@facebook.com",
                "created_at": "2015-05-13",
                "updated_at": "2021-12-14",
                "url": "B.NS.FACEBOOK.COM"
            },
            {
                "ip": "185.89.218.12",
                "whois_server": "whois.ripe.net",
                "code": "NE1880-RIPE",
                "name": "glb-external-dns-anycast",
                "address": "4 GRAND CANAL SQUARE, GRAND CANAL HARBOUR, DUBLIN, IRELAND",
                "country": "IE",
                "phone": null,
                "email": null,
                "abuse_email": null,
                "created_at": "2022-05-19T14:20:14Z",
                "updated_at": "2022-05-19T14:20:14Z",
                "url": "C.NS.FACEBOOK.COM"
            }
        ],
        "status": [
            "clientDeleteProhibited",
            "clientTransferProhibited",
            "clientUpdateProhibited",
            "serverDeleteProhibited",
            "serverTransferProhibited",
            "serverUpdateProhibited"
        ]
    },
    "registrant": {
        "code": null,
        "name": "Domain Admin (Meta Platforms, Inc.)",
        "address": "1601 Willow Rd",
        "country": "USA",
        "phone": "+1.6505434800",
        "email": "[https://www.whois.com/eimg/c/5c/c5c95f3193f9aee74b0ff9802339cc2b024afd2e.png]@fb.com",
        "site_web": null,
        "created_at": null,
        "updated_at": null
    },
    "admin": {
        "code": null,
        "name": "Domain Admin (Meta Platforms, Inc.)",
        "phone": "+1.6505434800",
        "email": "[https://www.whois.com/eimg/c/5c/c5c95f3193f9aee74b0ff9802339cc2b024afd2e.png]@fb.com",
        "created_at": null,
        "updated_at": null,
        "address": "1601 Willow Rd, 94025, Menlo Park, CA, US",
        "country": "USA"
    },
    "technical": {
        "code": null,
        "name": "Domain Admin",
        "phone": "+1.6505434800",
        "email": "[https://www.whois.com/eimg/c/5c/c5c95f3193f9aee74b0ff9802339cc2b024afd2e.png]@fb.com",
        "created_at": null,
        "updated_at": null,
        "address": "1601 Willow Rd, 94025, Menlo Park, CA, US",
        "country": "USA"
    }
}
```

### Enable OCR parser
PanoramaWhois use `https://ocr.space/` to parse images with email. To enable this feature, add this environment key in your `.env` file:
```dotenv
PANORAMA_WHOIS_OCR_APIKEY="your API key getted from https://ocr.space/OCRAPI"
```

## Contributing
We welcome contributions! Feel free to submit bug reports, feature requests, or pull requests to help improve PanoramaWhois.

## License
This project is licensed under the [MIT License](LICENSE).
