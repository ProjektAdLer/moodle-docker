<?php
define('CLI_SCRIPT', true);

$config_php_path = __DIR__ . '/../config.php';


require_once($config_php_path);

global $CFG, $DB;

require_once($CFG->libdir . "/clilib.php");
require_once($CFG->libdir . "/moodlelib.php");
require_once($CFG->libdir . "/accesslib.php");
require_once("lib.php");


## cli opts
$help = "Command line tool to uninstall plugins.

Options:
    -h --help                             Print this help.
    --first_run                           Set this flag if this script is run the first time
    --user_name                           Comma seperated list of users that will be created during first_run. Per default the users do not have any special permissions, it will be a default \"student\". This field will be the login name and used as default value for optional fields. Name and password parameters are required if this user should be created. To add multiple users use for example --user_name=user1,user,user3. All used switches have to have the same array length. Use false if you want the default behavior (eg --user_first_name=John,false,Peter)
    --user_password                       Passwords are not allowed to contain \",\". Passwords have to follow moodle password validation rules.
    --user_first_name                     See user_name for further information.
    --user_last_name                      See user_name for further information.
    --user_email                          See user_name for further information.
    --user_role                           shortname of role. See user_name for further information.
    --user_create_adler_course_category   true: create a course category for the user and assign the account to the adler_manager role in the context of this course category. See user_name for further information.
";

list($options, $unrecognised) = cli_get_params([
    'help' => false,
    'first_run' => false,
    'user_name' => false,
    'user_password' => false,
    'user_first_name' => false,
    'user_last_name' => false,
    'user_email' => false,
    'user_role' => false,
    'user_create_adler_course_category' => false,
], []);

if ($unrecognised) {
    $unrecognised = implode(PHP_EOL . '  ', $unrecognised);
    cli_error('unknown option(s):' . $unrecognised);
}

if ($options['help']) {
    cli_writeln($help);
    exit(0);
}

# cast boolean cli opts
$options['first_run'] = $options['first_run'] == "true";
## end cli opts
cli_writeln('CLI options: ' . json_encode((object) array_merge((array) $options, ['user_password' => '***'])));


function do_adler_roles_exist() {
    $roles = get_all_roles();
    $filtered_roles = array_filter($roles, function($role) {
        return $role->name == 'adler_manager';
    });

    return !empty($filtered_roles);
}

// check if adler roles already exist
if (do_adler_roles_exist()) {
    cli_writeln("adler roles already exist");
} else {
    // create adler role
    $role_adler_manager_id = create_role('adler_manager', 'adler_manager', 'Manager for adler courses. Has all permissions required to work with the authoring tool.', 'user');
    $capabilities_adler_manager = [
        'moodle/course:delete',
        'moodle/course:enrolconfig',
        'moodle/question:add',
        'moodle/question:managecategory',
        'moodle/restore:configure',
        'moodle/restore:restoreactivity',
        'moodle/restore:restorecourse',
        'moodle/restore:restoresection',
        'moodle/restore:restoretargetimport',
        'moodle/restore:rolldates',
        'moodle/restore:uploadfile',
        'moodle/restore:userinfo',
        'moodle/restore:viewautomatedfilearea'
    ];
    foreach ($capabilities_adler_manager as $capability) {
        assign_capability($capability, CAP_ALLOW, $role_adler_manager_id, context_system::instance());
    }
    // set context levels where the role can be assigned
    $contextlevels = [
        CONTEXT_COURSECAT,
    ];
    set_role_contextlevels($role_adler_manager_id, $contextlevels);
}


if ($options['first_run']) {
    // enable webservices
    set_config('enablewebservices', true);

    // enable moodle mobile web service
    // TODO: we are not using moodle official mobile service anymore
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