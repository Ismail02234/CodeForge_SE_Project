from selenium.webdriver.common.by import By

from config import BASE_URL
from helpers import wait_for_page


def test_landing_page_loads(driver):
    driver.get(BASE_URL)
    wait_for_page(driver)

    assert driver.current_url.startswith(BASE_URL)

    body = driver.find_element(By.TAG_NAME, "body")
    assert body is not None


def test_login_page_has_password_field(driver):
    driver.get(f"{BASE_URL}/login")
    wait_for_page(driver)

    password_fields = driver.find_elements(
        By.CSS_SELECTOR,
        "input[type='password']"
    )

    assert len(password_fields) > 0


def test_register_page_loads(driver):
    driver.get(f"{BASE_URL}/register")
    wait_for_page(driver)

    assert driver.current_url.startswith(BASE_URL)

    body = driver.find_element(By.TAG_NAME, "body")
    assert body is not None
