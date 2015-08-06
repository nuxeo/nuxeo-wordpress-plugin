<?php
add_action('plugins_loaded', 'nuxeo_download');

function nuxeo_download() {
    $nx_doc_id = $_GET['nx_doc_id'];
	$nx_file_name = $_GET['nx_file_name'];
	
	// Initialize Nuxeo Client
	$repo_url = get_option('nx_repository_url');
	$repo_username = get_option('nx_username');
	$repo_password = get_option('nx_password');
	
    if($nx_doc_id != '')
    {
        $nxclient = new NuxeoPhpAutomationClient($repo_url.'/site/automation');
		$nxsession = $nxclient->getSession($repo_username, $repo_password);
	
		$answer = $nxsession->newRequest("Blob.Get")->set('input', 'doc:'.$nx_doc_id)->sendRequest();
		if (!isset($answer) OR $answer == false) {
		 	echo "No file found";
		} else {
		 	header('Content-Description: FileTransfer');
		 	header('Content-Type: application/octet-stream');
		 	header('Content-Disposition: attachment; filename='.$nx_file_name.'');
		 	readfile('tempstream');
		}

        echo $content;
    }
}
?>
