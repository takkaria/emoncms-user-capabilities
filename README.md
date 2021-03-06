# User capabilities

This is an attempt to add some kind of fine-grained permissions model to EmonCMS, initially just the groups module.

## Concept

A 'capability' is just the ability to do something.  For example, to be able to create a user in the groups module or to rename a group.  The way it is currently implemented, the ability to use each API endpoint is a capability.

Roles are collections of capabilities, which are then granted to a particular set of users.

If the user has a role which grants them a capability, they can do that thing.  If they aren't, they can't.  For example, if a user has the `groups_view` capability, they can see the groups UI.  If they don't, they can't.

## Status

**Alpha**

This module works, but doesn't do very much by itself.  To be genuinely useful you need a patched version of the `group` module that supports this module, which you can find on the [capability](https://github.com/emoncms/group/compare/capabilities?expand=1) branch of the [`group`](https://github.com/emoncms/group) module.

You also need to be running a version of EmonCMS from after 12 August, which contained a patch that allows underscores in module names.

## Installation instructions

1. `git clone https://github.com/takkaria/emoncms-user-capabilities.git user_capabilities` into Modules directory
2. Add `const CAPABILITIES_ALWAYS_SUPERUSER = <userid>;` to `settings.php` for whoever your initial 'root' user will be
3. After installing, log in as that user and go to <emoncms url>/user_capabilities
4. You can use it.

## Screenshot

![readme.png](readme.png)

## Requirements

The capabilities frontend is written using "modern" JavaScript (ECMAScript 2015) so needs a modern browser (recent Firefox, Chrome, Edge, Safari will all be fine).

## Future plans

- Set up testing
- Compile down JavaScript into something more browser-friendly
- Figure out how to integrate with emoncms core – ideally replacing the current 'admin' flag on users somehow

