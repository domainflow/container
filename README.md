[![Tests](https://github.com/domainflow/container/actions/workflows/tests.yml/badge.svg)](https://github.com/domainflow/container/actions/workflows/tests.yml)
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

## Retained state and scopes

Rebinding an identifier always discards a previously retained shared value for
that identifier. `instance()` replaces the retained value directly, including
when that value is `null`; a shared factory that returns `null` is still invoked
only once. `has()` reports explicit bindings, instances, aliases, and existing
classes that the container can instantiate through autowiring. Consequently,
an identifier rejected by `has()` is also rejected by PSR-11 `get()` with a
`NotFoundException`.

`scope()` retains a named child container. Use `resetScope($name)` at a
lifecycle boundary to discard that scope's retained values while preserving its
local bindings and configuration. Use `disposeScope($name)` when the scope and
its configuration must be removed completely. `resetContainer()` clears all
registrations, retained values, aliases, scopes, hooks, tags, contextual and
union-type configuration, reflection metadata, and external cached definitions.
A scope resolves its own aliases before consulting its parent, so a local alias
shadows a parent alias with the same identifier. Scoped instances and shared
bindings retain `null` with the same semantics as the root container.

## Callable invocation and resolution hooks

`call()` accepts PHP callables and injects their parameters. Public static
methods may be passed as `[Utility::class, 'method']` or
`Utility::class . '::method'`; neither form constructs the declaring class.
Closures, function names, invokable objects, and object-method callables retain
their normal PHP semantics.

The resolution stack is established before before-resolve hooks run. Recursive
resolution from a before-hook therefore raises the same controlled
`ContainerException` as a constructor cycle, and failures always clear the
transient resolution state. After-resolve hooks receive the resolved `mixed`
value without an implicit object cast. Returning a non-`null` value replaces the
resolution result; returning `null` keeps the current result.

Properties marked with `#[Inject]` must be non-readonly properties with one
non-built-in named type. Untyped, built-in, union, intersection, and readonly
properties fail with `ContainerException` identifying the property.

## Resolution definition cache

`ContainerCacheInterface` can persist declarative class bindings and aliases so
a new container can restore them before calling `make()` or `get()`. This
declarative-definition path never stores resolved instances, closures, factory
results, or serialized values.
Only bindings registered with a concrete class string are cacheable; closure
bindings and `instance()` registrations remain local to the current container.

Attach a cache to an empty container with `setExternalCache()`. Binding or alias
changes replace the stored definition set. `clearResolutionCache()` deletes only
the external definitions, while `resetContainer()` clears both the local
container state and its stored definitions. Cache adapters are trusted
configuration stores: protect them from unauthorized writes, just as you would
the application's binding configuration.

### Legacy resolved-service cache API

`cacheResolvedService()`, `cacheResolvedServices()`,
`clearResolvedServicesCache()`, and `loadResolvedServicesFromExternalCache()`
remain available for backwards compatibility but are deprecated since 0.2.0.
They may store arbitrary resolved values and therefore are not part of the safe
declarative-definition cache contract. Migrate process-local reuse to shared
bindings and persist application data through the application's own cache API.

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
