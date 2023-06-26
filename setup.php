<?php
# requirements
# - curl
# - unzip

# todo no cli logs
# todo: after script: Invalid permissions detected when trying to create a directory. Turn debugging on for further details.

define('CLI_SCRIPT', true);

$config_php_path = __DIR__ . '/config.php';
//$config_php_path = __DIR__ . '/../../../config.php';

require_once($config_php_path);
require_once($CFG->libdir . "/clilib.php");
require_once($CFG->libdir . "/moodlelib.php");

## define dummy function for syntax highlighting
if (!function_exists('cli_writeln')) {
    function cli_writeln($text, $stream=STDOUT) {}
    function cli_error($text, $errorcode=1){}
    function cli_get_params(array $longoptions, array $shortmapping=null): object {return (object)[];}
    function set_config($name, $value, $plugin = null) {}
    define('MOODLE_OFFICIAL_MOBILE_SERVICE', 'moodle_mobile_app');
}


## cli opts
$help = "Command line tool to uninstall plugins.

Options:
    -h --help                   Print this help.
    --first_run                 Set this flag if this script is run the first time
";

list($options, $unrecognised) = cli_get_params([
    'help' => false,
    'first_run' => false,
], []);

if ($unrecognised) {
    $unrecognised = implode(PHP_EOL.'  ', $unrecognised);
    cli_error('unknown option(s):' . $unrecognised);
}

if ($options['help']) {
    cli_writeln($help);
    exit(0);
}
## end cli opts


// add "$CFG->enablewebservices = true;" to config.php
if ($options['first_run']) {
//    if (is_writable($config_php_path)) {
//        file_put_contents($config_php_path, "\n\$CFG->enablewebservices = true;\n", FILE_APPEND);
//    } else {
//        cli_error('file is not writeable ' . $config_php_path);
//    }
    // enable webservices
    set_config('enablewebservices', true);

    // enable moodle mobile web service
//    set_config('enablemobilewebservice', true);  // for any reason this does not set the corresponding option in the web ui and everything seems to work without it anyway.
    $external_service_record = $DB->get_record('external_services', array('shortname' => MOODLE_OFFICIAL_MOBILE_SERVICE));
    $external_service_record->enabled = 1;
    $DB->update_record('external_services', $external_service_record);

    // Enable REST server.
    $activeprotocols = empty($CFG->webserviceprotocols) ? array() : explode(',', $CFG->webserviceprotocols);

    if (!in_array('rest', $activeprotocols)) {
        $activeprotocols[] = 'rest';
        $updateprotocol = true;
    }

    if ($updateprotocol) {
        set_config('webserviceprotocols', implode(',', $activeprotocols));
    }

    // enable login for webservices other than moodle mobile service
    $cap = new stdClass();
    $cap->contextid    = 1;  // no idea what this is for, but it seems this is always 1
    $cap->roleid       = 7;  // TODO get this somehow
    $cap->capability   = 'moodle/webservice:createtoken';
    $cap->permission   = 1;  // no idea what this is for, but it seems this is always 1
    $cap->timemodified = time();
    $cap->modifierid   = 0;
    $DB->insert_record('role_capabilities', $cap);
}


// install plugins
$plugins = [
    [
        "path" => "local/adler",
        "url" => "https://github.com/ProjektAdLer/MoodlePluginLocal/archive/refs/heads/main.zip"
    ],
    [
        "path" => "availability/condition/adler",
        "url" => "https://github.com/ProjektAdLer/MoodlePluginAvailability/archive/refs/heads/main.zip"
    ],
];
foreach ($plugins as $plugin) {
    $plugin_path = $CFG->dirroot . DIRECTORY_SEPARATOR . $plugin["path"];


    if (is_dir($plugin_path)) {
        // delete plugin folder
        cli_writeln("Plugin already installed, updating...");
        $cmd = "rm -rf $plugin_path";
        cli_writeln("Executing: $cmd");
        exec($cmd, $blub,$result_code);
        if ($result_code!=0) {
            cli_error('command execution failed');
        }
    }
    cli_writeln("Installing plugin...");
    $cmd = "mkdir /tmp/plugin && curl -L {$plugin['url']} -o /tmp/plugin/plugin.zip && unzip /tmp/plugin/plugin.zip -d /tmp/plugin/ && rm /tmp/plugin/plugin.zip && mv /tmp/plugin/* $plugin_path && rm -r /tmp/plugin";
    cli_writeln("Executing: $cmd");
    exec($cmd);
}
// upgrade moodle installation
cli_writeln("Upgrading moodle installation...");
$cmd = "php {$CFG->dirroot}/admin/cli/upgrade.php --non-interactive --allow-unstable";
cli_writeln("Executing: $cmd");
exec($cmd, $blub,$result_code);
if ($result_code!=0) {
    cli_error('command execution failed');
}



