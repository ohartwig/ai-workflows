# AI Workflows — TYPO3 Extension

[![TYPO3 14](https://img.shields.io/badge/TYPO3-14.x-orange.svg)](https://get.typo3.org/)
[![PHP 8.2+](https://img.shields.io/badge/PHP-8.2%2B-blue.svg)](https://php.net/)
[![License: GPL-2.0-or-later](https://img.shields.io/badge/License-GPL--2.0--or--later-green.svg)](https://www.gnu.org/licenses/gpl-2.0.html)

Agentic workflow engine for TYPO3 that orchestrates AI-powered content operations through YAML-defined pipelines. Supports multi-step workflows with expression resolution, blocking/resume capability, and extensible step/notifier architecture.

## Features

- **YAML Workflow Definitions** — Declarative pipeline configuration with steps, conditions, and expressions
- **Blocking & Resume** — Workflows can pause for human review and resume after approval
- **Expression Resolution** — Dynamic value resolution within workflow steps using an expression language
- **Extensible Step Architecture** — Register custom steps via `StepInterface` and DI auto-discovery
- **Notifier System** — Pluggable notifications for workflow state changes
- **DDD 4-Layer Architecture** — Domain, Application, Infrastructure, Presentation layers enforced by deptrac

## Installation

```bash
composer require koh/ai-workflows
```

## Architecture

```
Classes/
├── Domain/          # Value objects, enums, contracts (interfaces)
├── Application/     # Orchestration services
├── Infrastructure/  # Steps, adapters, persistence
└── Presentation/    # Controllers, event listeners
```

Layer dependencies are enforced via deptrac — Domain has zero external dependencies, Application depends only on Domain, Infrastructure and Presentation may use framework classes.

### Extension Points

1. Define an interface in `Domain/Contract/`
2. Create an implementation in `Infrastructure/`
3. Auto-discovery via `_instanceof` tags in `Services.yaml`

## Configuration

Workflows are defined as YAML files and registered through TYPO3's configuration system. Each workflow consists of ordered steps with optional conditions and expressions.

## Development

```bash
composer install
composer test                    # Unit tests
composer phpstan                 # Static analysis (level 8)
composer deptrac                 # DDD layer enforcement
vendor/bin/php-cs-fixer fix      # Code style (PER-CS3x0)
```

## Dependencies

| Package | Type | Purpose |
|---------|------|---------|
| `symfony/yaml` | Required | YAML workflow parsing |
| `koh/content-intelligence` | Optional | Content quality analysis integration |
| `koh/semantic-delivery` | Optional | Multi-channel delivery integration |
| `koh/dev` | Dev | Shared QA tooling |

## License

GPL-2.0-or-later — see [LICENSE](LICENSE) for details.
