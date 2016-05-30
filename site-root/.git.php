<?php

// authenticate developer
if ($GLOBALS['Session']->hasAccountLevel('Developer')) {
    $User = $GLOBALS['Session']->Person;
} else {
    $authEngine = new \Sabre\HTTP\BasicAuth();
    $authEngine->setRealm('Develop '.\Site::$title);
    $authUserPass = $authEngine->getUserPass();

    // try to get user
    $userClass = User::$defaultClass;
    $User = $userClass::getByLogin($authUserPass[0], $authUserPass[1]);

    // send auth request if login is inadiquate
    if (!$User || !$User->hasAccountLevel('Developer')) {
        $authEngine->requireLogin();
        die("You must login using a ".\Site::getConfig('primary_hostname')." account with Developer access\n");
    }
}


// initialize git repository
$repoPath = Site::$rootPath . '/site-data/site.git';

if (!is_dir($repoPath)) {
    exec("git init --bare $repoPath");
}


// create git-http-backend process
$pipes = [];
$process = proc_open(
    exec('which git') . ' http-backend',
    [
		0 => ['pipe', 'rb'], // STDIN
		1 => ['pipe', 'wb'], // STDOUT
		2 => ['pipe', 'w']  // STDERR
    ],
    $pipes,
    null,
    [
        'GIT_HTTP_EXPORT_ALL' => 1,
        'GIT_PROJECT_ROOT' => $repoPath,
        'PATH_INFO' => '/' . implode('/', Site::$pathStack),
        'REMOTE_USER' => $_SERVER['REMOTE_USER'],
        'REMOTE_ADDR' => $_SERVER['REMOTE_ADDR'],
        'CONTENT_TYPE' => $_SERVER['CONTENT_TYPE'],
        'QUERY_STRING' => $_SERVER['QUERY_STRING'],
        'REQUEST_METHOD' => $_SERVER['REQUEST_METHOD'],
        'HTTP_ACCEPT', $_SERVER['HTTP_ACCEPT']
    ]
);


// copy POST body to STDIN
$inputStream = fopen('php://input', 'rb');
stream_copy_to_stream($inputStream, $pipes[0]);
fclose($inputStream);
fclose($pipes[0]);


// check for error on STDERR and turn into exception
stream_set_blocking($pipes[2], false);
$error = stream_get_contents($pipes[2]);
fclose($pipes[2]);

if ($error) {
    $exitCode = proc_close($process);
    throw new \Exception("git exited with code $exitCode: $error");
}


// read and set headers first
$headers = [];
while ($header = trim(fgets($pipes[1]))) {
    header($header, true);
}


// pass remaining output through to client
fpassthru($pipes[1]);
fclose($pipes[1]);


// clean up
proc_close($process);
exit();
