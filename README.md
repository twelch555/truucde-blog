# user-create-domain-exception

This is an MU plugin that enables super and site admins to create users outside of white/black list restrictions

## Installation
I've written this with MU installation in mind: create mu-plugins directory in wp-content and copy **just the plugin file (not the whole folder)**.

If you are running a plugin manager you may wish to install it (with directory) as a conventional plugin to more selectively make it available.

Also, in order for this plugin to work the following option needs to be
checked. Network Dashboard->Settings->Add New Users/Allow site
administrators to add new users to their site via the "Users → Add New"
page

## Note
This plugin works by removing the error codes/messages corresponding to the white list and black list checks. Clearing these errors enables logged in users with current_user_can('promote_users') permissions (aka Site Admins and Super Admins) to add users free of the white list/black list checks.

## However
As the error codes and messages are strings, and the error messages are wrapped in the translatable tag __( ) this plugin will fail to work unless the error message text is an exact match.

So, it is worth checking plugin operation if you have: a WP language version other than US English, multiple languages, or translated system messages.

It is also conceivable that the error messages will be edited or rewritten at some point (I damn near rewrote the horrible confusing black list message myself, but you know...don't edit core code and all that). If the error message change then this plugin will fail to function.

## Luckily
"Fail to function" as indicated above means that the white/black lists come back into effect for Super/Site Admins. So at least things become more secure rather than less.

