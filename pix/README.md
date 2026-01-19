# Images for Email Templates

This folder contains images that are embedded in email notifications.

## Required Images

The following image files are required for the first day and second day task notifications:

- `email_documentation_location.png` - Screenshot showing where to find course documentation
- `email_tutorial_video.png` - Screenshot showing the tutorial video in the course

**Note:** All screenshots are in Catalan and will be used for all language versions.

## How to Add Images:

1. Create/capture the screenshots (in Catalan)
2. Save them with the exact filenames above
3. Place them in this `pix` folder
4. Images will be automatically embedded in emails based on user's language

## Technical Details:

- Images are referenced via moodle_url in the task classes
- Language detection is automatic based on user preferences
- Format: PNG recommended
- Max size: Keep under 500KB for faster email loading
- Resolution: 800px width recommended
