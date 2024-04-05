<?php
# requirements
# - curl
# - unzip

define('CLI_SCRIPT', true);

$config_php_path = __DIR__ . '/../config.php';


require_once($config_php_path);
require_once('dependencies.php');

global $CFG, $DB;

require_once($CFG->libdir . "/clilib.php");
require_once("lib.php");


## cli opts
$help = "Command line tool to uninstall plugins.

Options:
    -h --help                             Print this help.
    --plugin_version                      Version of AdLer plugins to install. main or exact release name. Defaults to main.
    --develop_dont_install_plugins        DEVELOP OPTION: Skip plugin installation
";

list($options, $unrecognised) = cli_get_params([
    'help' => false,
    'plugin_version' => 'main',
    'develop_dont_install_plugins' => false,
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
$options['develop_dont_install_plugins'] = $options['develop_dont_install_plugins'] == "true";
## end cli opts
cli_writeln('CLI options: ' . json_encode($options));


// validate plugin version
if (version_compare($options['plugin_version'], $MINIMUM_PLUGIN_RELEASE_SET, '<') && $options['plugin_version'] !== 'main') {
    cli_error("Plugin version is too low. Minimum required version is $MINIMUM_PLUGIN_RELEASE_SET");
}


function get_plugin_config() {
    $url = __DIR__ . '/plugin-releases.json';
//    $url = 'https://raw.githubusercontent.com/Glutamat42/moodle-docker/main/plugin-releases.json';
    $file_content = file_get_contents($url);
    return json_decode($file_content, true);
}


if ($options['develop_dont_install_plugins']) {
    cli_writeln("skipping plugin installation");
} else {
    cli_writeln("installing plugins");

    $plugin_release_info = get_plugin_config();

    $plugins = [];
    if (isset($plugin_release_info['common_versions'][$options['plugin_version']])) {
        foreach ($plugin_release_info['common_versions'][$options['plugin_version']] as $plugin) {
            $path = $CFG->dirroot . $plugin['path'];

            if (preg_match('/^[0-9]+(\.[0-9]+){0,2}(-rc(\.[0-9]+)?)?$/', $plugin['version'])) {
                // plugin is a release
                $info = get_updated_release_info(
                    $plugin['git_project'],
                    $plugin['version'],
                    core_plugin_manager::instance()->get_plugin_info($plugin['name'])->release
                );

                if ($info === false) {
                    cli_writeln("No update available for {$plugin['name']} {$plugin['version']}");
                    continue;
                } else if ($info !== null && property_exists($info, 'tag_name')) {
                    // checking for one of the keys is sufficient
                    $url = $info->zip_url;
                } else {
                    cli_error("Failed to get release info for {$plugin['name']} {$plugin['version']}");
                }
            } else {
                // plugin is a branch
                $url = "https://github.com/" . $plugin['git_project'] . "/archive/refs/heads/" . $plugin['version'] . ".zip";
            }

            /** @noinspection PhpUndefinedVariableInspection */
            $plugins[] = [
                "path" => $path,
                "url" => $url
            ];
        }
    } else {
        cli_error("plugin version not found");
    }

    cli_writeln("plugins to install: " . json_encode($plugins));
    foreach ($plugins as $plugin) {
        update_plugin($plugin);
    }
}

// upgrade moodle installation
cli_writeln("Upgrading moodle installation...");
$cmd = "php {$CFG->dirroot}/admin/cli/upgrade.php --non-interactive --allow-unstable";
cli_writeln("Executing: $cmd");
exec($cmd, $blub, $result_code);
if ($result_code != 0) {
    cli_error('command execution failed');
}
