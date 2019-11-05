<h2 align="center">Automatic Narrowspark Framework Configurators</h2>
<p align="center">
    <a href="https://github.com/narrowspark/configurators/releases"><img src="https://img.shields.io/packagist/v/narrowspark/configurators.svg?style=flat-square"></a>
    <a href="https://php.net/"><img src="https://img.shields.io/badge/php-%5E7.2.0-8892BF.svg?style=flat-square"></a>
    <a href="https://travis-ci.org/narrowspark/configurators"><img src="https://img.shields.io/travis/rust-lang/rust/master.svg?style=flat-square"></a>
    <a href="https://codecov.io/gh/narrowspark/configurators"><img src="https://img.shields.io/codecov/c/github/narrowspark/configurators/master.svg?style=flat-square"></a>
    <a href="http://opensource.org/licenses/MIT"><img src="https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square"></a>
</p>

Installation
------------

```bash
composer require narrowspark/automatic narrowspark/configurators
```

Configurators
-------------
There are several types of tasks, which are called **configurators**:
`options`, `bootstrap` and `providers`.

`providers` Configurator

Enables one or more service provider in the Narrowspark application by appending them to the `serviceproviders.php` file.
Its value is an associative array where the key is the service provider class name and the value is an array of environments where it must be enabled.
The supported environments are `local`, `prod`, `testing` and `global` (which enables the `service provider` in all environments):

```json
{   
    "extra": {
        "automatic": {
            "providers": {
                "Viserio\\Component\\Routing\\Provider\\RoutingServiceProvider": [
                    "global"
                ],
                "Viserio\\Component\\Routing\\Provider\\RoutingDataCollectorServiceProvider": [
                    "testing"
                ]
            }
        }
    }
}
```

The previous operation is transformed into the following PHP code:

```php
// config/serviceproviders.php
return [
    /** > viserio/routing **/
    \Viserio\Component\Routing\Provider\RoutingServiceProvider::class,
    /** viserio/routing < **/
];

// config/testing/serviceproviders.php
return [
    /** > viserio/routing **/
    \Viserio\Component\Routing\Provider\RoutingDataCollectorServiceProvider::class,
    /** viserio/routing < **/
];
```

`options` Configurator

Adds new config files to the `config` folder provided from your root composer.json `config-dir` name.

> NOTE: The package name is taken to generate the file name.

This example creates a new `view` config file in the `packages` folder and `packages/test` folder:

> NOTE: The first array key is taken as environment key, like `global` or `test` in this example.

```json
{   
    "extra": {
        "automatic": {
            "options": {
                "global": {
                    "viserio": {
                        "view": {
                            "paths": null
                        }
                    }
                },
                "test": {
                    "viserio": {
                        "view": {
                            "paths": [
                                "./views/"
                            ]
                        }
                    }
                }
            }
        }
    }
}
```

`bootstrap` Configurator

This example creates new `bootstrap` configs for the `console` and `http` kernel:
You can choose between `http`, `console` and `global` type to configure your kernel bootstraps, 
with the possibility to configure bootstraps for your chosen environment.

> NOTE: Generates a new `bootstrap.php` file to the `config` folder provided from your root composer.json `config-dir` name, if the file doesn't exists.

> NOTE: The `global` type will configure both kernels.

```json
{   
    "extra": {
        "automatic": {
            "bootstrap": {
                "Viserio\\Component\\Foundation\\Bootstrap\\LoadEnvironmentVariables": ["http"]
            }
        }
    }
}
```

Contributing
------------

If you would like to help take a look at the [list of issues](http://github.com/narrowspark/configurators/issues) and check our [Contributing](CONTRIBUTING.md) guild.

> **Note:** Please note that this project is released with a Contributor Code of Conduct. By participating in this project you agree to abide by its terms.

License
---------------

The Narrowspark configurators is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT)

[1]: http://github.com/jshttp/mime-db
