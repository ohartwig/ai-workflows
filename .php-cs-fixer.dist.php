<?php

// SPDX-FileCopyrightText: 2026 Moselwal Digitalagentur GmbH
// SPDX-License-Identifier: MIT

declare(strict_types=1);

// Delegiert vollständig an die zentrale koh/dev-Konvention
// (@Symfony + @PER-CS3x0 + @PHP85Migration + @DoctrineAnnotation).
// Die koh/dev-Konfig liest KOH_FRAMEWORK aus der Umgebung.
\putenv('KOH_FRAMEWORK=typo3');
$_ENV['KOH_FRAMEWORK'] = 'typo3';

/** @var PhpCsFixer\Config $config */
$config = require __DIR__ . '/vendor/koh/dev/.php-cs-fixer.dist.php';

// `new_expression_parentheses` (part of @PER-CS3x0/@PHP85Migration) drops
// the parentheses around a `new` expression when it is immediately chained,
// e.g. `(new Foo())->bar()` -> `new Foo()->bar()`. That is valid PHP 8.4+
// and matches this project's target version, but test:deptrac still runs
// on deptrac/deptrac 3.0.0, which bundles nikic/php-parser v4.19.1 — a
// parser version that predates PHP 8.4 and cannot parse the
// parenthesis-free form at all (hard parse error, not a lint warning).
//
// This is a temporary measure with a known end date, and the condition is
// not "once deptrac supports PHP 8.4" — it already does. deptrac 4.7.1
// requires nikic/php-parser ^5 and parses this fine. test-tools pins that
// version on main, but the pin has not been released yet: the newest
// release, 1.16.8, still carries the old one. So the blocker is our own
// release chain, not the upstream project.
//
// Remove this override once test-tools has released the 4.7.1 pin and the
// composed pin in this repository has followed. Leaving it in place after
// that would quietly hold this repository to an older dialect than the rest.
$config->setRules([
    ...$config->getRules(),
    'new_expression_parentheses' => false,
]);

return $config;
