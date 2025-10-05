# Storytelling Hero Media Assets

Place unoptimised source imagery inside `source/` and run `npm run build:hero-media` from the theme root to generate responsive JPEG/WebP variants. The build script outputs files following the `{image_reference}-{width}.{ext}` naming convention required by the storytelling presenter. Commit the generated variants alongside taxonomy updates so the front office can load `<picture>` elements without broken links.
