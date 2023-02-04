# Release Notes for LinkMate

## 2.4.0 - 2023-02-05
### Added
- LinkMate fields are now previewable in element indexes  

## 2.3.1 - 2023-01-27
### Changed
- Input link types (email, phone, URL, custom) now return their raw URLs for the `getText()` method  

## 2.3.0 - 2023-01-20
### Added
- Added the "Allow element sources without URLs" setting to entry, categories and asset link types. This setting applies when "All" is selected as the source for these element types.  
### Changed
- LinkMate now hides all sources without URLs, for entry, categories and asset link types where the source setting is set to "All". The new "Allow element sources without URLs" setting can be used to override this behavior.  
### Fixed
- Fixed an issue where LinkMate could throw an exception if a Link had no LinkField instance set. Hello edge case.

## 2.2.2 - 2022-08-02
### Fixed
- Fixes typing issues in ElementSourceValidator

## 2.2.1 - 2022-05-02
### Fixed
- Fixes an issue where the site menu would always display in element selector modals, regardless of whether the "Show site menu" option was actually checked or not.  

## 2.2.0 - 2022-05-27
### Changed  
- Changed default link type to "Entry"
- All link types are now disabled by default, for new fields
- Moved the "Enable all" toggle to the bottom of the link type settings list  
- Tightened up the styles for the field settings a little bit  

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
