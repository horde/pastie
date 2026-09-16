<?php

declare(strict_types=1);
/**
 * Pastie configuration class factory
 *
 * Creates instances of the PastieConfig class.
 *
 * Old pattern: globals $conf; $somethingDetail = $conf['something']['detail']; *
 * New pattern: $config = $injector->get(PastieConfig::class); $somethingDetail = $config->get('something.detail');
 *
 * Prefer DI over instantiating $config in your code.
 */

namespace Horde\Pastie;

use Horde\Core\Config\ConfigLoader;
use Horde\Injector\Injector;

class PastieConfigFactory
{
    public function __construct(private Injector $injector) {}

    public function create(): PastieConfig
    {
        $state = $this->injector->get(ConfigLoader::class)->load('pastie');
        return new PastieConfig($state->toArray());
    }
}
