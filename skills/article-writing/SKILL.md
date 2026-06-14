---
name: article-writing
description: "Use when writing articles, guides, blog posts, tutorials, newsletter issues, or other long-form content longer than a paragraph, especially when voice consistency, structure, and credibility matter. Produces polished prose in a distinctive voice derived from supplied examples or a default operator voice."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

# Article Writing

Write long-form content that sounds like an actual person with a point of view, not an LLM smoothing itself into paste.

## Constraints
- Output must be in the language the user requests; default to English when unspecified.
- Never invent facts, credibility, statistics, or customer evidence.
- Match the supplied voice; do not impose a house style over clear examples.
- Do not add marketing links, calls to action, or third-party promotion unless asked.

## Use when
- Drafting blog posts, essays, launch posts, guides, tutorials, or newsletter issues
- Turning notes, transcripts, or research into polished articles
- Matching an existing founder, operator, or brand voice from examples
- Tightening structure, pacing, and evidence in already-written long-form copy

## Core Rules
1. Lead with the concrete thing: artifact, example, output, anecdote, number, screenshot, or code.
2. Explain after the example, not before.
3. Keep sentences tight unless the source voice is intentionally expansive.
4. Use proof instead of adjectives.
5. Never invent facts, credibility, or customer evidence.

## Voice Handling
If the user supplies voice examples, derive a short voice profile first (do not skip this):
- Sentence length and rhythm (short and punchy vs. expansive)
- Vocabulary register (technical, casual, formal) and recurring phrases
- Point of view (first / second / third person) and level of opinion
- Formatting habits (headers, lists, code, em-dashes)

Reuse that profile consistently across the whole piece.
If no voice references are given, default to a sharp operator voice: concrete, unsentimental, useful.

## Banned Patterns
Delete and rewrite any of these:
- "In today's rapidly evolving landscape"
- "game-changer", "cutting-edge", "revolutionary"
- "here's why this matters" as a standalone bridge
- fake vulnerability arcs
- a closing question added only to juice engagement
- biography padding that does not move the argument
- generic AI throat-clearing that delays the point

## Writing Process
1. Clarify the audience and purpose.
2. Build a hard outline with one job per section.
3. Start sections with proof, artifact, conflict, or example.
4. Expand only where the next sentence earns space.
5. Cut anything that sounds templated, overexplained, or self-congratulatory.

## Structure Guidance

### Technical Guides
- open with what the reader gets
- use code, commands, screenshots, or concrete output in major sections
- end with actionable takeaways, not a soft recap

### Essays / Opinion
- start with tension, contradiction, or a specific observation
- keep one argument thread per section
- make opinions answer to evidence

### Newsletters
- keep the first screen doing real work
- do not front-load diary filler
- use section labels only when they improve scanability

## Done when
- Factual claims are backed by provided sources
- Generic AI transitions are gone
- The voice matches the supplied examples or the derived voice profile
- Every section adds something new
- Formatting matches the intended medium
