# Example: Blocked by Missing Infrastructure

## PR #315 — feat(import): add XML parser for legacy format

### Review Recommendations

| # | Recommendation | Status |
|---|---|---|
| 1 | Test parsing of valid legacy XML document | Added |
| 2 | Test handling of malformed XML input | Added |
| 3 | Test integration with import pipeline | Blocked |

### Tests Added or Updated

- **tests/Import/XmlParserTest.php** (new)
  - Added `test_parses_valid_legacy_xml_document`
  - Added `test_rejects_malformed_xml_with_clear_error`

### Coverage

85% coverage for current changes. Integration path not covered.

### Blockers

- Integration test for the import pipeline requires a running queue worker, which is not available in the current test environment. A test container or mock queue adapter is needed before this scenario can be covered.
