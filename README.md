# Mixpanel Instrumentation for WordPress

A WordPress plugin that integrates Mixpanel analytics and session replay into your site. Perfect for single-site and multisite installations with powerful network admin controls.

Created with the Claude Sonnet 4 LLM.

## ✨ Features

- 🎯 **Smart Event Tracking**: Uses Mixpanel's autocapture feature for comprehensive event tracking without manual coding
- 🎬 **Session Replay**: Optional Mixpanel session replay integration for user behavior analysis
- 🌐 **Multisite Ready**: Full WordPress multisite compatibility with network-level controls
- 👤 **User Identification**: Automatically track logged-in users with their profile data and roles
- ⚡ **Performance Monitoring**: Track WordPress core function execution times (optional)
- 💾 **localStorage Persistence**: Modern data storage for better tracking continuity
- 🛡️ **Clean Uninstall**: Removes all plugin data when deactivated/uninstalled
- 🎛️ **Granular Controls**: Site-specific overrides and network-wide settings

## 📦 Installation

1. Upload the plugin files to `/wp-content/plugins/mixpanel-instrumentation`
2. Activate the plugin in WordPress admin
3. Go to **Settings → Mixpanel Instrumentation** to configure your settings
4. Enter your Mixpanel token (required)
5. Choose your tracking mode and optionally enable session replay

### For Multisite

1. Network activate the plugin
2. Go to **Network Admin → Settings → Mixpanel Instrumentation**
3. Configure network-wide settings
4. Optionally allow site-specific overrides

## 🔧 Usage

### Basic Setup

- Enter your Mixpanel token in the settings
- Choose tracking mode: pageviews, all JS events, or specific JS events
- Optionally enable session replay

### Tracking Modes

1. **Pageviews Only:** Tracks when pages are loaded
2. **All JS Events:** Tracks clicks, inputs, and other user interactions
3. **Specific Events:** Define custom events like `click`, `submit`, `focus`, etc. (comma-separated)

### Multisite Configuration

Network admins can:
- Enable Mixpanel for all sites in the network
- Enable for specific sites only (by site ID)
- Allow individual site admins to override network settings
- Configure centralized tracking settings

## 🔒 Privacy & GDPR

This plugin sends data to Mixpanel's servers for analytics purposes. Ensure compliance with privacy regulations by:
- Adding Mixpanel to your privacy policy
- Obtaining user consent where required
- Configuring Mixpanel's privacy settings appropriately

## 🛠 Development

### Requirements

- WordPress 5.0+
- PHP 7.2+
- Mixpanel account and project token

### File Structure

```
mixpanel-instrumentation/
├── mixpanel-instrumentation.php  # Main plugin file
├── readme.txt                    # WordPress.org readme
└── README.md                     # GitHub readme
```

## 📝 License

This plugin is licensed under the GNU General Public License v2 or later.

```
Copyright (C) 2025 Goke Pelemo

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
```

**License URI:** https://www.gnu.org/licenses/gpl-2.0.html

### Third-Party Dependencies

This plugin integrates with:
- **Mixpanel Analytics**: External service for analytics and session replay
- **WordPress Core**: GPL v2 compatible framework

All code in this plugin is original work and GPL v2 compatible.

## 🤝 Contributing

Contributions are welcome! Please submit a pull request with your improvements.

## Changelog

### v1.0.0
- Initial release
- Mixpanel analytics integration
- Session replay support
- Three tracking modes (pageviews, all events, specific events)
- Full multisite compatibility
- Network admin controls
- Site-specific override capability
- Clean uninstall functionality

---

**Ready to track your WordPress site's analytics with Mixpanel? Get started today!** 🎯
