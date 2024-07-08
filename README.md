# Moodle Bitnami Image Extension - User Creation, PHP Environment Variables, and AdLer Setup

This project extends the bitnami/moodle image with the following features:

- Setting up AdLer (after the first start the Moodle part of AdLer is fully set up).
- Create user(s) on first start
- Adding another environment variable to set a php.ini option.


## Dependencies
- Requires at least Plugin-Release-Set version 3.1.0


## Windows Users

This project works only under Linux.
Git on Windows (also WSL) breaks the line endings which is why it cannot be used there.
Also, editing on Windows can cause the project to stop working on Linux as well.
To use this project on Windows you have to disable the option core.autocrlf
(why the hell does this option exist and why is it enabled by default on Windows...).
To do this run the following command before the git clone `git config --global core.autocrlf false`.

**ATTENTION**: This affects all git repositories on this PC.

If you want to run this project with Windows without disabling autocrlf you can use an Docker-twostage approach.
I will not support this, but this is an approach you could use to implement it by yourself:

```
RUN apk add dos2unix
RUN cat setup.sh | dos2unix > setup.sh.tmp
RUN mv setup.sh.tmp setup.sh
RUN chmod +x setup.sh
```

## Environment variables

All variables from the bitnami/moodle image are supported. Additionally, the following environment variables can be set:

### PHP environment variables

| Variable               | required | Description                                                                                                  |
|------------------------|----------|--------------------------------------------------------------------------------------------------------------|
| `PHP_OUTPUT_BUFFERING` | no       | Controls the output buffering behavior of PHP. Set it to adjust the buffering setting in the `php.ini` file. |

### Moodle user creation variables

| Variable                            | required                    | Description                                                                                                                                                             |
|-------------------------------------|-----------------------------|-------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `USER_NAME`                         | no                          | Specifies the login name of a user to be created during the initial setup. Watch out that the default name of the admin user of bitnami/docker is "user"                |
| `USER_PASSWORD`                     | if `USER_NAME` is specified | Specifies the password for the user created during the initial setup. Passwords have to follow moodle password validation rules. Otherwise the setup script will break. |
| `USER_FIRST_NAME`                   | no                          | Specifies the first name of the user created during the initial setup.                                                                                                  |
| `USER_LAST_NAME`                    | no                          | Specifies the last name of the user created during the initial setup.                                                                                                   |
| `USER_EMAIL`                        | no                          | Specifies the email address of the user created during the initial setup.                                                                                               |
| `USER_ROLE`                         | no                          | Specifies the short name of a page wide (system) role to assign to the user created during the initial setup. This is mostly useful for moodle administration accounts  |
| `USER_CREATE_ADLER_COURSE_CATEGORY` | no                          | true \| false - If true a course category will be created for the user where has the role "adler_manager"                                                               |                                                                                                                                                            

### Other environment variables

| Variable                       | required | Description                                                                                            |
|--------------------------------|----------|--------------------------------------------------------------------------------------------------------|
| `DEVELOP_DONT_INSTALL_PLUGINS` | no       | true \| false - If true the setup script will not install any plugins. This is useful for development. |

#### Examples

Example one user

```
USER_NAME=john_doe
USER_PASSWORD=Pass1234
USER_FIRST_NAME=John
```

Example three users

```
USER_NAME=user1,user2,user3
USER_PASSWORD=Secret123,Secret123,Pass1234
USER_FIRST_NAME=First1,First2,First3
USER_LAST_NAME=Last1,Last2,Last3
USER_EMAIL=user1@example.com,user2@example.com,user3@example.com
USER_ROLE=false,manager,false
```

## Sample docker-compose.yml

see [tests/docker-compose.yml](tests/docker-compose.yml)

## Updating moodle

> [!IMPORTANT]  
> The moodle release is NOT updated automatically in any way! \
> Incrementing MOODLE_VERSION build arg will not result in an update of moodle.

Moodle updates have to be done manually (Plugins are not affected by this issue).
Follow the [Bitnami Moodle Upgrade Guide](https://docs.bitnami.com/aws/apps/moodle/administration/upgrade/).
Sadly it is not easy to automate that process as moodle itself does not provide a way to automatically update moodle.

It might be within the realm of possibility to provide AdLer images with moodle and Plugins preinstalled,
but with this approach all additional plugins would be deleted after every update (potentially breaking moodle).

A possible approach to mitigate this issue might be placing an overlay volume on top of the whole moodle directory of this
moodle image. But it is unknown whether this would work and what potential issues might arise from this.

## Docker Build Arguments

When building the Docker image for this project, you can customize the following arguments:

- `MOODLE_VERSION`: Specifies the version of Moodle to be used in the image. The default value is `latest`.
- `PLUGIN_VERSION`: Specifies the version of the Moodle plugin to be included in the image. The default value is `main`.

These arguments allow you to control the versions of Moodle and the plugin that are used during the image build process. You can adjust these values according to your specific
requirements and preferences.

## Install additional languages
1) Install the required system locale by modifying the Dockerfile
2) Install the moodle language pack either via web interface or by modifying the setup.php script (add an additional 
   `install_language_pack` call for the desired language)

## Troubleshooting

**Moodle setup fails with "The configuration file config.php alreaady exists. ...":** \
This typically indicates the setup script already failed during a previous run. Have a look on the logs of the first execution. 