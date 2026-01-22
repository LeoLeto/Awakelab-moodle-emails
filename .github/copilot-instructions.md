# Moodle Plugin Development Instructions

## Version Management - CRITICAL RULE

**Every time you make ANY change to this plugin, you MUST update BOTH the version number AND the release in `version.php`.**

### When to Update Version and Release:
- Bug fixes
- New features
- Language string additions or modifications
- Code refactoring
- Configuration changes
- Database schema changes
- ANY code modification

### Version Format:
```
YYYYMMDDXX
```
- `YYYY` = Year (4 digits)
- `MM` = Month (2 digits)
- `DD` = Day (2 digits)
- `XX` = Incremental counter for same-day releases (00-99)

### Release Format:
Use semantic versioning: `MAJOR.MINOR.PATCH`
- `MAJOR` = Breaking changes or major new features
- `MINOR` = New features, backwards compatible
- `PATCH` = Bug fixes and small improvements

### How to Update:
1. Open `version.php`
2. Update `$plugin->version` to current date + increment
   - Example: First change on 2026-01-19: `2026011900`
   - Second change same day: `2026011901`
3. Update `$plugin->release` following semantic versioning
   - Bug fix: `1.7.0` → `1.7.1`
   - New feature: `1.7.0` → `1.8.0`
   - Breaking change: `1.7.0` → `2.0.0`

### Why:
- Moodle requires version increments to trigger upgrades
- Release numbers help users understand the nature of changes
- Without updates, changes won't be applied to the database
- Prevents caching issues and ensures proper plugin updates

## Language Strings

When adding or modifying features, ensure all language strings are added to ALL language files:
- `lang/en/local_courseprogressnotify.php` (English - required)
- `lang/es/local_courseprogressnotify.php` (Spanish)
- `lang/ca/local_courseprogressnotify.php` (Catalan)

## Code Standards

Follow Moodle coding standards and best practices for plugin development.
