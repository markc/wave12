# YouTube API Keys Security Update

This document explains the changes made to secure the YouTube API keys that were previously hardcoded in JavaScript files.

## Changes Made

1. Added environment variables for YouTube API keys in `.env.example`
2. Created a Blade template to expose these environment variables to the frontend
3. Updated the Voyager configuration to include this template
4. Modified the JavaScript files to use these environment variables instead of hardcoded keys

## How to Set Up

1. Add the following lines to your `.env` file:

```
YOUTUBE_API_KEY_1=your_first_api_key
YOUTUBE_API_KEY_2=your_second_api_key
```

Replace `your_first_api_key` with the value that was previously hardcoded in `jquery.yt_data_v3.js` (AIzaSyBykc_bErt-mcLNSZ4ejKViOu4Prlllvbw).
Replace `your_second_api_key` with the value that was previously hardcoded in `youtube.js` (AIzaSyBQ2COy7Wdn8gfx-vavH8tMHPCmjE3rfWA).

2. Clear your application cache:

```
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

## Security Benefits

-   API keys are no longer hardcoded in JavaScript files that might be committed to version control
-   Keys can be easily rotated by updating the `.env` file without modifying code
-   Different environments (development, staging, production) can use different API keys

## Files Modified

1. `.env.example` - Added environment variables for YouTube API keys
2. `config/voyager.php` - Added the YouTube API keys Blade template to the additional_js array
3. `resources/views/vendor/voyager/youtube-api-keys.blade.php` - Created a new Blade template to expose environment variables
4. `public/vendor/tcg/voyager/assets/js/plugins/youtube/js/jquery.yt_data_v3.js` - Updated to use environment variables
5. `public/vendor/tcg/voyager/assets/js/plugins/youtube/js/youtube.js` - Updated to use environment variables
