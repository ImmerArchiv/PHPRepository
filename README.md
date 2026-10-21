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

Set these three environment variables outside the test process:

- `API_BASE_URL`: API base URL, for example `http://localhost:8765`.
- `API_BEARER_TOKEN`: plain-text bearer token for the repository, not the bcrypt hash stored in the configuration.
- `API_REPOSITORY`: name of the existing empty repository.

Install the test dependencies and run the lifecycle and security scenarios against a fresh empty repository:
```
python -m pip install -r requirements-test.txt
python -m behave tests/features/repository_lifecycle.feature tests/features/repository_security.feature
```

For an HTML report, install the optional formatter and run:
```
python -m behave --format behave_html_formatter:HTMLFormatter --outfile reports/behave-report.html tests/features/
```

