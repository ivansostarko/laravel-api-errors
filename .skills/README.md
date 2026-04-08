# AI Agent Skills for Laravel API Errors

This directory contains skills that AI coding agents (Claude Code, Cursor, Windsurf, Cline, Copilot, etc.) can use when working with the `ivansostarko/laravel-api-errors` package.

## Available Skills

| Skill | Trigger | Description |
|---|---|---|
| **[setup](./setup/SKILL.md)** | Installing, configuring, integrating the package | Full walkthrough: install → config → exception renderer → middleware → Sentry |
| **[create-error-codes](./create-error-codes/SKILL.md)** | Adding new error codes, creating enums | Template, naming conventions, domain grouping, registration checklist |
| **[export](./export/SKILL.md)** | TypeScript, Swagger, translations | All three export commands, frontend integration patterns, CI checks |
| **[troubleshoot](./troubleshoot/SKILL.md)** | Debugging issues | Common problems with solutions: HTML instead of JSON, duplicates, missing request IDs, etc. |

## How to Use

### Claude Code / Claude Projects
Add the `.skills/` directory as a skill source. The agent will automatically consult the relevant skill based on your request.

### Other AI Agents
Point your agent's context/knowledge base at the relevant `SKILL.md` file for the task you're performing.

### Manual Reference
Each `SKILL.md` is self-contained documentation — readable by humans and machines alike.
