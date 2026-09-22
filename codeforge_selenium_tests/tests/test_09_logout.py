from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait

from config import BASE_URL, DEFAULT_TIMEOUT
from helpers import wait_for_app_ready


def test_logout_control_exists(logged_in_driver):
    logged_in_driver.get(f"{BASE_URL}/dashboard")
    wait_for_app_ready(logged_in_driver)

    xpath = (
        "//*[self::button or self::a]"
        "[contains(translate(normalize-space(.), "
        "'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz'), 'logout') "
        "or contains(translate(normalize-space(.), "
        "'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz'), 'log out') "
        "or contains(translate(normalize-space(.), "
        "'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz'), 'sign out') "
        "or contains(translate(@aria-label, "
        "'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz'), 'logout') "
        "or contains(translate(@aria-label, "
        "'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz'), 'sign out') "
        "or contains(translate(@title, "
        "'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz'), 'logout') "
        "or contains(translate(@title, "
        "'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz'), 'sign out')]"
    )

    WebDriverWait(logged_in_driver, DEFAULT_TIMEOUT).until(
        lambda d: len(d.find_elements(By.XPATH, xpath)) > 0
    )

    assert logged_in_driver.find_elements(By.XPATH, xpath)
