<?php

declare(strict_types=1);

namespace DomainFlow\Container\Attribute;

use Attribute;

/**
 *  Attribute to signal that a property should be injected.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final readonly class Inject
{
}
