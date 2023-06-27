Edit this project only under linux (not Windows and not WSL). Otherwise expect problems because of stupid Windows line endings....

Das Image bitnami/moodle stellt nicht alle für uns notwendigen php.ini Parameter als Umgebungsvariable bereit.
Dieses Image fügt weitere Umgebungsvariablen hinzu, die die php.ini Parameter setzen.


# TODOs
- Pipeline (oder das Image wird immer beim deploy gebaut)
- Moodle versionen (aktuell wird der latest Tag von bitnami/moodle verwendet)
