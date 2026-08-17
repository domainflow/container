[![Tests](https://github.com/domainflow/container/actions/workflows/tests.yml/badge.svg)](https://github.com/domainflow/uuid/actions/workflows/tests.yml)
![Packagist Version](https://img.shields.io/packagist/v/domainflow/container)
![PHP Version](https://img.shields.io/packagist/php-v/domainflow/container)
![License](https://img.shields.io/github/license/domainflow/container)
![PHPStan](https://img.shields.io/badge/PHPStan-Level%209-brightgreen.svg)

# DomainFlow Dependency Injection Container

The **DomainFlow Container** is a modular and extensible **Dependency Injection (DI) Container** built for modern PHP back-end applications and microservices. It provides a full suite of features to register, resolve, and manage dependencies with ease.

---

## ✨ Core Functionality

- **Dependency Injection (DI):** Register and resolve dependencies automatically.
- **Singleton & Shared Instances:** Bind and reuse single instances across your application.
- **Lazy Loading & Autowiring:** Automatically resolve class dependencies only when needed.
- **Service Binding:** Bind concrete implementations to interfaces or abstractions.
- **PSR-11 Compliance:** Fully implements [PSR-11](https://www.php-fig.org/psr/psr-11/) for broad interoperability.

## Resolution errors

Circular constructor dependencies are not supported. Resolving one throws a
`DomainFlow\\Container\\Exception\\ContainerException` with the dependency chain,
for example `A -> B -> A`. Use an explicit factory binding to defer work where a
lazy relationship is genuinely required.

## Resolution definition cache

`ContainerCacheInterface` can persist declarative class bindings and aliases so
a new container can restore them before calling `make()` or `get()`. It never
stores resolved instances, closures, factory results, or serialized values.
Only bindings registered with a concrete class string are cacheable; closure
bindings and `instance()` registrations remain local to the current container.

Attach a cache to an empty container with `setExternalCache()`. Binding or alias
changes replace the stored definition set. `clearResolutionCache()` deletes only
the external definitions, while `resetContainer()` clears both the local
container state and its stored definitions. Cache adapters are trusted
configuration stores: protect them from unauthorized writes, just as you would
the application's binding configuration.

---

## ⚙️ Requirements

- **PHP 8.4 or 8.5**

---

## 📦 Installation

Use Composer to install the package:

```sh
composer require domainflow/container
```

---

More details and usage examples can be found in our [documentation](https://www.domainflow.dev/docs/container)

---


## 📄 License

The **DomainFlow Container** is open-sourced software licensed under the [MIT license](https://opensource.org/license/MIT).
