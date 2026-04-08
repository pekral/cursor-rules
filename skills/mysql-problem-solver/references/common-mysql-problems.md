# Common MySQL Problems

The skill should actively look for these issues during analysis:

## Full table scans and missing indexes
- Full table scans on large tables
- Joins without effective indexes
- Missing foreign key side indexes

## Poor index utilization
- Filtering on low-selectivity columns without a better composite index
- Functions on indexed columns that make indexes unusable (e.g., `WHERE YEAR(created_at) = 2024`)
- Leading wildcard searches like `LIKE '%term%'`
- `OR` conditions that degrade index usage
- Redundant or overlapping indexes

## Query shape problems
- Sorting without supporting indexes
- Offset pagination on large datasets
- `SELECT *` on wide tables when fewer columns are needed
- Unbounded scans caused by missing limits or weak predicates

## Application-level problems
- N+1 patterns caused by application code
- Repeated query patterns in loops
- Missing eager loading in ORM usage
