<?php

global $CFG;
require_once($CFG->dirroot . '/user/profile/lib.php');
require_once($CFG->dirroot . '/user/lib.php');

use local_adler\plugin_interface;
use core\event\user_created;


/** Get Infos (incl download urls) of release to update to. Will return false if nothing to update
 * @param string $github_repo GitHub repo of the plugin, <group>/<repo>
 * @param string $version Version pattern to match against. Eg 1 will match all Releases 1.x.y, 2.7 will match 2.7.x
 * @param string|null $old_version Current version
 * @return object|false
 */
function get_updated_release_info(string $github_repo, string $version, string $old_version = null) {
    cli_writeln("Params for get_updated_release_info: github_repo=$github_repo, version=$version, old_version=$old_version");

    $url = "https://api.github.com/repos/" . $github_repo . "/releases";
    cli_writeln("Fetching release info from $url");

    $context = stream_context_create(['http' => ['user_agent' => 'PHP']]);
    $response = file_get_contents($url, false, $context);
//    $response = "[{\"url\":\"https://api.github.com/repos/ProjektAdLer/MoodlePluginLocal/releases/108886375\",\"assets_url\":\"https://api.github.com/repos/ProjektAdLer/MoodlePluginLocal/releases/108886375/assets\",\"upload_url\":\"https://uploads.github.com/repos/ProjektAdLer/MoodlePluginLocal/releases/108886375/assets{?name,label}\",\"html_url\":\"https://github.com/ProjektAdLer/MoodlePluginLocal/releases/tag/1.0.0\",\"id\":108886375,\"author\":{\"login\":\"Glutamat42\",\"id\":51326311,\"node_id\":\"MDQ6VXNlcjUxMzI2MzEx\",\"avatar_url\":\"https://avatars.githubusercontent.com/u/51326311?v=4\",\"gravatar_id\":\"\",\"url\":\"https://api.github.com/users/Glutamat42\",\"html_url\":\"https://github.com/Glutamat42\",\"followers_url\":\"https://api.github.com/users/Glutamat42/followers\",\"following_url\":\"https://api.github.com/users/Glutamat42/following{/other_user}\",\"gists_url\":\"https://api.github.com/users/Glutamat42/gists{/gist_id}\",\"starred_url\":\"https://api.github.com/users/Glutamat42/starred{/owner}{/repo}\",\"subscriptions_url\":\"https://api.github.com/users/Glutamat42/subscriptions\",\"organizations_url\":\"https://api.github.com/users/Glutamat42/orgs\",\"repos_url\":\"https://api.github.com/users/Glutamat42/repos\",\"events_url\":\"https://api.github.com/users/Glutamat42/events{/privacy}\",\"received_events_url\":\"https://api.github.com/users/Glutamat42/received_events\",\"type\":\"User\",\"site_admin\":false},\"node_id\":\"RE_kwDOIidAYc4GfXln\",\"tag_name\":\"1.0.0\",\"target_commitish\":\"main\",\"name\":\"v1.0.0\",\"draft\":false,\"prerelease\":false,\"created_at\":\"2023-06-16T14:30:58Z\",\"published_at\":\"2023-06-16T14:50:20Z\",\"assets\":[],\"tarball_url\":\"https://api.github.com/repos/ProjektAdLer/MoodlePluginLocal/tarball/1.0.0\",\"zipball_url\":\"https://api.github.com/repos/ProjektAdLer/MoodlePluginLocal/zipball/1.0.0\",\"body\":\"This initial release of the project supports both Moodle 4.2 and Moodle 4.1 (LTS) versions. It introduces essential functionality to enhance the project's capabilities. Key highlights of this release include:\\r\\n\\r\\n- Compatibility with Moodle 4.2 and Moodle 4.1 (LTS)\\r\\n- API endpoints for integration with external systems (backend)\\r\\n- Backup and restore functionality\\r\\n- Plugin interface for clear communication functions between plugins (availability_adler)\\r\\n- Documentation\"}]";
//    $response = "[\n  {\n    \"url\": \"https://api.github.com/repos/ProjektAdLer/MoodlePluginLocal/releases/108886375\",\n    \"assets_url\": \"https://api.github.com/repos/ProjektAdLer/MoodlePluginLocal/releases/108886375/assets\",\n    \"upload_url\": \"https://uploads.github.com/repos/ProjektAdLer/MoodlePluginLocal/releases/108886375/assets{?name,label}\",\n    \"html_url\": \"https://github.com/ProjektAdLer/MoodlePluginLocal/releases/tag/1.0.0\",\n    \"id\": 108886375,\n    \"node_id\": \"RE_kwDOIidAYc4GfXln\",\n    \"tag_name\": \"1.0.0\",\n    \"target_commitish\": \"main\",\n    \"name\": \"v1.0.0\",\n    \"draft\": false,\n    \"prerelease\": false,\n    \"created_at\": \"2023-06-16T14:30:58Z\",\n    \"published_at\": \"2023-06-16T14:50:20Z\",\n    \"assets\": [],\n    \"tarball_url\": \"https://api.github.com/repos/ProjektAdLer/MoodlePluginLocal/tarball/1.0.0\",\n    \"zipball_url\": \"https://api.github.com/repos/ProjektAdLer/MoodlePluginLocal/zipball/1.0.0\"\n  },\n  {\n    \"url\": \"https://api.github.com/repos/ProjektAdLer/MoodlePluginLocal/releases/108886375\",\n    \"assets_url\": \"https://api.github.com/repos/ProjektAdLer/MoodlePluginLocal/releases/108886375/assets\",\n    \"upload_url\": \"https://uploads.github.com/repos/ProjektAdLer/MoodlePluginLocal/releases/108886375/assets{?name,label}\",\n    \"html_url\": \"https://github.com/ProjektAdLer/MoodlePluginLocal/releases/tag/1.0.0\",\n    \"id\": 108886375,\n    \"node_id\": \"RE_kwDOIidAYc4GfXln\",\n    \"tag_name\": \"1.0.3\",\n    \"target_commitish\": \"main\",\n    \"name\": \"v1.0.3\",\n    \"draft\": false,\n    \"prerelease\": false,\n    \"created_at\": \"2023-06-16T14:30:58Z\",\n    \"published_at\": \"2023-06-16T14:50:20Z\",\n    \"assets\": [],\n    \"tarball_url\": \"https://api.github.com/repos/ProjektAdLer/MoodlePluginLocal/tarball/1.0.0\",\n    \"zipball_url\": \"https://api.github.com/repos/ProjektAdLer/MoodlePluginLocal/zipball/1.0.0\"\n  },\n  {\n    \"url\": \"https://api.github.com/repos/ProjektAdLer/MoodlePluginLocal/releases/108886375\",\n    \"assets_url\": \"https://api.github.com/repos/ProjektAdLer/MoodlePluginLocal/releases/108886375/assets\",\n    \"upload_url\": \"https://uploads.github.com/repos/ProjektAdLer/MoodlePluginLocal/releases/108886375/assets{?name,label}\",\n    \"html_url\": \"https://github.com/ProjektAdLer/MoodlePluginLocal/releases/tag/1.0.0\",\n    \"id\": 108886375,\n    \"node_id\": \"RE_kwDOIidAYc4GfXln\",\n    \"tag_name\": \"2.0.0\",\n    \"target_commitish\": \"main\",\n    \"name\": \"v2.0.0\",\n    \"draft\": false,\n    \"prerelease\": false,\n    \"created_at\": \"2023-06-16T14:30:58Z\",\n    \"published_at\": \"2023-06-16T14:50:20Z\",\n    \"assets\": [],\n    \"tarball_url\": \"https://api.github.com/repos/ProjektAdLer/MoodlePluginLocal/tarball/1.0.0\",\n    \"zipball_url\": \"https://api.github.com/repos/ProjektAdLer/MoodlePluginLocal/zipball/1.0.0\"\n  },\n  {\n    \"url\": \"https://api.github.com/repos/ProjektAdLer/MoodlePluginLocal/releases/108886375\",\n    \"assets_url\": \"https://api.github.com/repos/ProjektAdLer/MoodlePluginLocal/releases/108886375/assets\",\n    \"upload_url\": \"https://uploads.github.com/repos/ProjektAdLer/MoodlePluginLocal/releases/108886375/assets{?name,label}\",\n    \"html_url\": \"https://github.com/ProjektAdLer/MoodlePluginLocal/releases/tag/1.0.0\",\n    \"id\": 108886375,\n    \"node_id\": \"RE_kwDOIidAYc4GfXln\",\n    \"tag_name\": \"2.0.1\",\n    \"target_commitish\": \"main\",\n    \"name\": \"v2.0.1\",\n    \"draft\": false,\n    \"prerelease\": false,\n    \"created_at\": \"2023-06-16T14:30:58Z\",\n    \"published_at\": \"2023-06-16T14:50:20Z\",\n    \"assets\": [],\n    \"tarball_url\": \"https://api.github.com/repos/ProjektAdLer/MoodlePluginLocal/tarball/1.0.0\",\n    \"zipball_url\": \"https://api.github.com/repos/ProjektAdLer/MoodlePluginLocal/zipball/1.0.0\"\n  },\n  {\n    \"url\": \"https://api.github.com/repos/ProjektAdLer/MoodlePluginLocal/releases/108886375\",\n    \"assets_url\": \"https://api.github.com/repos/ProjektAdLer/MoodlePluginLocal/releases/108886375/assets\",\n    \"upload_url\": \"https://uploads.github.com/repos/ProjektAdLer/MoodlePluginLocal/releases/108886375/assets{?name,label}\",\n    \"html_url\": \"https://github.com/ProjektAdLer/MoodlePluginLocal/releases/tag/1.0.0\",\n    \"id\": 108886375,\n    \"node_id\": \"RE_kwDOIidAYc4GfXln\",\n    \"tag_name\": \"2.1.0\",\n    \"target_commitish\": \"main\",\n    \"name\": \"v2.1.0\",\n    \"draft\": false,\n    \"prerelease\": false,\n    \"created_at\": \"2023-06-16T14:30:58Z\",\n    \"published_at\": \"2023-06-16T14:50:20Z\",\n    \"assets\": [],\n    \"tarball_url\": \"https://api.github.com/repos/ProjektAdLer/MoodlePluginLocal/tarball/1.0.0\",\n    \"zipball_url\": \"https://api.github.com/repos/ProjektAdLer/MoodlePluginLocal/zipball/1.0.0\"\n  },\n    {\n    \"url\": \"https://api.github.com/repos/ProjektAdLer/MoodlePluginLocal/releases/108886375\",\n    \"assets_url\": \"https://api.github.com/repos/ProjektAdLer/MoodlePluginLocal/releases/108886375/assets\",\n    \"upload_url\": \"https://uploads.github.com/repos/ProjektAdLer/MoodlePluginLocal/releases/108886375/assets{?name,label}\",\n    \"html_url\": \"https://github.com/ProjektAdLer/MoodlePluginLocal/releases/tag/1.0.0\",\n    \"id\": 108886375,\n    \"node_id\": \"RE_kwDOIidAYc4GfXln\",\n    \"tag_name\": \"3.0.0\",\n    \"target_commitish\": \"main\",\n    \"name\": \"v3.0.0\",\n    \"draft\": false,\n    \"prerelease\": false,\n    \"created_at\": \"2023-06-16T14:30:58Z\",\n    \"published_at\": \"2023-06-16T14:50:20Z\",\n    \"assets\": [],\n    \"tarball_url\": \"https://api.github.com/repos/ProjektAdLer/MoodlePluginLocal/tarball/1.0.0\",\n    \"zipball_url\": \"https://api.github.com/repos/ProjektAdLer/MoodlePluginLocal/zipball/1.0.0\"\n  }\n]";
    cli_writeln("Response: $response");

    $releases = json_decode($response, true);
    $matchingReleases = array_filter($releases, function ($release) use ($version) {
        return strpos($release['tag_name'], $version) === 0;
    });
    cli_writeln("Matching releases: " . json_encode($matchingReleases));

    usort($matchingReleases, function ($a, $b) {
        return version_compare($a['tag_name'], $b['tag_name'], '<=') ? 1 : -1;
    });
    cli_writeln("Sorted matching releases: " . json_encode($matchingReleases));

    $latestRelease = reset($matchingReleases);
    cli_writeln("Latest release: " . json_encode($latestRelease));

    if ($old_version !== null && $latestRelease['tag_name'] === $old_version) {
        return false;
    }

    return (object)[
        'tag_name' => $latestRelease['name'],
        'version' => $latestRelease['tag_name'],
        'zip_url' => $latestRelease['zipball_url'],
        'tar_url' => $latestRelease['tarball_url']
    ];
}

/**
 * @param $plugin object plugin object with "path" and "url"
 * @return void
 */
function update_plugin($plugin) {
    $plugin_path = $CFG->dirroot . DIRECTORY_SEPARATOR . $plugin["path"];

    if (is_dir($plugin_path)) {
        // make writeable
        chmod($plugin_path,0775);
        // delete plugin folder
        cli_writeln("Plugin already installed, updating...");
        $cmd = "rm -rf $plugin_path";
        cli_writeln("Executing: $cmd");
        exec($cmd, $blub, $result_code);
        if ($result_code != 0) {
            cli_error('command execution failed');
        }
    }
    cli_writeln("Installing plugin...");
    // first cleanup in case the folder is left over from a previous failed attempt
    $cmd = "rm -rf /tmp/plugin && mkdir /tmp/plugin && curl -L {$plugin['url']} -o /tmp/plugin/plugin.zip && unzip /tmp/plugin/plugin.zip -d /tmp/plugin/ && rm /tmp/plugin/plugin.zip && mv /tmp/plugin/* $plugin_path && rm -r /tmp/plugin";
    cli_writeln("Executing: $cmd");
    exec($cmd, $blub, $result_code);
    if ($result_code != 0) {
        cli_error('command execution failed');
    }
    // revoke write permissions to prevent update in moodle ui
    chmod($plugin_path, 0555);
}

function create_one_user($username, $password, $first_name, $last_name, $email, $role) {
    // originally taken from moodlelib ~4.2, but not much left of it
    cli_writeln('creating user "' . $username . '"');

    $newuser = new stdClass();
    // Just in case check text case.
    $newuser->username = trim(strtolower($username));
    $newuser->password = $password;
    $newuser->email = $email;
    $newuser->firstname = $first_name;
    $newuser->lastname = $last_name;
    $newuser->auth = 'manual';
    $newuser->lang = 'en';
    $newuser->timecreated = time();
    $newuser->timemodified = $newuser->timecreated;
    $newuser->confirmed = 1;
    $newuser->mnethostid = get_config('core', 'mnet_localhost_id');
    $newuser->description = "Created during setup";

    try {
        $newuser->id = user_create_user($newuser, true, false);
    } catch (Throwable $e) {
        cli_error(json_encode($e));
    }
//    Setting the password this way ignore password validation rules
//    update_internal_user_password($user, $password);

    // Save user profile data.
    profile_save_data($newuser);

    $user = get_complete_user_data('id', $newuser->id);

    // Trigger event.
    user_created::create_from_userid($newuser->id)->trigger();

    // assign user to role
    if ($role) {
        global $DB;
        $role_id = $DB->get_record('role', array('shortname' => $role))->id;
        role_assign($role_id, $user->id, 1);
    }

    cli_writeln('user created');
    return $user;
}

function create_users($options) {
    // parse data, set default values
    $users_data = [
        'name' => explode(',', $options['user_name']),
        'password' => explode(',', $options['user_password']),
    ];
    $users_data['first_name'] = $options['user_first_name'] ? explode(',', $options['user_first_name']) : array_fill(0, count($users_data['name']), "false");
    $users_data['last_name'] = $options['user_last_name'] ? explode(',', $options['user_last_name']) : array_fill(0, count($users_data['name']), "false");
    $users_data['email'] = $options['user_email'] ? explode(',', $options['user_email']) : array_fill(0, count($users_data['name']), "false");
    $users_data['role'] = $options['user_role'] ? explode(',', $options['user_role']) : array_fill(0, count($users_data['name']), "false");
    $users_data['create_adler_course_category'] = $options['user_create_adler_course_category'] ? explode(',', $options['user_create_adler_course_category']) : array_fill(0, count($users_data['name']), "false");

    // trim all values
    foreach (array_keys($users_data) as $key) {
        $users_data[$key] = array_map('trim', $users_data[$key]);
    }

    // in case any value is empty, set to false
    foreach (array_keys($users_data) as $key) {
        $users_data[$key] = array_map(function ($value) {
            return empty($value) ? "false" : $value;
        }, $users_data[$key]);
    }

    // validation
    foreach (array_keys($users_data) as $key) {
        assert(count($users_data['name']) == count($users_data[$key]), 'all user property arrays has to have the same length');
    }


    for ($i = 0; $i < count($users_data['name']); $i++) {
        // for optional fields, fill with default values if not set
        $first_name = $users_data['first_name'][$i] != "false" ? $users_data['first_name'][$i] : $users_data['name'][$i];
        $last_name = $users_data['last_name'][$i] != "false" ? $users_data['last_name'][$i] : $users_data['name'][$i];
        $role = $users_data['role'][$i] == "false" ? false : $users_data['role'][$i];
        $email = $users_data['email'][$i] != "false" ?: $users_data['name'][$i] . '@example.example';

        $user = create_one_user($users_data['name'][$i], $users_data['password'][$i], $first_name, $last_name, $email, $role);

        if ($users_data['create_adler_course_category'][$i] == "true") {
            cli_writeln("creating course category with upload permission for user " . $user->username);
            create_default_course_category_for_user($user->username);
        }
    }
}

function create_default_course_category_for_user($username) {
    // use local_adler cli script
    plugin_interface::create_category_user_can_create_courses_in($username, 'adler_manager');
}
