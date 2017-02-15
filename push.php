<?php
//error_reporting(E_ALL);

function fetch($url)
{
	$curl = curl_init();
	file_put_contents('/tmp/webhook.log', 'My hook url: ' . $url . PHP_EOL, FILE_APPEND);
	curl_setopt($curl, CURLOPT_URL, $url );
	$head = curl_exec($curl);
	$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
	curl_close($curl);

	return ($httpCode);
}

// MAIN
$key = 'someKey';
$ip = "xxx.xxx.xxx.xxx";

file_put_contents('/tmp/webhook.log','Request on ' . date("F j, Y, g:i a") . ' from ' . $_SERVER['REMOTE_ADDR'] . PHP_EOL, FILE_APPEND);

$json= file_get_contents('php://input');
//file_put_contents('/tmp/webhook.log', PHP_EOL . $json, FILE_APPEND);
$jsarr=json_decode($json,true);
//file_put_contents('/tmp/webhook.log', PHP_EOL . print_r($jsarr,true), FILE_APPEND);
$branch=$jsarr["ref"];
$user_name=$jsarr["user_name"];
$user_email=$jsarr["user_email"];
$prj_arr=$jsarr["project"];

$git_ssh_url=$prj_arr["git_ssh_url"];
$project_name=$prj_arr["name"];

file_put_contents('/tmp/webhook.log', '----WakeUp---- ' . $branch . PHP_EOL, FILE_APPEND);
file_put_contents('/tmp/webhook.log', 'Project Name= ' . $project_name . PHP_EOL, FILE_APPEND);
file_put_contents('/tmp/webhook.log', 'Branch= ' . $branch . PHP_EOL, FILE_APPEND);
file_put_contents('/tmp/webhook.log', 'User= ' . $user_name . PHP_EOL, FILE_APPEND);
file_put_contents('/tmp/webhook.log', 'User Email= ' . $user_email . PHP_EOL, FILE_APPEND);
file_put_contents('/tmp/webhook.log', 'git_ssh_url= ' . $git_ssh_url . PHP_EOL, FILE_APPEND);

// Watch for develop/stage/master only:
switch ($branch) {
	case "refs/heads/master":
		echo "Master branch";
		break;
	case "refs/heads/stage":
		echo "Stage branch";
		break;
	case "refs/heads/develop":
		echo "Develop branch";
		break;
	default:
		echo "$branch branch not tracking";
		file_put_contents('/tmp/webhook.log', 'Not for tracking branch: ' . $branch . PHP_EOL, FILE_APPEND);

		die();
}

$dbfilepath="map.sqlite";

$db = new SQLite3($dbfilepath); $db->busyTimeout(5000);
$sql = "SELECT jenkins_url FROM map WHERE git_url=\"$git_ssh_url\" AND branch=\"$branch\"";

file_put_contents('/tmp/webhook.log', 'SQL str: ' . $sql . PHP_EOL, FILE_APPEND);

$targetres = $db->query($sql);

$hook_user="jenkins";
$hook_token="HOOKTOKEN";
$token_name="GITLAB TOKEN";

$known_route=0;

if (!($targetres instanceof Sqlite3Result)) {
	echo "Error: $dbfilepath";
	file_put_contents('/tmp/webhook.log', 'SQLite error for ' . $dbfilepath . PHP_EOL, FILE_APPEND);
	system("/var/www/jpush.my.domain/current/notify-by-slack.sh \"jpush: SQLite error for \"" . $dbfilepath);
	die();
}

while ($row = $targetres->fetchArray()) {
	list( $jenkins_url ) = $row;
	$myurl="https://".$hook_user.":".$hook_token."@jenkins.my.domain/job/".$jenkins_url."/build?token=".$token_name;
	file_put_contents('/tmp/webhook.log', 'route found in map: ' . $myurl . PHP_EOL, FILE_APPEND);
	$known_route=1;
}


if ( $known_route == 0 ) {
//	system("/var/www/jpush.my.domain/current/notify-by-slack.sh \"jpush debug: no route for [$git_ssh_url][$branch], use default\"",$retval);
	$branch_without_refs=str_replace("refs/heads/","",$branch);

	$tmp_int_project=str_replace("git@gitlab.my.domain:"," ",$git_ssh_url);
	$tmp_int_project=str_replace("/"," ",$tmp_int_project);
	sscanf($tmp_int_project,"%s",$int_project);

	//if branch = "develop" lets try to push testing first. If not 2xx code - kick original url
	if (!strcmp($branch_without_refs,"develop")) {
		$jenkins_url=$project_name."-test";
		$myurl="https://".$hook_user.":".$hook_token."@jenkins.my.domain/job/".$int_project."-".$jenkins_url."/build?token=".$token_name;

		$httpCode=fetch($myurl);

		if ($httpCode==201) {
			exit(0);
		}

		system("/var/www/jpush.my.domain/current/notify-by-slack.sh \"jpush: test job not found for [$git_ssh_url], force to [$branch] job\"",$retval);
		$jenkins_url=$project_name."-".$branch_without_refs;
	} else {
		$jenkins_url=$project_name."-".$branch_without_refs;
	}

	$myurl="https://".$hook_user.":".$hook_token."@jenkins.my.domain/job/".$int_project."-".$jenkins_url."/build?token=".$token_name;
	file_put_contents('/tmp/webhook.log', 'route not found in map, use default: ' . $myurl . PHP_EOL, FILE_APPEND);
}

$httpCode=fetch($myurl);

if ($httpCode!=201) {
	system("/var/www/jpush.my.domain/current/notify-by-slack.sh \"jpush: not 201 http code for $myurl [$git_ssh_url][$branch][code: $httpCode]\"",$retval);
}


// Pushed to master?
//if ($branch === 'refs/heads/master')
//{
//exec("/tmp/webhook.sh");
//}
?>
