from selenium.webdriver.common.by import By

from config import BASE_URL
from helpers import body_text, wait_for_app_ready


def test_valid_login_reaches_authenticated_area(logged_in_driver):
    assert "/login" not in logged_in_driver.current_url

    logged_in_driver.get(f"{BASE_URL}/dashboard")
    wait_for_app_ready(logged_in_driver)

    assert "/login" not in logged_in_driver.current_url
    assert logged_in_driver.find_element(By.TAG_NAME, "body") is not None


def test_dashboard_opens_after_login(logged_in_driver):
    logged_in_driver.get(f"{BASE_URL}/dashboard")
    wait_for_app_ready(logged_in_driver)

    assert "/dashboard" in logged_in_driver.current_url
    assert len(body_text(logged_in_driver).strip()) > 0
