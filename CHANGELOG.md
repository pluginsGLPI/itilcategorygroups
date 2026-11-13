# Change Log

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

## [UNRELEASED]

### Fixed

- Delete the group links of a rule when it is purged, and clean the links left by rules purged before
- Reduce SQL queries of the "Assigned to" actor dropdown filter (one query per group replaced by batched queries)
- Load the 4 levels of the rule form in 2 queries instead of 8
- Only delete and add the changed group links when saving a rule
- Remove the unused `multiple_group.js` loaded on every page

## [2.6.3] - 2026-10-06

### Fixed

- Fix restrict mode
- Fix warnings and errors in logs
- CI: fix Psalm cache directory, drop the stray `glpi-project/tools` composer dependency, declare a unique composer autoloader suffix

## [2.6.2] - 2026-05-12

###

- Fix SQL query `Field 'MIN(level)' unknown`


## [2.6.1] - 2025-11-13

### Fixed

- Fix Group level not being saved correctly

## [2.6.0] - 2025-09-30

### Added

- GLPI 11 compatibility
