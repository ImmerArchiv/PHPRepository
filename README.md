# PHPrepository

API of Repository for ImmerArchiv (written in PHP)

## Installation
Create a `.env` file with project-specific values:
```
WEB_PORT=8765
```

## External Docker network
The Compose configuration expects an external network named `reverse-proxy`. Create it before starting the application:
```
docker network create reverse-proxy
```

You can check that the network exists with:
```
docker network inspect reverse-proxy
```

## Usage
```
docker compose up --build -d
```

Call index.html for Installation

To stop the application and remove the test network:
```
docker compose down
docker network rm reverse-proxy
```
`docker compose down` does not remove external networks. Remove the network only if no other containers or projects use it.

## Add new Repository
Run the command inside the web container. It creates the repository under `DATAPATH` from `conf/config.inc.php`, updates the mounted bearer and repository configuration files, and prints the generated bearer token once:
```
docker exec php-repository php cli-util/adduser.php <username> "100 MB"
```

## Tests
The lifecycle test cleans generated `behave-*` BagIts and the log file in `data/BagItRepository/test` before running its five phases. It also checks `listall` pagination across two test Bags, including an empty page after the last Bag. The quota test performs the same cleanup before testing the exact `1 MB` limit. Both tests leave their newly created BagIts in place. Keep unrelated BagIts out of the test repository; phase 1 expects it to be empty after cleanup.

In Git Bash, create the local environment script from the example, replace the token placeholder with the repository's plain-text bearer token (not its bcrypt hash), then source it:
```
cp .test-env.sh.example .test-env.sh
# Edit API_BEARER_TOKEN in .test-env.sh
source ./.test-env.sh
```
The local `.test-env.sh` is ignored by Git so the bearer token is not committed. It sets `API_BASE_URL`, `API_BEARER_TOKEN`, and `API_REPOSITORY`.

Install the test dependencies and run the lifecycle and security scenarios against a fresh empty repository:
```
python -m pip install -r requirements-test.txt
```

Test and xreate an HTML report
```
python -m behave --format behave_html_formatter:HTMLFormatter --outfile reports/behave-report.html tests/features/
```

