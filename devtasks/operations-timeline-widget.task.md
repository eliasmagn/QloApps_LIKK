# Operations timeline widget

## Summary
- Surface an operations summary widget on the booking timeline that highlights overdue, today and tomorrow workloads by resource kind.
- Provide a presenter/service in `kloperations` that aggregates pending and in-progress tasks into the summary buckets using the shop timezone.
- Link the summary to the Operations console so staff can jump straight to filtered task lists per resource.
- Cover the widget with acceptance tests to guard the integration when the module is enabled.

## Testing
- `cd tests && ./vendor/bin/phpunit --filter OperationsTimelineWidgetPantherTest`
