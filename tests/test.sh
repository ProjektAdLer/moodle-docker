#!/bin/bash

# fail if one command fails
set -e

# Set variables
username="manager"
password='Manager1234!1234'
hostname="localhost"
port="8000"

if [ $(id -u) -ne 0 ]
  then echo "Please run as root"
  exit 1
fi

apt update && apt install -y curl jq

docker compose down -v
docker compose up -d --build

echo "docker containers starting"

# Check if the container is ready
set +e
COUNT=0
COUNT_MAX=100
while [ $COUNT -lt $COUNT_MAX ]; do
  curl -f -s -LI http://${hostname}:${port} > /dev/null
  if [ $? -eq 0 ]
    then
      break
  fi
  echo "moodle still starting... This will typically take between 1 and 3 minutes"
  sleep 5
  COUNT=$((COUNT + 1))
done
set -e
# output docker logs
docker compose logs

# If the container is not ready, exit with an error
if [ $COUNT -eq $COUNT_MAX ]; then
  echo "Container did not become ready in time"
  exit 1
fi

# Get token
token=$(curl --location "http://${hostname}:${port}/login/token.php?username=${username}&password=${password}&service=adler_services" -s)

# Extract token from response
token=$(echo "$token" | jq -r '.token')

# Upload course
course_fullname=$(curl --location "http://${hostname}:${port}/webservice/rest/server.php" \
  --form "wstoken=${token}" \
  --form "wsfunction=local_adler_upload_course" \
  --form "moodlewsrestformat=json" \
  --form "mbz=@testworld.mbz" \
  | jq -r '.data.course_fullname')
if [ "$course_fullname" != "test" ]; then
  exit 1
fi
printf "course upload successful, name is $course_fullname \n"

# Search uploaded course and get course ID
new_course_id=$(curl --location "http://${hostname}:${port}/webservice/rest/server.php" \
  --form "wstoken=${token}" \
  --form "wsfunction=core_course_search_courses" \
  --form "moodlewsrestformat=json" \
  --form "criterianame=search" \
  --form "criteriavalue=${course_fullname}" \
  | jq -r '.courses[0].id')
if [ "$new_course_id" != "2" ]; then
  exit 1
fi
printf "found course, has id $new_course_id \n"

# Get total number of courses and verify it equals 1
total_matching_courses=$(curl --location "http://${hostname}:${port}/webservice/rest/server.php" \
  --form "wstoken=${token}" \
  --form "wsfunction=core_course_search_courses" \
  --form "moodlewsrestformat=json" \
  --form "criterianame=search" \
  --form "criteriavalue=${course_fullname}" \
  | jq -r '.total')
if [ "$total_matching_courses" -ne 1 ]; then
  exit 1
fi
printf "found exactly one course \n"

docker compose down -v
