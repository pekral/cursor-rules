# Analysis Dimensions

## Repository Context Areas

When analyzing the repository, cover all of the following areas to ensure a comprehensive understanding before ideation:

| Area | What to look for |
|---|---|
| **Architecture** | Module structure, coupling, cohesion, dependency graph |
| **Tests** | Coverage gaps, flaky tests, missing integration tests |
| **Developer experience (DX)** | Build times, onboarding friction, tooling gaps, documentation quality |
| **Reliability** | Error handling, retry logic, graceful degradation, observability |
| **Performance** | Bottlenecks, N+1 queries, unnecessary computations, caching opportunities |
| **Security** | Input validation, authentication/authorization gaps, dependency vulnerabilities |
| **Delivery speed** | CI/CD pipeline efficiency, deployment friction, feature flag support |

## Ideation Prompts

Use these internal prompts to generate candidates:

- **General:** "What is the single smartest and most radically innovative and accretive and useful and compelling addition you could make to the project at this point?"
- **Technical:** "What is the single smartest and most radically innovative and accretive and useful and compelling technical code change/addition you could make to the project at this point?"

Pick the prompt variant that best matches the user's intent. Use the general variant by default; use the technical variant when the user explicitly asks for a code-level recommendation.
