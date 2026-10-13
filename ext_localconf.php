<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Cache\Backend\SimpleFileBackend;
use TYPO3\CMS\Core\Cache\Frontend\VariableFrontend;

defined('TYPO3') or exit;

$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['aiworkflows'] ??= [];
$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['aiworkflows']['frontend'] ??= VariableFrontend::class;
$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['aiworkflows']['backend'] ??= SimpleFileBackend::class;
