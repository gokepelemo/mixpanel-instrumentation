=== Mixpanel Instrumentation ===
Contributors: gokepelemo
Tags: analytics, mixpanel, session replay, multisite, tracking
Requires at least: 5.0
Tested up to: 6.5
Requires PHP: 7.2
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Adds Mixpanel analytics and optional session replay to your WordPress site frontend. Multisite compatible with network admin controls and site-specific overrides.

== Description ==

The Mixpanel Instrumentation plugin seamlessly integrates Mixpanel analytics into your WordPress site with powerful features designed for both single-site and multisite installations.

**Key Features:**

* **Easy Setup:** Simply enter your Mixpanel token and start tracking
* **Smart Event Tracking:**
  * Track pageviews only
  * Track all user interactions (clicks, inputs, scrolling, form submissions)
  * Track specific events using Mixpanel's autocapture feature
* **User Identification:** Automatic identification and tracking of logged-in users with profile data
* **Session Replay:** Optional Mixpanel session replay integration
* **Performance Monitoring:** Optional WordPress core function execution time tracking
* **localStorage Persistence:** Modern data storage for better tracking continuity across sessions
* **Multisite Compatible:**
  * Network admins can enable for all sites, specific sites, or none
  * Allow site-specific overrides when needed
  * Centralized network-wide configuration
* **Clean Uninstall:** All plugin options are removed when uninstalled

**Perfect for:**
* E-commerce sites tracking user interactions
* Content sites monitoring engagement
* Multisite networks requiring centralized analytics control
* Developers needing flexible event tracking

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/mixpanel-instrumentation` directory, or install the plugin through the WordPress plugins screen directly
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Go to Settings → Mixpanel Instrumentation to configure your settings
4. Enter your Mixpanel token (required)
5. Choose your tracking mode and optionally enable session replay

**For Multisite:**
1. Network activate the plugin
2. Go to Network Admin → Settings → Mixpanel Instrumentation
3. Configure network-wide settings
4. Optionally allow site-specific overrides

== Frequently Asked Questions ==

= How do I get a Mixpanel token? =

Sign up for a free account at mixpanel.com, create a project, and copy your project token from the project settings.

= Does this work with WordPress multisite? =

Yes! Network admins can:
* Enable Mixpanel for all sites in the network
* Enable for specific sites only (by site ID)
* Allow individual site admins to override network settings
* Configure centralized tracking settings

= What events can I track? =

You can choose from three tracking modes:
* **Pageviews:** Track when pages are loaded
* **All JS Events:** Track clicks, inputs, and other interactions
* **Specific Events:** Define custom events like 'click', 'submit', 'focus', etc.

= Is session replay included? =

Yes, you can optionally enable Mixpanel's session replay feature to record user sessions for analysis.

= Will this slow down my site? =

No, the Mixpanel script loads asynchronously and has minimal impact on page load times.

= What happens when I uninstall the plugin? =

All plugin settings and options are completely removed from your database for a clean uninstall.

== Screenshots ==

1. Plugin settings page - configure token, tracking mode, and session replay
2. Network admin settings - multisite configuration options
3. Tracking mode selection - pageviews, all events, or specific events

== Changelog ==

= 1.0.0 =
* Initial release
* Mixpanel analytics integration
* Session replay support
* Three tracking modes (pageviews, all events, specific events)
* Full multisite compatibility
* Network admin controls
* Site-specific override capability
* Clean uninstall functionality

== Upgrade Notice ==

= 1.0.0 =
Initial release of Mixpanel Instrumentation plugin with full analytics and multisite support.

== Privacy Policy ==

This plugin sends data to Mixpanel's servers for analytics purposes. Please ensure you comply with privacy regulations like GDPR by:
* Adding Mixpanel to your privacy policy
* Obtaining user consent where required
* Configuring Mixpanel's privacy settings appropriately

For more information, see Mixpanel's privacy documentation at https://mixpanel.com/legal/privacy-policy/
