# ADR-0005: PDF renderer for CV export

Status: Proposed. Decision in Phase 4, using fixture CVs as evidence.
Date: 2026-10-29

## Question
Which library produces print-ready A4 and Letter CV PDFs with page-break control, from HTML and CSS templates, on Windows and Linux?

## Candidates
| Option | Strength | Weakness |
|---|---|---|
| dompdf (PHP) | Pure PHP, no browser needed, simple install | Supports only a subset of CSS; layout may differ from a browser |
| Headless Chromium via Browsershot (Node + Chrome) | Accurate CSS and print layout | Needs Chrome and Node at runtime; heavier to deploy and to secure |
| Other PHP PDF libraries | Varies | Must be evaluated with the same fixtures |

## Criteria
1. Passes the Phase 4 fixture set: long text, missing fields, special characters, 1-page and 3-page CVs, A4 and Letter.
2. No heading left alone at a page bottom (automated check on extracted text positions).
3. Installs and runs on the owner's Windows PC and on the Linux server.
4. Licence allows the planned use. Confirm on the project's licence file before adoption.
5. Maintenance: recent releases and an active issue tracker, checked on the release date.
6. Memory and time per PDF are recorded for one 3-page CV.

## Decision rule
The option that passes all fixtures with the fewest deployment dependencies is chosen. If none passes, the CV template design is simplified to the CSS subset that passes, and the ADR records the limit.