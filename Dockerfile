FROM bitnami/moodle

COPY entrypoint_additional_php_vars.sh /opt/

ENTRYPOINT [ "/opt/entrypoint_additional_php_vars.sh" ]
CMD [ "/opt/bitnami/scripts/moodle/run.sh" ]

