== Paid Memberships Pro - Group Discount Codes Add On ==
Contributors: strangerstudios
Tags: pmpro, paid memberships pro, discount codes, group codes, group discount
Requires at least: 3.6
Tested up to: 4.3.1
Stable tag: .3.1

Adds features to PMPro to better manage grouped discount codes or large numbers of discount codes.

== Description ==

Adds the ability to create a single ‘parent’ discount code and bulk generate ‘child’ codes with the same settings. The parent code controls all of the pricing, expiration, allowed levels, etc. Once saved, each of the sub codes will act as if they have the same settings as the main code.

This is useful for bulk mailing, newsletters, or participation in a Groupon-type program.

== Installation ==

1. Upload the 'pmpro-group-discount-codes' directory to the '/wp-content/plugins/' directory of your site.
1. Activate the plugin through the 'Plugins' menu in WordPress.
1. Go to Memberships > Discount Codes.
1. Create a new discount code.
1. Under "Group Codes" enter one unique code per line (these can be auto-generated with any random string generator - https://www.random.org/strings/ - or in a spreadsheet program).
1. Set the pricing per level that the group of codes will apply to.
The "parent" code should be kept private with unlimited uses.

== Frequently Asked Questions ==

= I found a bug in the plugin. =

Please post it in the issues section of GitHub and we'll fix it as soon as we can. Thanks for helping. https://github.com/strangerstudios/pmpro-group-discount-codes/issues

== Changelog ==

= .3.2 =
* ENHANCEMENT: Added Group Code Uses column to Memberships > Discount Codes to show a sum of child codes that have been used.
* ENHANCEMENT: Added a Group Code column to the PMPro Orders CSV Export.

= .3.1. =
* BUG: Now sending group codes instead of master code in emails.

= .3 =
* BUG: Now correctly tracking group discount code uses.

= .2.1 =
* Added column for count of group codes to main Discount Codes page.
* Added link to random string generator for easier import.

= .2 =
* Handling when codes are deleted better.

= .1 =
* This is the initial version of the plugin.
