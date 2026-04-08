# I/O Bottleneck Checklist

Apply this checklist when changes touch file, storage, or external I/O.

## Checklist items

1. **File reads/writes on large files** must use PHP streams (`fopen`/`fread` in chunks) or Laravel `Storage` streaming methods.
2. **Storage uploads triggered during HTTP requests** must be deferred to a queued job unless the file is small (< 1 MB) and the response depends on the result.
3. **Blocking HTTP calls** must have explicit timeouts; consider async via queued jobs for non-critical paths.
4. **File downloads** must stream content with `StreamedResponse` or `Storage::download()` — never load the full file into memory.
5. **CSV/Excel exports** must use chunked queries (`chunk()` or `cursor()`) and stream output row by row.
6. **Image or media processing** (resize, compress, convert) must be offloaded to a background job.

## Trigger signals

Flag when any of these patterns appear:
- Synchronous file reads/writes (`file_get_contents`, `fread`, `file_put_contents`) on large or unbounded files
- Blocking HTTP calls without timeouts
- Storage operations (`Storage::put`, `Storage::get`, S3 uploads/downloads) executed in the request lifecycle
- Large file responses not using `StreamedResponse` or `Storage::download()`
- Export/import operations loading all records into memory
