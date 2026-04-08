# Attachment Handling

## Detection

- Scan the PR body, comments, and linked issues for attachment references
- Look for image links, file uploads, and external document references
- Check for inline screenshots embedded in GitHub markdown

## Retrieval

- Download attachments using CLI tools or MCP — never use a web browser
- If a direct download fails, try alternative retrieval methods (gh api, curl)
- Log which attachments were successfully retrieved and which failed

## Analysis

- Examine each attachment for information relevant to the JIRA issue
- For images/screenshots: describe the visual content and its implications
- For documents: extract key requirements or specifications

## Inclusion in Output

- Summarize attachment findings in the "Technický kontext z PR" section
- If an attachment reveals additional requirements, add them to "Požadavky pro implementaci"
- If an attachment cannot be retrieved, add a warning note in "Poznámky"
