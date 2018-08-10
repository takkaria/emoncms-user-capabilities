# User capabilities

This is an attempt to add some kind of fine-grained permissions model to EmonCMS, initially just the groups module.  Very alpha at the moment.

Looks a bit like this:

![readme.png](readme.png)

## Roadmap

- Get this module working well enough with groups for now (involves thinking through security model of both this + groups + emon core together)
- Set up testing
- Compile down JavaScript into something more browser-friendly
- Figure out how to integrate with emoncms core – ideally replacing the current 'admin' flag on users somehow

