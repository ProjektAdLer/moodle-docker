ARG MOODLE_VERSION=latest
ARG PLUGIN_VERSION=main

FROM bitnami/moodle:${MOODLE_VERSION}

RUN apt update && apt install curl unzip nano -y
COPY opt/adler /opt/adler
# ARG are wiped after FROM, see https://docs.docker.com/engine/reference/builder/#understand-how-arg-and-from-interact
ARG PLUGIN_VERSION
ENV PLUGIN_VERSION=${PLUGIN_VERSION}

ENTRYPOINT [ "/opt/adler/entrypoint_adler.sh" ]
CMD [ "/opt/bitnami/scripts/moodle/run.sh" ]

