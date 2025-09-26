# Known Issues

This document tracks technical gaps and regressions identified during code review. All previously logged items were resolved in the current maintenance pass:

- Composer now advertises the supported PHP 8.1–8.4 window so dependency installs succeed on our documented runtimes.
- The inquiry quote preview endpoint now sends pricing payloads that match `KLQuotePricingEngine::generateQuote()`, including the submitted occupancy counts.
- `.venv/` is ignored at the repository root so running `start_dev.sh` no longer leaves a dirty working tree.

We will append fresh issues here as they arise.
