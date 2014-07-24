WordPress-CallTrackingMetrics-POST-Plugin
=========================================

Simple plugin to relay calls from [Call Tracking Metrics](http://www.calltrackingmetrics.com) API (webhook) to another endpoint (mapping the variables).

Could be easily extended to process call data in some other way - this was just the use I needed.

In order to pass through Google Analytics clientID parameter, [this snippet](https://gist.github.com/chrisgoddard/700406e6e28e194688b7) needs to be present alongside the Google Analytics and Call Tracking Metrics snippets.

Plugin uses my [WpExternalApi class](https://gist.github.com/chrisgoddard/21d559fb5890c2d17b9e) to create a simple JSON or XML endpoint on any WordPress installation.

No plugin UI - code must be modified to set POST url and parameter mapping.
