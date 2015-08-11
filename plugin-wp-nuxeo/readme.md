# Nuxeo Plugin for Wordpress

**Contributors**:       [nuxeo](http://www.nuxeo.com/)  
**Tags**:               automation, nuxeo  
**Requires at least**:  4.1.4
**Tested up to**:       4.2.1  
**Plugin URI**:         http://www.nuxeo.com/  
**Version**:            0.0.1-snapshot  
**Author**:             Nuxeo  
**Author URI**:         http://www.nuxeo.com/  
**License**:            Apache License v2.0  

## Description

WordPress Nuxeo integration through the use of a shortcode.

## Installation

### Manual

On WordPress side, zip the plugin-wp-nuxeo projet and deploy manually through the Plugins panel of WordPress. Set the global parameters through the Settings>Nuxeo Options menu.
On Nuxeo side, build the [SSO plugin](https://github.com/nuxeo/nuxeo-platform-login/tree/master/nuxeo-platform-login-mod_sso) (according to your Nuxeo version) and store it on NUXEO-PATH/nxserver/bundles.
Create a XML file mod-sso-config.xml with the following content and store it on NUXEO-PATH/nxserver/config :

```xml
<component name="org.nuxeo.ecm.platform.wordpress.authenticator.mod.sso.config">
 
  <require>org.nuxeo.ecm.platform.ui.web.auth.WebEngineConfig</require>
  <require>org.nuxeo.ecm.platform.login.Proxy</require>
 
  <extension target="org.nuxeo.ecm.platform.ui.web.auth.service.PluggableAuthenticationService" point="authenticators">
    <authenticationPlugin name="PROXY_AUTH">
      <loginModulePlugin>Trusting_LM</loginModulePlugin>
      <parameters>
        <!-- configure here the name of the http header that is used to retrieve user identity -->
        <parameter name="ssoHeaderName">remote_user</parameter>
        <parameter name="ssoNeverRedirect">true</parameter>
      </parameters>
    </authenticationPlugin>
  </extension>
 
  <extension target="org.nuxeo.ecm.platform.ui.web.auth.service.PluggableAuthenticationService" point="chain">
    <authenticationChain>
      <plugins>
        <!--  Keep basic Auth at top of Auth chain to support RSS access via BasicAuth -->
        <plugin>BASIC_AUTH</plugin>
				<plugin>FORM_AUTH</plugin>
      </plugins>
    </authenticationChain>
  </extension>

  <extension point="specificChains" target="org.nuxeo.ecm.platform.ui.web.auth.service.PluggableAuthenticationService">
    <specificAuthenticationChain name="Automation">
        <urlPatterns>
            <url>(.*)/automation.*</url>
        </urlPatterns>
        	<replacementChain>
						<plugin>PROXY_AUTH</plugin>
            		<plugin>AUTOMATION_BASIC_AUTH</plugin>
						<plugin>ANONYMOUS_AUTH</plugin>
        </replacementChain>
    </specificAuthenticationChain>
  </extension>
</component>

```

## Usage

Usage of the plugin :
 * [nuxeo path="/my particular/folder name/"] 												# Display the content of a folderish document based on its path (under the configured domain path)
 * [nuxeo type="File"]   																							# Display Documents whose type matches
 * [nuxeo nxquery="SELECT * FROM File WHERE ..."]                				# Display Documents based on the NXQL query
 * [nuxeo name="Agenda%.doc"]                   													# Docs whose name matches. May include wildcard character '%'.
 * [nuxeo doc_id="c5e890d3-87f5-42cb-abe4-1ed9d0d2600c"]				# Display the detail of a document based on its id
 * [nuxeo folderish_id="05cdeaa1-e57d-450a-8347-1600206e7cce"]		# Display the content of a folderish document based on its id

## Documentation
Take a look at the Blog Post [Power Up Your Website: Integrate Nuxeo with WordPress](http://www.nuxeo.com/blog/power-up-your-website-integrate-nuxeo-with-wordpress/) to have more details (installation and usage) on that plugin.

## License

apache License v2.0
