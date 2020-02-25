=== Another MailChimp Widget ===
Contributors: jameslafferty, MotoPress
Donate link: https://motopress.com/
Tags: newsletter, mailchimp, mailchimp widget, mailchimp subscribe, mailchimp shortcode
Requires at least: 3.8
Tested up to: 5.3
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Simple MailChimp subscription form to your lists and groups.

== Description ==

This plugin lets your users sign up for your MailChimp lists and groups via subscription form added through widget or shortcode.
Based on [jameslafferty](https://profiles.wordpress.org/jameslafferty/) ["MailChimp Widget" plugin](https://wordpress.org/plugins/mailchimp-widget/).

= Shortcode example: =

[mp-mc-form list="list_id/group_id" button="Subscribe" email_text="Your E-mail" first_name_text="First Name" last_name_text="Last Name" placeholder="true" firstname="false" lastname="false" success="Thank you for joining our mailing list." failure="There was a problem processing your submission." ]

= Shortcode attributes: =
* list - MailChimp list_id or list_id/group_id if you want to subscribe to specific group. To subscribe to several lists and groups separate them by comma.
* button - button label
* email_text - label of the email address field
* first_name_text - label of the first name field
* last_name_text - label of the last name field
* placeholder - true or false; set true to display labels as placeholders;
* firstname - true or false; set true if first name is required;
* lastname - true or false; set true if last name is required;
* success - success message;
* failure - failure message;

== Installation ==
1. Upload the plugin to /wp-content/plugins/.
1. Activate the plugin through the "Plugins" menu in WordPress.
1. Enter a valid MailChimp API key on the Settings > Another MailChimp page.
1. Drag the widget into your sidebar from the "Widgets" menu in WordPress or add subscription form via shortcode to any page or post.
1. Select a mailing list and configure options.

== Screenshots ==

1. Example of subscription forms.

== Frequently Asked Questions ==

== Changelog ==

= 2.0.8 =
* Version bump.

= 2.0.7 =
* Tweak: unique names to prevent conflicts with other plugins.

= 2.0.6 =
* Bug fix: fixed deprecated function.

= 2.0.5 =
* Added filter to default subscription status.

= 2.0.4 =
* Added the lists, groups and shortcode attributes to the plugin settings.

= 2.0.3 =
* Performance improvements.

= 2.0.2 =
* Bug fix: fixed an error in 2.0.1.

= 2.0.1 =
* Tweak: unique class names to prevent conflicts with other plugins.

= 2.0.0 =
* Performance improvements.
* Added the ability to subscribe users to groups.
* Added html5 validation of form input fields.
* Added the ability to change text of input fields.
* Bug fix: fixed the issue with html code when the form was incorrect after user subscription.

= 1.3.3 =
* Bug fix: increased MailChimp pagination limit to get more than 10 lists and interests

= 1.3.2 =
* Bug fix: Fixed output of custom notifications.

= 1.3.1 =
* Bug fix: Fixed the issue of autoloading classes.

= 1.3 =
* MailChimp API v3.0

= 1.2 =
* Admin notice removed

= 1.1 =
* Added the space to form items
* Added the ability to dismiss admin notification

= 1.0 =
* Release

== Upgrade Notice ==