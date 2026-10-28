# SmartThink

SmartThink is a multi-tenant Applicant Tracking System (ATS) for recruitment companies. One installation serves many companies. Each company's data is isolated and accessible only to its authorised members.

Status: Phase 0 (planning and documentation). No product features are implemented yet.

## Documentation index
- Product scope and roadmap: `docs/ROADMAP.md`, `docs/PHASE_STATUS.md`
- Requirements: `docs/REQUIREMENTS.md`
- Architecture decisions: `docs/ADR/`
- Risks and assumptions: `docs/RISKS_AND_DECISIONS.md`
- Changes: `docs/CHANGELOG.md`

## Repository layout (planned)
apps/api Fastify backend (modular monolith)
apps/web React + TypeScript frontend
packages/shared Shared types and validation schemas
scripts/ Setup, backup, and maintenance scripts
tests/ Cross-cutting integration, tenant-isolation, and E2E tests
infra/ Deployment and infrastructure configuration
docs/ All project documentation

## Requirements on the development machine
- Windows 11, Node.js 24 LTS, Git, PostgreSQL 18 (verified in Phase 0 audit)

## Security rules for this repository
- Never commit `.env`, passwords, tokens, private keys, real candidate documents, or database dumps.
- Use `.env.example` as the template for local configuration.
