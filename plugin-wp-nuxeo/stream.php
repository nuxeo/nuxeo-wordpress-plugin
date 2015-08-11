<?php
/*
 * (C) Copyright 2015 Nuxeo SA (http://nuxeo.com/) and others.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * Contributors:
 *     Laurent Dreuillat
 */

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
