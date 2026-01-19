# Moodle Plugin Development Instructions

## Version Management - CRITICAL RULE

**Every time you make ANY change to this plugin, you MUST update the version number in `version.php`.**

### When to Update Version:
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

### How to Update:
1. Open `version.php`
2. Update `$plugin->version` to current date + increment
3. Example: First change on 2026-01-19: `2026011900`
4. Second change same day: `2026011901`

### Why:
- Moodle requires version increments to trigger upgrades
- Without updates, changes won't be applied to the database
- Prevents caching issues and ensures proper plugin updates

## Language Strings

When adding or modifying features, ensure all language strings are added to ALL language files:
- `lang/en/local_courseprogressnotify.php` (English - required)
- `lang/es/local_courseprogressnotify.php` (Spanish)
- `lang/ca/local_courseprogressnotify.php` (Catalan)

## Code Standards

Follow Moodle coding standards and best practices for plugin development.
