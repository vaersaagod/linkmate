# Release Notes for LinkMate

## 2.1.0 - 2022-05-27  
### Added
- Added the "Show site menu" setting to the element link type, making it possible to link across sites to non-propagated elements (excepting users).  

### Improved
- Elements are now queried using element query param methods instead of criteria arrays, which should improve reliability for automatic template cache invalidation.  

### Changed
- Moved the `schemaVersion` definition into the main plugin class  

## 2.0.2 - 2022-05-15
### Fixed
- Fixes a PHP exception that could occur when saving a link field

## 2.0.1 - 2022-05-06

### Fixed
- Fixes a PHP exception that would be thrown if there wasn't an element selected for a link field  

### Improved
- Stricter types for a couple of Link model attributes  

## 2.0.0 - 2022-05-06

### Added  
- Added Craft 4 support

## 1.1.0 - 2021-09-28

### Added  
- Added the "UI Mode" field setting, with "Normal" and "Compact" options  

### Changed
- Changed the "Url" link type label to "URL"

## 1.0.1 - 2020-06-17

### Added
- Added migrate helper to help transition from LinkIt fields to LinkMate.


## 1.0.0 - 2020-04-28
- Initial release
