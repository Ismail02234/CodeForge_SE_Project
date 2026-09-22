import pytest
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait

from config import BASE_URL, DEFAULT_TIMEOUT


ROUTES = [
    "/problems",
    "/contests",
    "/rivalry",
    "/universities",
    "/performance-profile",
    "/ghost-race",
    "/sql-battle",
    "/database",
    "/sql-lab",
    "/how-it-works",
]


@pytest.mark.parametrize("route", ROUTES)
def test_authenticated_routes_load(logged_in_driver, route):
    logged_in_driver.get(f"{BASE_URL}{route}")

    WebDriverWait(logged_in_driver, DEFAULT_TIMEOUT).until(
        lambda d: d.execute_script("return document.readyState") == "complete"
    )

    WebDriverWait(logged_in_driver, DEFAULT_TIMEOUT).until(
        lambda d: route in d.current_url or "/login" in d.current_url
    )

    assert "/login" not in logged_in_driver.current_url, (
        f"Authentication was lost while opening {route}"
    )

    assert route in logged_in_driver.current_url, (
        f"Expected route {route}, got {logged_in_driver.current_url}"
    )

    body = logged_in_driver.find_element(By.TAG_NAME, "body")
    assert body is not None

    assert logged_in_driver.execute_script(
        "return document.documentElement != null && document.body != null"
    )
