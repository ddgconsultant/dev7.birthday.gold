# Logo Converter

This folder contains an automated logo conversion system for Birthday Gold.

## How to Use

1. **Drop logos here**: Place any logo image files (PNG, JPG, JPEG, GIF, BMP, WEBP, SVG) directly in this CONVERT folder

2. **Run the converter**: Execute the conversion script using one of these methods:
   ```bash
   # Method 1: Use the wrapper script
   bash run_converter.sh
   
   # Method 2: Direct Python with virtual environment
   /tmp/logo_converter_env/bin/python convert_logos.py
   
   # Method 3: If you have PIL installed
   python3 convert_logos.py
   ```

3. **Results**:
   - Converted logos will appear in `/public/assets/img/logos/bwpng/`
   - Original files will be moved to the `DONE` subfolder
   - All logos will be:
     - Transparent PNG format
     - 55px height (maintaining aspect ratio)
     - Minimum 80px width
     - Black logos on transparent background (ready for CSS white filter)

## Automated Processing (Optional)

### Method 1: Uptime Kuma (Recommended)
Add a monitor in Uptime Kuma with:
- **Type**: HTTP(s) Keyword
- **URL**: `https://dev7.birthday.gold/admin_actions/scheduler--convertlogos.php`
- **Keyword**: `[STATUS: OK]`
- **Interval**: 60 minutes

### Method 2: Direct PHP Scheduler
```bash
# Run via HTTP request
curl https://dev7.birthday.gold/admin_actions/scheduler--convertlogos.php

# Or via command line
php /mnt/w/BIRTHDAY_SERVER/dev7.birthday.gold/admin_actions/scheduler--convertlogos.php
```

### Method 3: Cron Job
```bash
# Run every hour via HTTP
0 * * * * curl -s https://dev7.birthday.gold/admin_actions/scheduler--convertlogos.php > /dev/null

# Or run PHP directly
0 * * * * php /mnt/w/BIRTHDAY_SERVER/dev7.birthday.gold/admin_actions/scheduler--convertlogos.php >> /var/log/convertlogos.log 2>&1
```

## Manual Processing

You can also run the script manually whenever you add new logos:
```bash
cd /mnt/w/BIRTHDAY_SERVER/dev7.birthday.gold/public/assets/img/logos/CONVERT
bash run_converter.sh
```

## Specifications

- **Output Height**: 55px
- **Minimum Width**: 80px (except for square/circular logos)
- **Format**: PNG with transparency
- **Color**: Black (for CSS filter: brightness(0) invert(1) to make white)
- **Background**: Transparent (pixels > 240 luminance removed)
- **Special Handling**: Square/circular logos (aspect ratio 0.9-1.1) are kept square at 55x55px to preserve their shape

## Supported Formats

- **Raster Images**: PNG, JPG, JPEG, GIF, BMP, WEBP
- **Vector Images**: SVG (requires cairosvg library)

## SVG Support

SVG files are automatically converted to high-resolution PNGs before processing. The converter will attempt to install cairosvg automatically. If SVG support is not available, you'll see a note in the output.

To manually install SVG support:
```bash
pip3 install --user cairosvg
```

## Processed Files

Check the `DONE` folder to see all previously processed logos.