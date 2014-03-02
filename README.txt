=== Redux Converter ===
Contributors: dovyp
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=3WQGEY4NSYE38
Tags: option framework, framework, converter, optiontree, smof
Requires at least: 3.5.1
Tested up to: 3.8.1
Stable tag: 1.1.0
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Redux Converter allows you to get going with Redux Framework almost instantly. Just install and run in a theme using one of the supported frameworks, and try out the same panel and data in Redux. Also download a config file & data conversion!

== Description ==

Redux Converter takes the pain out of trying Redux with your current framework. It auto-generates a fully functional panel with compatable fields. It even has auto-generated code for you to migrate your data from/to.

After you intall the plugin, it will search for supported framework(s) that may be installed. You will then see a new Admin Menu option names `XXX 2 Redux`. Click that item for a fully functional panel using Redux Framework, your current panel configuration and your current data.

= Supported Frameworks = 
* SMOF - Slightly Modified Options Framework
* OptionTree

= Found a Bug? Need Help? =
Visit our issue tracker at https://github.com/ReduxFramework/redux-converter/issues

== Installation ==

Installation is pretty strait forward. In case you need some instruction, here goes.

= Using The WordPress Dashboard =

1. Navigate to the 'Add New' in the plugins dashboard
2. Search for 'redux-converter'
3. Click 'Install Now'
4. Activate the plugin on the Plugin dashboard

= Uploading in WordPress Dashboard =

1. Navigate to the 'Add New' in the plugins dashboard
2. Navigate to the 'Upload' area
3. Select `redux-converter.zip` from your computer
4. Click 'Install Now'
5. Activate the plugin in the Plugin dashboard

= Using FTP =

1. Download `redux-converter.zip`
2. Extract the `redux-converter` directory to your computer
3. Upload the `redux-converter` directory to the `/wp-content/plugins/` directory
4. Activate the plugin in the Plugin dashboard


== Frequently Asked Questions ==

= Does this really work? =
Yes.

= Are you sure? =
Yes.

= Really? =
Yes. Try it already!

= Will my data get messed up if I try it? =
Nope. Everything in the demo panel is stored in a transient table that expires after an hour. To fully convert to Redux, we have a section that generates an actual config file for you.

= Sounds great, but my users will be up a creek! =
Not true. With the config file there's also a data conversion function that's provided so your users can switch. It only runs on theme activation (upgrade) too!

= Wow, this is amazing! How can I help? =
Please donate!

== Screenshots ==

1. This demonstrates how you can take any theme using one of the accepted frameworks, and run it through Redux. Avada is a trademark of ThemeFusion. We only used this theme as it has a fair amount of exposure. We claim no rights to Avada.  :P

== Changelog ==

= 1.1.1 =
* Small fix where the converter hook was commented out even if the user wanted it.

= 1.1.0 =
* Full OptionTree conversion and deploying on WordPress.org.

= 1.0.0 =
* Full SMOF conversion and deploying on WordPress.org.

== Updates ==

You'll be notified of updates using the standard WordPress update procedure.
