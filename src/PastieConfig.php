<?php

declare(strict_types=1);
/**
 * Pastie configuration class
 *
 * Provides access to the Pastie configuration settings.
 *
 * Old pattern: globals $conf; $somethingDetail = $conf['something']['detail']; *
 * New pattern: $config = $injector->get(PastieConfig::class); $somethingDetail = $config->get('something.detail');
 *
 * Prefer DI over instantiating $config in your code.
 */

namespace Horde\Pastie;

use Horde\Core\Config\State;
use Horde\Injector\Attribute\Factory;

#[Factory(factory: PastieConfigFactory::class, method: 'create')]
class PastieConfig extends State {}
