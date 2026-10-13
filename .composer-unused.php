<?php

declare(strict_types=1);

use ComposerUnused\ComposerUnused\Configuration\Configuration;
use ComposerUnused\ComposerUnused\Configuration\NamedFilter;

/*
 * composer-unused only sees direct PHP `use … as …` references; Fluid
 * view-helpers are wired through templates and the engine instead.
 */
return static function (Configuration $config): Configuration {
    return $config
        ->addNamedFilter(NamedFilter::fromString('typo3/cms-fluid'));
};
