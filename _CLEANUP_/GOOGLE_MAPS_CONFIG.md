# Google Maps Configuration for Birthday Gold

## Configuration Variables

Add these to your ENV_CONFIGS file:

```php
$sitesettings['GOOGLEAPI']['mainkey'] = 'YOUR_API_KEY_HERE';
$sitesettings['GOOGLEAPI']['mapid'] = '9cd54b1058579fe87b380337';  // birthday-gold-www
```

## Map ID Details

- **Map ID**: `9cd54b1058579fe87b380337`
- **Name**: birthday-gold-www
- **Type**: JavaScript (Vector)
- **Features**: No tilt, No rotation
- **Purpose**: Tour route display with AdvancedMarkerElement support

## Environment-Specific Configuration

You can use different Map IDs for different environments:

```php
// Development
if ($site == 'dev7') {
    $sitesettings['GOOGLEAPI']['mapid'] = 'YOUR_DEV_MAP_ID';
}

// Production
if ($site == 'www') {
    $sitesettings['GOOGLEAPI']['mapid'] = '9cd54b1058579fe87b380337';
}
```

## Required Google APIs

Ensure these APIs are enabled in Google Cloud Console:
1. Maps JavaScript API
2. Places API  
3. Geocoding API
4. Directions API

## Testing

Visit `/myaccount/test-maps-api.php` to verify your configuration.

## Fallback

If `mapid` is not set in config, the code will fallback to the hardcoded production Map ID.