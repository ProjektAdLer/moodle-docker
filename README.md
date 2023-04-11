Das Image bitnami/moodle stellt nicht alle für uns notwendigen php.ini Parameter als Umgebungsvariable bereit.
Dieses Image fügt weitere Umgebungsvariablen hinzu, die die php.ini Parameter setzen.


# TODOs
- Pipeline (oder das Image wird immer beim deploy gebaut)
- Moodle versionen (aktuell wird der latest Tag von bitnami/moodle verwendet)
