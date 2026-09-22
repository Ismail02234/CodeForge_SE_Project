from selenium.webdriver.common.by import By
from selenium.webdriver.common.keys import Keys
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC

from config import BASE_URL, DEFAULT_TIMEOUT
from helpers import wait_for_app_ready


def test_ai_assistant_can_navigate_without_external_model(logged_in_driver):
    logged_in_driver.get(f"{BASE_URL}/dashboard")
    wait_for_app_ready(logged_in_driver)

    launcher = WebDriverWait(logged_in_driver, DEFAULT_TIMEOUT).until(
        EC.element_to_be_clickable(
            (By.CSS_SELECTOR, "button[aria-label='Open CodeForge Copilot']")
        )
    )
    launcher.click()

    command = WebDriverWait(logged_in_driver, DEFAULT_TIMEOUT).until(
        EC.visibility_of_element_located(
            (By.CSS_SELECTOR, "input[aria-label='Message CodeForge Copilot']")
        )
    )
    command.send_keys("open ghost race")
    command.send_keys(Keys.ENTER)

    WebDriverWait(logged_in_driver, DEFAULT_TIMEOUT).until(
        lambda d: "/ghost-race" in d.current_url
    )

    assert "/ghost-race" in logged_in_driver.current_url
