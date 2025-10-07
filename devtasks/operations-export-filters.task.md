# Operations Export Filters

## Objective
Let operations staff export focused CSV and calendar snapshots that match the scope they are coordinating. The existing exports only supported a rolling week and hard-coded status filters, which meant housekeeping leads had to post-process the files before sharing them with specific teams.

## Scope
- Extend the export service so task lookups accept explicit status, resource kind and team constraints alongside a configurable date window.
- Surface filter controls (date range, statuses, resource kinds, configured teams) above the Operations → Tasks list with quick actions for CSV and ICS export.
- Include filter context in the generated filenames and metadata (CSV banner row, ICS properties) so downstream recipients know what slice of work they received.
- Cover representative filter combinations with PHPUnit to keep query builders and metadata helpers from regressing.

## Acceptance Criteria
- [ ] Form defaults should match the previous behaviour (today → +7 days, pending + in-progress statuses, all resources/teams) until the user narrows them.
- [ ] Selecting multiple resource kinds or teams yields only matching tasks in both CSV and ICS downloads.
- [ ] Filenames include a compact slug indicating the date window and any explicit status/resource/team filters.
- [ ] CSV exports begin with a filter summary row and ICS exports include a matching `X-QLO-FILTERS` line per event.
- [ ] PHPUnit coverage exercises at least one multi-filter combination and checks the generated metadata strings.

## Dependencies & Notes
- Team options come from the JSON/YAML-ish `KLOPERATIONS_TEAMS` configuration. Reuse the existing parser so the filter select mirrors assignment choices.
- Resource kind values live in `kl_operation_task.resource_type`. Include a "General" option for manual tasks without a linked resource.
- Respect timezone configuration when building default date windows and descriptions so the exported window matches what the console shows.

