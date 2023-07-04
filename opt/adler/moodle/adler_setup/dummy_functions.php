<?php
## define dummy function for syntax highlighting
// @formatter:off
if (!function_exists('cli_writeln')) {
    define('MOODLE_OFFICIAL_MOBILE_SERVICE', 'moodle_mobile_app');
    function cli_writeln($text, $stream = STDOUT) {}
    function cli_error($text, $errorcode = 1) {}
    function cli_get_params(array $longoptions, array $shortmapping = null): object {return (object)[];}
    function set_config($name, $value, $plugin = null) {}
    function get_config($plugin, $name = null): mixed {return '';}
    function user_create_user($user, $updatepassword = true, $triggerevent = true): int {return 1;}
    function profile_save_data(stdClass $usernew): void {}
    function get_complete_user_data($field, $value, $mnethostid = null, $throwexception = false): mixed {return '';}
    function role_assign($roleid, $userid, $contextid, $component = '', $itemid = 0, $timemodified = '') {}
}
// @formatter:on