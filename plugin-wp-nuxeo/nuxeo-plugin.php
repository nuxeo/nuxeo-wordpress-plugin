<?php
/*
Plugin Name: Plugin Nuxeo for Wordpress
Plugin URI: https://github.com/nuxeo/plugin-wp-nuxeo
Description: Wordpress Plugin for Nuxeo through Automation API. Tested with Nuxeo 6.0.
Version: 0.0.1-snapshot
Author: nuxeo
Author URI: https://github.com/nuxeo
*/

// Required PHP files
$PLUGIN_PATH = dirname(__FILE__).'/';
require_once($PLUGIN_PATH . '/admin.php');
require_once ($PLUGIN_PATH . '/stream.php');
require_once ($PLUGIN_PATH . '/nuxeo/NuxeoAutomationAPI.php');

/*
Define Nuxeo shortcode

Usage:
    [nuxeo path="/my particular/folder name/"] 												# Display the content of a folderish document based on its path (under the configured domain path)
    [nuxeo type="File"]   																							# Display Documents whose type matches
    [nuxeo nxquery="SELECT * FROM File WHERE ..."]                				# Display Documents based on the NXQL query
    [nuxeo name="Agenda%.doc"]                   													# Docs whose name matches. May include wildcard character '%'.
	[nuxeo doc_id="c5e890d3-87f5-42cb-abe4-1ed9d0d2600c"]				# Display the detail of a document based on its id
	[nuxeo folderish_id="05cdeaa1-e57d-450a-8347-1600206e7cce"]		# Display the content of a folderish document based on its id
*/
function nuxeo_shortcode( $attr, $content = null ) {
    extract( shortcode_atts( array(
      'path' => '',
      'type' => '',
	  'nxquery' => '',
      'name' => '',
	  'doc_id' => '',
	  'folderish_id' => ''
      ), $attr ) );
	
	// Escape sur les caractères posant problème
	$tmp = $folder;
	$folder = str_replace("'", "\'", $tmp);
	
	return do_nxql($path, $type, $nxquery, $name, $doc_id, $folderish_id);
}
add_shortcode('nuxeo', 'nuxeo_shortcode');

function do_nxql($path, $type, $nxquery, $name, $doc_id, $folderish_id) {
	// Initialize Nuxeo Client
    $repo_url = get_option('nx_repository_url');
	$workspace_root = get_option('nx_workspace_root');
    //$repo_username = get_option('nx_username');
	$current_user = wp_get_current_user();	
	$repo_username = $current_user->user_login;
    $repo_password = get_option('nx_password');
	$display_nxql = get_option('nx_display_query');
	$error_message = 'Unable to display documents.';
	$folder_url = $workspace_root;
	
	// Check si appel d'un document ou dossier directement depuis son permalink
	if ($nxquery) {
			$result = display_nuxeo_query($repo_url, $repo_username, $repo_password, $nxquery, $display_nxql);
	} elseif ($doc_id) {
		$result = display_nuxeo_document_by_Id($repo_url, $repo_username, $repo_password, $doc_id, $display_nxql);
	} elseif ($folderish_id) {
		$result = display_nuxeo_folderish_by_Id($repo_url, $repo_username, $repo_password, $folderish_id, $name, $type, $display_nxql);
	} else {	
		// Add domain path if specified
		if ($workspace_root) {
			$path = $workspace_root.'/'.$path;
		}
		
		$result = display_nuxeo_document($repo_url, $repo_username, $repo_password, $path, $type, $name, $display_nxql);
	}		
	
	return $result;
}

/**
 * Function that display documents details based on a NXQL Query
 * @param unknown $repo_url
 * @param unknown $repo_username
 * @param unknown $repo_password
 * @param unknown $doc_id
 * @param unknown $display_nxql
 * @return string
 */
function display_nuxeo_query($repo_url, $repo_username, $repo_password, $nxquery, $display_nxql) {
	$nxclient = new NuxeoPhpAutomationClient($repo_url.'/site/automation');
	$nxsession = $nxclient->getSession($repo_username, $repo_password);

	$msg = $msg . trace_nuxeo_query($nxquery, $display_nxql);

	$docList = $nxsession->newRequest("Document.Query")->set('params', 'query', "$nxquery")->setSchema($schema = '*')->sendRequest();
	$docArray = $docList->getDocumentList();
	$value = sizeof($docArray);
	$msg = $msg.getDisplayHeader();
	for ($test = 0; $test < $value; $test++) {
		$msg = $msg . display_nuxeo_object(current($docArray));
		next($docArray);
	}
	$msg = $msg . getDisplayFooter();

	return $msg;
}

function display_nuxeo_document_by_Id($repo_url, $repo_username, $repo_password, $doc_id, $display_nxql) {
	$nxclient = new NuxeoPhpAutomationClient($repo_url.'/site/automation');
	$nxsession = $nxclient->getSession($repo_username, $repo_password);
	
	$query = "SELECT * FROM Document WHERE ecm:uuid = '$doc_id'";
	
	$query = $query." AND ecm:mixinType != 'Folderish' AND ecm:mixinType != 'HiddenInNavigation' AND ecm:isCheckedInVersion = 0 AND ecm:currentLifeCycleState != 'deleted'";
	
	$msg = $msg . trace_nuxeo_query($query, $display_nxql);
	
	$docList = $nxsession->newRequest("Document.Query")->set('params', 'query', "$query")->setSchema($schema = '*')->sendRequest();
	$docArray = $docList->getDocumentList();
	$value = sizeof($docArray);
	$msg = $msg.getDisplayHeader();
	
	for ($test = 0; $test < $value; $test++) {
		$msg = $msg . display_nuxeo_object(current($docArray));
		next($docArray);
	}
	$msg = $msg . getDisplayFooter();

	return $msg;
}

function display_nuxeo_folderish_by_Id($repo_url, $repo_username, $repo_password, $dossier_id, $name, $type, $display_nxql) {
	$nxclient = new NuxeoPhpAutomationClient($repo_url.'/site/automation');
	$nxsession = $nxclient->getSession($repo_username, $repo_password);
	
	$query = "SELECT * FROM Document WHERE ecm:parentId = '$dossier_id'";
	
	if ($name) {
		$query = $query." AND dc:title LIKE '$name%'";
	}
	
	if ($name) {
		$query = $query." AND ecm:primaryType = '$type%'";
	}
	
	$query = $query." AND ecm:mixinType != 'Folderish' AND ecm:mixinType != 'HiddenInNavigation' AND ecm:isCheckedInVersion = 0 AND ecm:currentLifeCycleState != 'deleted'";
	
	$msg = $msg . trace_nuxeo_query($query, $display_nxql);
	
	$docList = $nxsession->newRequest("Document.Query")->set('params', 'query', "$query")->setSchema($schema = '*')->sendRequest();
	$docArray = $docList->getDocumentList();
	$value = sizeof($docArray);
	$msg = $msg.getDisplayHeader();
	
	for ($test = 0; $test < $value; $test++) {
		$msg = $msg . display_nuxeo_object(current($docArray));
		next($docArray);
	}
	$msg = $msg . getDisplayFooter();
	
	return $msg;
}

function display_nuxeo_document($repo_url, $repo_username, $repo_password, $path, $type, $name, $display_nxql) {
	$nxclient = new NuxeoPhpAutomationClient($repo_url.'/site/automation');
	$nxsession = $nxclient->getSession($repo_username, $repo_password);
	
	$query = "SELECT * FROM Document WHERE";
	
	if ($path) {
		$query = $query." ecm:path STARTSWITH '$path'";
		
		if ($name || $type) {
			$query = $query." AND";
		}
	}
	
	if ($name) {
		$query = $query." dc:title LIKE '$name%'";
		
		if ($type) {
			$query = $query." AND";
		}
	}
	
	if ($type) {
		$query = $query." ecm:primaryType = '$type'";
	}
	
	$query = $query." AND ecm:mixinType != 'Folderish' AND ecm:mixinType != 'HiddenInNavigation' AND ecm:isCheckedInVersion = 0 AND ecm:currentLifeCycleState != 'deleted'";
	
	$msg = $msg . trace_nuxeo_query($query, $display_nxql);
	
	$docList = $nxsession->newRequest("Document.Query")->set('params', 'query', "$query")->setSchema($schema = '*')->sendRequest();
	$docArray = $docList->getDocumentList();
	$value = sizeof($docArray);
	$msg = $msg.getDisplayHeader();
	
	for ($test = 0; $test < $value; $test++) {
		$msg = $msg . display_nuxeo_object(current($docArray));
		next($docArray);
	}
	$msg = $msg . getDisplayFooter();

	return $msg;
}

/**
 * Function that display the detail of the current NXQL Query if the trace option is activated
 * @param unknown $query NXQL Query to display
 * @param unknown $display_nxql Trace option
 */
function trace_nuxeo_query($query, $display_nxql) {
	if ($display_nxql != "false") {
		$trace = $trace . '<p><font color=\'red\'>Nuxeo Query : ' . $query . '</font></p>';
	} else {
		$trace = "";
	}
	
	return $trace;
}

function getDisplayHeader() {
	$header = $header . '<table border=\'1\'>';
	$header = $header.'<tr bgcolor=\'#0084C3\'>';
	$header = $header.'<th>Doc</th><th>Creator</th><th>Creation date</th><th>Type</th>';
	$header = $header.'</tr>';	
	
	return $header;
}

function getDisplayFooter() {
	$footer = $footer . '</table>';
	
	return $footer;
}

function display_nuxeo_object($nxDoc) {
	$nxUtils = new NuxeoUtilities();
	$phpDate = $nxUtils->dateConverterNuxeoToPhp($nxDoc->getProperty('dc:created'));
	$nxUrl = get_nxdoc_url($nxDoc);
	
	$msg = $msg . '<tr>';
	$msg = $msg . '<td><a href="'.$nxUrl.'">'.$nxDoc->getTitle().'</a></td>';
	$msg = $msg . '<td>'.$nxDoc->getProperty('dc:creator').'</td>';
	$msg = $msg . '<td>'.$phpDate->format('d-m-Y').'</td>';
	$msg = $msg . '<td>'.$nxDoc->getType().'</td>';
	$msg = $msg . '</tr>';
	
	return $msg;
}

function get_nxdoc_url($nxDoc) {
	$siteurl = get_option('siteurl');
    $id = $nxDocUrl.$nxDoc->getUid();
	$fileName=$nxDoc->getProperty('file:filename');
    return $siteurl . '?nx_doc_id=' . urlencode($id).'&nx_file_name='.$fileName;
}

?>