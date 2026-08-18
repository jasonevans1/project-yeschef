# Task 004: Bookmarklet link on the paste-import page

**Status**: pending
**Depends on**: 002
**Retry count**: 0

## Description
Render a draggable bookmarklet on the paste-import page that, when clicked from any recipe page in the user's browser, copies that page's full rendered HTML to the clipboard and opens this app's paste-import page in a new tab — so the user only has to paste (Cmd/Ctrl+V) and submit. No extraction logic runs client-side; the existing server-side `MicrodataParser` (via `parseHtml()`) does all the JSON-LD finding, same as the URL path.

## Context
- Files to modify: `app/Livewire/Recipes/ImportPaste.php` and `resources/views/livewire/recipes/import-paste.blade.php` (both built in Task 002).
- The bookmarklet is a plain `javascript:` URI, not a browser extension — no manifest, no packaging, no build step. The destination comes from `route('recipes.import.paste', absolute: true)` so it is correct per environment (local DDEV vs. production) without hardcoding.
- **Build the URI string in `ImportPaste::render()`** and pass it to the view (e.g. `view(..., ['bookmarklet' => $this->bookmarkletHref()])`); echo it with `{{ $bookmarklet }}`. Do not assemble the JS inline in Blade: Blade's echo compiler runs over inline HTML (including `@php` block bodies), so any `{{` appearing in minified JS becomes a Blade expression. Escaped output is correct here — the browser HTML-decodes attribute values (`&#039;` → `'`) before parsing the URI.
- Keep the JS on a single line, with **no `%` and no `#` characters** (both change `javascript:` URI semantics and would otherwise need percent-encoding).
- JS logic:
  ```js
  javascript:(function(){
    var html = document.documentElement.outerHTML;
    var dest = 'ABSOLUTE_PASTE_ROUTE_URL';
    function openDest(){ var w = window.open(dest, '_blank'); if (!w) { location.href = dest; } }
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(html).then(openDest, function(){
        alert('Could not copy automatically - view source and copy the page HTML manually.');
        openDest();
      });
    } else {
      alert('Clipboard access is unavailable - view source and copy the page HTML manually.');
      openDest();
    }
  })();
  ```
- **Why `openDest` is written that way** (the ordering note in an earlier draft of this task was wrong — string building was never the constraint): the real constraint is transient user activation. `window.open()` from inside a promise `.then()` is blocked by Firefox and by Chrome once activation expires. Opening synchronously *before* the clipboard write is not a fix either, because `navigator.clipboard.writeText()` then rejects with `NotAllowedError: Document is not focused`. So: keep `window.open` in the `.then()` (clipboard resolves within milliseconds, inside the activation window) and fall back to `location.href = dest` when the popup is blocked — the clipboard already holds the HTML at that point, so a same-tab navigation loses nothing.
- Render this as an `<a href="javascript:...">Drag to your bookmarks bar</a>` link, with a short usage blurb above it: (1) drag the link to your bookmarks bar, (2) open it while viewing a recipe page, (3) it copies the page HTML and opens this paste page, (4) paste and submit. Include a static manual fallback line — "if the bookmarklet doesn't run on a site, use View Source, select all, and copy" — because Firefox blocks `javascript:` bookmarklets outright on pages with a restrictive CSP, and in that case the in-script `alert()` never fires.
- Testing scope: only the *href generation* is automated (this is server-rendered Blade output, testable like any other view assertion). The bookmarklet's actual runtime behavior executes on arbitrary third-party origins outside this app's control and cannot be exercised by a Pest feature or browser test — note this as a manual verification step, not a gap to fill with more automation.

- Tests go in the existing `tests/Feature/Recipe/ImportPasteTest.php` (created in Task 002).

## Requirements (Test Descriptions)
- [ ] `it renders a bookmarklet link on the paste html import page`
- [ ] `it generates a bookmarklet href starting with javascript: and containing the absolute paste import url`
- [ ] `it displays usage instructions for the bookmarklet`
- [ ] `it displays a manual copy fallback instruction for sites where the bookmarklet cannot run`

## Acceptance Criteria
- All requirements have passing tests.
- Bookmarklet href is built via `route(..., absolute: true)` in `ImportPaste::render()`, not hardcoded and not assembled inline in Blade.
- Rendered href contains no `%` or `#` characters and no literal `{{`.
- Manual verification (not automatable — the JS runs on third-party origins): drag the link to the bookmarks bar, click it on a real recipe page, confirm the HTML lands on the clipboard and the paste page opens.
- Code follows code standards.

## Implementation Notes
(Left blank - filled in by programmer during implementation)
