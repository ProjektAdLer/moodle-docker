This project extends the bitnami/moodle image with the following features:
- Setting up AdLer (after the first start the Moodle part of AdLer is fully set up).
- Adding another environment variable to set a php.ini option.


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
