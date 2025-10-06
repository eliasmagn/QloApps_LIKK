# Storytelling presenter test hardening

## Summary
- Add coverage around `HotelReservationSystemStorytellingPresenter` to lock down section grouping, capacity messaging, package payload construction and availability snapshot caching.
- Introduce Panther-powered UI smoke tests that exercise the residencies storytelling template and assert Lighthouse-aligned navigation timing thresholds.
- Wire the new suites into PHPUnit so CI runs both the legacy unit tests and the new storytelling-specific checks.

## Testing
- `cd tests && ./vendor/bin/phpunit`
