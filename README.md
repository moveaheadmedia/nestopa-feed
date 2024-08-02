# Nestopa Feed

A WordPress plugin to create XML feeds for the Property post type to be used on the Nestopa platform.

## Description

Nestopa Feed is a plugin designed to generate XML feeds for property listings. It retrieves information from custom post types and outputs the data in an XML format compatible with the Nestopa platform.

## Features

- Generates XML feeds for property listings
- Supports custom post types
- Includes property features, images, coordinates, and more
- Scheduled XML generation using WordPress cron jobs
- Manual XML generation via admin settings page
- Adds a settings link on the plugins page

## Installation

1. Download the plugin zip file.
2. Go to your WordPress admin dashboard.
3. Navigate to **Plugins > Add New**.
4. Click **Upload Plugin**.
5. Choose the downloaded zip file and click **Install Now**.
6. Activate the plugin.

## Usage

1. Upon activation, the plugin will schedule an hourly event to generate the XML feed.
2. You can also manually generate the XML feed by navigating to **Settings > Nestopa Feed** and clicking the **Generate Now** button.
3. The URL to the generated XML feed is displayed on the settings page.

## Shortcodes and Functions

### Shortcodes

No shortcodes are provided by this plugin.

### Functions

- `nestopa_feed_generate_xml()`: Manually generate the XML feed.
- `nestopa_feed_get_lat_lng_from_address_nominatim($address)`: Get latitude and longitude coordinates from an address using the Nominatim API.

## Customization

You can customize the plugin by modifying the following functions:

- `nestopa_feed_convert_price_to_integer($price)`: Convert price formats to integers.
- `nestopa_feed_convert_array_format($input_array)`: Convert serialized arrays to a custom format.

## Contributing

1. Fork the repository.
2. Create a new branch: `git checkout -b my-feature-branch`.
3. Make your changes and commit them: `git commit -m 'Add some feature'`.
4. Push to the branch: `git push origin my-feature-branch`.
5. Submit a pull request.

## License

This plugin is licensed under the GPLv2 or later.

## Changelog

### 1.0.0

- Initial release.

## Author

**Ali Sal**  
[Move Ahead Media](https://moveaheadmedia.co.uk)

## Plugin URI

[GitHub Repository](https://github.com/moveaheadmedia/nestopa-feed)
