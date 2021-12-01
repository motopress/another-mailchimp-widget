# [Another Mailchimp Widget](https://motopress.com/products/another-mailchimp-widget/) #

![](https://img.shields.io/wordpress/plugin/v/another-mailchimp-widget.svg?style=flat)
![](https://img.shields.io/wordpress/plugin/installs/another-mailchimp-widget.svg?style=flat)
![](https://img.shields.io/wordpress/plugin/rating/another-mailchimp-widget.svg?style=flat)
![](https://img.shields.io/wordpress/plugin/tested/another-mailchimp-widget.svg?style=flat)
![](https://img.shields.io/badge/license-GPL--2.0%2B-blue.svg?style=flat)

This WordPress plugin lets your users sign up for your Mailchimp lists and groups via subscription form added through widget or shortcode.

## Getting started ##
1. You can clone the GitHub repository: `https://github.com/motopress/another-mailchimp-widget.git`.
1. Or download it directly as a ZIP file: `https://github.com/motopress/another-mailchimp-widget/archive/master.zip`.
1. If you want to use the latest release with your WordPress site, get the latest release from the [WordPress.org plugins repository](https://wordpress.org/plugins/another-mailchimp-widget/).
1. Install and activate the plugin through the "Plugins" menu in WordPress.
1. Enter a valid [Mailchimp API key](https://mailchimp.com/help/about-api-keys/) on the `Settings > Mailchimp` page.
1. Drag the widget into your sidebar from the `Appearance > Widgets` menu in WordPress or add subscription form via [shortcode](#shortcode) to any page or post.
1. Select a mailing list and configure options.

### Shortcode ###

```
[mp-mc-form list="list_id/group_id" button="Subscribe" email_text="Your E-mail" first_name_text="First Name" last_name_text="Last Name" placeholder="true" firstname="false" lastname="false" success="Thank you for joining our mailing list." failure="There was a problem processing your submission." ]
```

#### Shortcode attributes ####
* `list` - Mailchimp list_id or list_id/group_id if you want to subscribe to specific group. To subscribe to several lists and groups separate them by comma.
* `button` - button label.
* `email_text` - label of the email address field.
* `first_name_text` - label of the first name field.
* `last_name_text` - label of the last name field.
* `placeholder` - true or false; set true to display labels as placeholders.
* `firstname` - true or false; set true if first name is required.
* `lastname` - true or false; set true if last name is required.
* `success` - success message.
* `failure` - failure message.

## Support ##
This is a developer's portal for Another Mailchimp Widget and should _not_ be used for support. Please visit the [support page](https://wordpress.org/support/plugin/another-mailchimp-widget/) if you need to submit a support request.

## Contributions ##
Anyone is welcome to contribute.