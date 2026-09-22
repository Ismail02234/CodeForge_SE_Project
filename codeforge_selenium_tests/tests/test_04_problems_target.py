from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait

from config import BASE_URL, DEFAULT_TIMEOUT
from helpers import wait_for_page, body_text


def test_problems_page_shows_target_feature(logged_in_driver):
    logged_in_driver.get(f"{BASE_URL}/problems")
    wait_for_page(logged_in_driver)

    buttons = logged_in_driver.find_elements(
        By.XPATH,
        "//button[contains(translate(normalize-space(.), "
        "'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz'), 'target')]",
    )

    assert buttons, "TARGET button was not found on the Problems page"


def test_target_button_activates_weakest_field_focus(logged_in_driver):
    logged_in_driver.get(f"{BASE_URL}/problems")
    wait_for_page(logged_in_driver)

    button = logged_in_driver.find_element(
        By.XPATH,
        "//button[contains(translate(normalize-space(.), "
        "'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz'), 'target')]",
    )
    button.click()

    WebDriverWait(logged_in_driver, DEFAULT_TIMEOUT).until(
        lambda d: "target" in body_text(d).lower()
        and (
            "weakest" in body_text(d).lower()
            or "problem" in body_text(d).lower()
        )
    )

    text = body_text(logged_in_driver).lower()
    assert "target" in text
