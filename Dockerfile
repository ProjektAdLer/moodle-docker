FROM bitnami/moodle:${MOODLE_VERSION:-latest}

RUN apt update && apt install curl unzip nano -y

COPY entrypoint_adler.sh setup.php setup.sh /opt/adler/

ENTRYPOINT [ "/opt/adler/entrypoint_adler.sh" ]
CMD [ "/opt/bitnami/scripts/moodle/run.sh" ]

