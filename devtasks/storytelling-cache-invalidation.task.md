# Storytelling availability cache invalidation

## Summary
- Added a cache invalidation helper so storytelling availability snapshots can be purged on demand.
- Hooked booking lifecycle, inquiry submission, and room disable flows to invalidate stale storytelling availability caches.
- Covered cache rebuild behaviour with unit tests to ensure new snapshots are generated after invalidation.
- Documented cron/configuration considerations and updated high-level docs with cache invalidation notes.

## Testing
- `cd tests && ./vendor/bin/phpunit --testsuite Unit`
