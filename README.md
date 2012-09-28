# Media Access Control #

Media Access Control is WordPress plugin that is meant to be extended by other plugins or developers in order to implement custom access rules for uploaded media files. On its own, it won't actually do anything useful.

The plugin adds a field to the standard Media settings screen that allows users to define a list of file extensions that should have custom access rules applied to them. Then when a visitor makes a request to a matching file, the file is passed through a filter that allows plugins to determine whether or not the visitor should be granted access.

Custom business rules will need to be applied by hooking into the `media_access_control_allow_file_access` filter.

Additional filters are provided for disabling the settings interface and controlling the whitelist manually. This should work well as a "mu-plugin" to ensure it's executed before regular plugins and so it can't be disabled, or as an include.

## Credits ##

Built by Brady Vercher ([@bradyvercher](http://twitter.com/bradyvercher))  
Copyright 2012  Blazer Six, Inc.(http://www.blazersix.com/) ([@BlazerSix](http://twitter.com/BlazerSix))