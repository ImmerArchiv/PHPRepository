import hashlib
import json
import os
import shutil
import stat
from pathlib import Path
from urllib.error import HTTPError
from urllib.request import Request, urlopen

from behave import given, when, then


TEST_BAGIT = "behave-test-bag"
SECOND_TEST_BAGIT = "behave-test-bag-page2"
TEST_FILENAME = "behave-test.txt"
TEMP_FILENAME = "behave-test.part"
TEST_FILE_CONTENT = b"Behave repository lifecycle test file.\n"
TEST_FILE_SPLIT = len(TEST_FILE_CONTENT) // 2
QUOTA_BAGIT = "behave-quota-bag"
QUOTA_FILENAME = "quota-exact.bin"
QUOTA_TEMP_FILENAME = "quota-exact.part"
OVERFLOW_TEMP_FILENAME = "quota-overflow.part"
QUOTA_BYTES = 1024 * 1024
QUOTA_FILE_CONTENT = b"Q" * QUOTA_BYTES
MULTIPART_BOUNDARY = "----BehaveRepositoryTestBoundary"
REPOSITORY_ROOT = Path(__file__).resolve().parents[3]


def _store_response(context, response, expect_json):
    with response:
        context.response_status = response.status
        context.response_content_type = response.headers.get_content_type()
        context.response_body = response.read()

    context.response_json = None
    if expect_json:
        try:
            context.response_json = json.loads(context.response_body)
        except (UnicodeDecodeError, json.JSONDecodeError) as error:
            raise AssertionError(
                "Expected a JSON response from the API, got "
                f"{context.response_content_type}: {context.response_body!r}"
            ) from error


def _request(context, endpoint, payload, token=None, use_configured_token=True):
    headers = {"Content-Type": "application/json"}
    if use_configured_token:
        token = context.api_token
    if token is not None:
        headers["Authorization"] = f"Bearer {token}"

    request = Request(
        f"{context.api_base_url}/{endpoint}.php",
        data=json.dumps(payload).encode("utf-8"),
        headers=headers,
        method="POST",
    )

    try:
        response = urlopen(request, timeout=10)
    except HTTPError as error:
        response = error
    _store_response(
        context,
        response,
        expect_json=endpoint not in {"downloadfile", "getfilepart", "downloadlog"},
    )


def _request_file_upload(context, tempname, filename, content):
    boundary = MULTIPART_BOUNDARY
    parts = []

    # Add the repository form field.
    parts.extend(
        [
            f"--{boundary}\r\n".encode("ascii"),
            b'Content-Disposition: form-data; name="repository"\r\n\r\n',
            context.repository.encode("utf-8"),
            b"\r\n",
        ]
    )

    # Add the temporary filename form field.
    parts.extend(
        [
            f"--{boundary}\r\n".encode("ascii"),
            b'Content-Disposition: form-data; name="tempname"\r\n\r\n',
            tempname.encode("utf-8"),
            b"\r\n",
        ]
    )

    # Add the uploaded file as the multipart data field.
    parts.extend(
        [
            f"--{boundary}\r\n".encode("ascii"),
            (
                'Content-Disposition: form-data; name="data"; '
                f'filename="{filename}"\r\n'
            ).encode("ascii"),
            b"Content-Type: application/octet-stream\r\n\r\n",
            content,
            b"\r\n",
            f"--{boundary}--\r\n".encode("ascii"),
        ]
    )

    request = Request(
        f"{context.api_base_url}/putfilepart.php",
        data=b"".join(parts),
        headers={
            "Authorization": f"Bearer {context.api_token}",
            "Content-Type": f"multipart/form-data; boundary={boundary}",
        },
        method="POST",
    )

    try:
        response = urlopen(request, timeout=10)
    except HTTPError as error:
        response = error
    _store_response(context, response, expect_json=True)


@given("the externally configured API credentials")
def step_externally_configured_api_credentials(context):
    required_variables = ("API_BASE_URL", "API_BEARER_TOKEN", "API_REPOSITORY")
    missing_variables = [name for name in required_variables if not os.getenv(name)]
    if missing_variables:
        raise AssertionError(
            "Set these environment variables before running Behave: "
            + ", ".join(missing_variables)
        )

    context.api_base_url = os.environ["API_BASE_URL"].rstrip("/")
    context.api_token = os.environ["API_BEARER_TOKEN"]
    context.repository = os.environ["API_REPOSITORY"]


@when("I request the repository status")
def step_request_repository_status(context):
    _request(context, "status", {"repository": context.repository})


@when("I request the repository status without a bearer token")
def step_request_status_without_token(context):
    _request(
        context,
        "status",
        {"repository": context.repository},
        use_configured_token=False,
    )


@when("I request the repository status with an invalid bearer token")
def step_request_status_with_invalid_token(context):
    _request(
        context,
        "status",
        {"repository": context.repository},
        token="invalid-behave-token",
        use_configured_token=False,
    )


@when("I request the default repository status with the test token")
def step_request_default_status_with_test_token(context):
    assert context.repository != "default", (
        "API_REPOSITORY must name a non-default repository for this security test"
    )
    _request(context, "status", {"repository": "default"})


@when("I list all bags")
def step_list_all_bags(context):
    _list_bags(context, skip=0, take=1000)


def _list_bags(context, skip, take):
    _request(
        context,
        "listall",
        {"repository": context.repository, "skip": skip, "take": take},
    )


@when("I list the first bag page")
def step_list_first_bag_page(context):
    _list_bags(context, skip=0, take=1)


@when("I list the second bag page")
def step_list_second_bag_page(context):
    _list_bags(context, skip=1, take=1)


@when("I list a page after the last bag")
def step_list_page_after_last_bag(context):
    _list_bags(context, skip=2, take=1)


@when("I create the test bag")
def step_create_test_bag(context):
    _request(
        context,
        "create",
        {
            "repository": context.repository,
            "bagit": TEST_BAGIT,
            "info": [{"Description": "Behave API lifecycle test"}],
        },
    )


@when("I create the second test bag")
def step_create_second_test_bag(context):
    _request(
        context,
        "create",
        {
            "repository": context.repository,
            "bagit": SECOND_TEST_BAGIT,
            "info": [{"Description": "Behave API pagination test"}],
        },
    )


@when("I upload the first part of the test file")
def step_upload_first_test_file_part(context):
    _request_file_upload(
        context,
        TEMP_FILENAME,
        TEST_FILENAME,
        TEST_FILE_CONTENT[:TEST_FILE_SPLIT],
    )


@when("I upload the second part of the test file")
def step_upload_second_test_file_part(context):
    _request_file_upload(
        context,
        TEMP_FILENAME,
        TEST_FILENAME,
        TEST_FILE_CONTENT[TEST_FILE_SPLIT:],
    )


@given('the configured repository is "test"')
def step_repository_is_test(context):
    assert context.repository == "test"


def _test_repository_path(context):
    assert context.repository == "test"
    repository_path = (
        REPOSITORY_ROOT / "data" / "BagItRepository" / context.repository
    ).resolve()
    expected_path = (REPOSITORY_ROOT / "data" / "BagItRepository" / "test").resolve()
    assert repository_path == expected_path
    assert repository_path.is_dir()
    return repository_path


def _retry_readonly_delete(function, path, error_info):
    os.chmod(path, os.stat(path).st_mode | stat.S_IWRITE)
    function(path)


@when('I delete all "behave-*" BagIts from the test repository')
def step_delete_behave_bagits(context):
    repository_path = _test_repository_path(context)
    context.deleted_bagits = []

    for bag_path in repository_path.glob("behave-*"):
        if bag_path.is_dir() and (bag_path / "bagit.txt").is_file():
            shutil.rmtree(bag_path, onerror=_retry_readonly_delete)
            context.deleted_bagits.append(bag_path.name)


@then('no "behave-*" BagIts remain in the test repository')
def step_no_behave_bagits_remain(context):
    repository_path = _test_repository_path(context)
    remaining_bagits = [
        bag_path.name
        for bag_path in repository_path.glob("behave-*")
        if bag_path.is_dir() and (bag_path / "bagit.txt").is_file()
    ]
    assert remaining_bagits == []


@when("I delete the test repository log file")
def step_delete_test_repository_log(context):
    repository_path = _test_repository_path(context)
    context.test_log_path = repository_path / "log" / "log.txt"
    context.test_log_path.unlink(missing_ok=True)


@then("the test repository log file does not exist")
def step_test_repository_log_is_deleted(context):
    assert not context.test_log_path.exists()


@when("I create the quota test bag")
def step_create_quota_test_bag(context):
    _request(
        context,
        "create",
        {
            "repository": context.repository,
            "bagit": QUOTA_BAGIT,
            "info": [{"Description": "Behave quota boundary test"}],
        },
    )


@when("I upload a file of exactly one MiB")
def step_upload_exact_quota_file(context):
    _request_file_upload(
        context,
        QUOTA_TEMP_FILENAME,
        QUOTA_FILENAME,
        QUOTA_FILE_CONTENT,
    )


@when("I finalize the one MiB file")
def step_finalize_quota_file(context):
    _request(
        context,
        "appendfile",
        {
            "repository": context.repository,
            "bagit": QUOTA_BAGIT,
            "mode": "complete",
            "name": QUOTA_FILENAME,
            "tempname": QUOTA_TEMP_FILENAME,
            "md5": hashlib.md5(QUOTA_FILE_CONTENT).hexdigest(),
        },
    )


@when("I upload one byte beyond the quota")
def step_upload_over_quota(context):
    _request_file_upload(
        context,
        OVERFLOW_TEMP_FILENAME,
        "quota-overflow.bin",
        b"X",
    )


@when("I finalize the test file")
def step_finalize_test_file(context):
    _request(
        context,
        "appendfile",
        {
            "repository": context.repository,
            "bagit": TEST_BAGIT,
            "mode": "complete",
            "name": TEST_FILENAME,
            "tempname": TEMP_FILENAME,
            "md5": hashlib.md5(TEST_FILE_CONTENT).hexdigest(),
        },
    )


@when("I list the test bag")
def step_list_test_bag(context):
    _request(
        context,
        "listone",
        {"repository": context.repository, "bagit": TEST_BAGIT},
    )


@when("I request the test file checksum")
def step_request_test_file_checksum(context):
    _request(
        context,
        "checksum",
        {
            "repository": context.repository,
            "bagit": TEST_BAGIT,
            "name": TEST_FILENAME,
        },
    )


@when("I download the test file")
def step_download_test_file(context):
    _request(
        context,
        "downloadfile",
        {
            "repository": context.repository,
            "bagit": TEST_BAGIT,
            "name": TEST_FILENAME,
        },
    )


@when("I download the first half of the test file")
def step_download_first_file_part(context):
    _request(
        context,
        "getfilepart",
        {
            "repository": context.repository,
            "bagit": TEST_BAGIT,
            "name": TEST_FILENAME,
            "offset": 0,
            "maxlength": TEST_FILE_SPLIT,
        },
    )
    context.downloaded_file_parts = [context.response_body]


@when("I download the second half of the test file")
def step_download_second_file_part(context):
    _request(
        context,
        "getfilepart",
        {
            "repository": context.repository,
            "bagit": TEST_BAGIT,
            "name": TEST_FILENAME,
            "offset": TEST_FILE_SPLIT,
            "maxlength": len(TEST_FILE_CONTENT) - TEST_FILE_SPLIT,
        },
    )
    context.downloaded_file_parts.append(context.response_body)


@when("I download the repository log")
def step_download_repository_log(context):
    _request(context, "downloadlog", {"repository": context.repository})


@then("the API response status is {status_code:d}")
def step_api_response_status(context, status_code):
    assert context.response_status == status_code


@then('the API response state is "{state}"')
def step_api_response_state(context, state):
    assert context.response_json["state"] == state


@then('the API error message is "{message}"')
def step_api_error_message(context, message):
    assert context.response_json["state"] == "error"
    assert context.response_json["message"] == message


@then("the repository quota is exactly one MiB")
def step_repository_quota_is_one_mib(context):
    assert int(context.response_json["maxsize"]) == QUOTA_BYTES


@then("the repository contains exactly one MiB of files")
def step_repository_contains_one_mib(context):
    assert int(context.response_json["size"]) == QUOTA_BYTES
    assert context.response_json["files"] == 1


@then("the repository has no bags or files")
def step_repository_is_empty(context):
    assert context.response_json["bagits"] == 0
    assert context.response_json["files"] == 0
    assert int(context.response_json["size"]) == 0


@then("no bags are listed")
def step_no_bags_are_listed(context):
    assert context.response_json["bagits"] == []


@then("the test bag is listed")
def step_test_bag_is_listed(context):
    assert any(bag["bagit"] == TEST_BAGIT for bag in context.response_json["bagits"])


@then("the first bag page contains only the first test bag")
def step_first_bag_page(context):
    assert [bag["bagit"] for bag in context.response_json["bagits"]] == [TEST_BAGIT]


@then("the second bag page contains only the second test bag")
def step_second_bag_page(context):
    assert [bag["bagit"] for bag in context.response_json["bagits"]] == [
        SECOND_TEST_BAGIT
    ]


@then("the bag page is empty")
def step_bag_page_is_empty(context):
    assert context.response_json["bagits"] == []


@then("the test file is listed")
def step_test_file_is_listed(context):
    assert context.response_json["bagit"] == TEST_BAGIT
    assert any(
        file["name"] == TEST_FILENAME for file in context.response_json["data"]
    )


@then("the returned checksum matches the test file")
def step_checksum_matches_test_file(context):
    expected_checksum = hashlib.md5(TEST_FILE_CONTENT).hexdigest()
    assert context.response_json["bagit"] == TEST_BAGIT
    assert context.response_json["name"] == TEST_FILENAME
    assert context.response_json["md5"] == expected_checksum


@then("the append checksum matches the test file")
def step_append_checksum_matches_test_file(context):
    expected_checksum = hashlib.md5(TEST_FILE_CONTENT).hexdigest()
    assert context.response_json["bagit"] == TEST_BAGIT
    assert context.response_json["md5"] == expected_checksum


@then("the downloaded content matches the test file")
def step_downloaded_content_matches_test_file(context):
    assert context.response_content_type == "application/octet-stream"
    assert context.response_body == TEST_FILE_CONTENT


@then("the downloaded parts reconstruct the test file")
def step_downloaded_parts_reconstruct_file(context):
    assert context.response_content_type == "application/octet-stream"
    assert b"".join(context.downloaded_file_parts) == TEST_FILE_CONTENT


@then("the log contains the test file append entry")
def step_log_contains_append_entry(context):
    assert context.response_content_type == "application/octet-stream"
    assert b"append file name=behave-test.txt" in context.response_body
