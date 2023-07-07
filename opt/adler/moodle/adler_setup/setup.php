<?php
# requirements
# - curl
# - unzip

define('CLI_SCRIPT', true);

$config_php_path = __DIR__ . '/../config.php';


require_once($config_php_path);

global $CFG, $DB;

require_once($CFG->libdir . "/clilib.php");
require_once($CFG->libdir . "/moodlelib.php");
require_once($CFG->libdir . "/externallib.php");
require_once($CFG->libdir . "/accesslib.php");
require_once("lib.php");


## cli opts
$help = "Command line tool to uninstall plugins.

Options:
    -h --help           Print this help.
    --first_run         Set this flag if this script is run the first time
    --plugin_version    Version of AdLer plugins to install. main or exact release name. Defaults to main.
    --user_name         Plain user that will be created during first_run. This user does not have any special permissions, it will be a default \"student\". This field will be the login name and used as default value for optional fields. name and password parameters are required if this user should be created. This is a comma separated list. To add multiple users use for example --user_name=user1,user,user3. All used switches has to have the same array length. Use false if you want the default behavior (eg --user_first_name=John,false,Peter)
    --user_password     Passwords are not allowed to contain \",\". Passwords have to follow moodle password validation rules.
    --user_first_name
    --user_last_name
    --user_email
    --user_role         shortname of one role
";

list($options, $unrecognised) = cli_get_params([
    'help' => false,
    'first_run' => false,
    'plugin_version' => 'main',
    'user_name' => false,
    'user_password' => false,
    'user_first_name' => false,
    'user_last_name' => false,
    'user_email' => false,
    'user_role' => false,
], []);

if ($unrecognised) {
    $unrecognised = implode(PHP_EOL . '  ', $unrecognised);
    cli_error('unknown option(s):' . $unrecognised);
}


if ($options['help']) {
    cli_writeln($help);
    exit(0);
}

$options['first_run'] = $options['first_run'] == "true";
## end cli opts
cli_writeln('CLI options: ' . json_encode($options));

if ($options['first_run']) {
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
    $cap->contextid = 1;  // no idea what this is for, but it seems this is always 1
    $cap->roleid = $role_id = $DB->get_record('role', array('shortname' => 'user'))->id;  # role id of "authenticated user"
    $cap->capability = 'moodle/webservice:createtoken';
    $cap->permission = 1;  // no idea what this is for, but it seems this is always 1
    $cap->timemodified = time();
    $cap->modifierid = 0;
    $DB->insert_record('role_capabilities', $cap);

    // enable rest:use for webservices other than moodle mobile service
    $cap = new stdClass();
    $cap->contextid = 1;  // no idea what this is for, but it seems this is always 1
    $cap->roleid = $role_id = $DB->get_record('role', array('shortname' => 'user'))->id;  # role id of "authenticated user"
    $cap->capability = 'webservice/rest:use';
    $cap->permission = 1;  // no idea what this is for, but it seems this is always 1
    $cap->timemodified = time();
    $cap->modifierid = 0;
    $DB->insert_record('role_capabilities', $cap);

    // create users
    if ($options['user_name']) {
        cli_writeln("creating user(s)");
        create_users($options);
    }
}

// install plugins
if ($options['plugin_version'] == 'main') {
    $plugins = [
        [
            "path" => $CFG->dirroot . "/local/adler",
            "url" => "https://github.com/ProjektAdLer/MoodlePluginLocal/archive/refs/heads/main.zip"
        ],
        [
            "path" => $CFG->dirroot . "/availability/condition/adler",
            "url" => "https://github.com/ProjektAdLer/MoodlePluginAvailability/archive/refs/heads/main.zip"
        ],
    ];
} else {
    $plugins = [];
    $info = get_updated_release_info(
        "ProjektAdLer/MoodlePluginLocal",
        $options['plugin_version'],
        core_plugin_manager::instance()->get_plugin_info('local_adler')->release
    );
    if ($info) {
        $plugins[] = [
            "path" => $CFG->dirroot . "/local/adler",
            "url" => $info->zip_url
        ];
    }
    $info = get_updated_release_info(
        "ProjektAdler/MoodlePluginAvailability",
        $options['plugin_version'],
        core_plugin_manager::instance()->get_plugin_info('local_adler')->release
    );
    if ($info) {
        $plugins[] = [
            "path" => $CFG->dirroot . "/availability/condition/adler",
            "url" => $info->zip_url
        ];
    }
}
//echo json_encode($plugins);
foreach ($plugins as $plugin) {
    update_plugin($plugin);
}


// upgrade moodle installation
cli_writeln("Upgrading moodle installation...");
$cmd = "php {$CFG->dirroot}/admin/cli/upgrade.php --non-interactive --allow-unstable";
cli_writeln("Executing: $cmd");
exec($cmd, $blub, $result_code);
if ($result_code != 0) {
    cli_error('command execution failed');
}
