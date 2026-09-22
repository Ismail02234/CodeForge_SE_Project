from __future__ import annotations

from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC


def wait_for_page(driver, timeout=15):
    WebDriverWait(driver, timeout).until(
        lambda d: d.execute_script("return document.readyState") == "complete"
    )


def body_text(driver) -> str:
    return driver.find_element(By.TAG_NAME, "body").text


def wait_for_app_ready(driver, timeout=20):
    wait_for_page(driver, timeout)

    def ready(d):
        try:
            text = body_text(d).strip().lower()
            if not text:
                return False

            transient = {
                "loading",
                "loading...",
                "loading system",
                "loading system...",
            }

            return text not in transient
        except Exception:
            return False

    WebDriverWait(driver, timeout).until(ready)


def click_text(driver, text: str, timeout=10):
    xpath = (
        f"//*[self::a or self::button]"
        f"[contains(translate(normalize-space(.), "
        f"'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz'), "
        f"'{text.lower()}')]"
    )

    element = WebDriverWait(driver, timeout).until(
        EC.element_to_be_clickable((By.XPATH, xpath))
    )
    driver.execute_script(
        "arguments[0].scrollIntoView({block: 'center'});",
        element,
    )
    element.click()
    return element


def find_first(driver, selectors):
    for by, value in selectors:
        found = driver.find_elements(by, value)
        for element in found:
            if element.is_displayed() and element.is_enabled():
                return element
    return None


def _visible_text_inputs(driver):
    result = []

    for element in driver.find_elements(By.TAG_NAME, "input"):
        try:
            if not element.is_displayed() or not element.is_enabled():
                continue

            input_type = (element.get_attribute("type") or "text").lower()

            if input_type in {
                "hidden",
                "submit",
                "button",
                "checkbox",
                "radio",
                "file",
                "image",
                "reset",
            }:
                continue

            result.append(element)
        except Exception:
            continue

    return result


def login(driver, base_url: str, username: str, password: str, timeout=15):
    driver.get(f"{base_url}/login")
    wait_for_page(driver, timeout)

    WebDriverWait(driver, timeout).until(
        lambda d: len(_visible_text_inputs(d)) >= 2
    )

    inputs = _visible_text_inputs(driver)

    username_input = find_first(
        driver,
        [
            (By.NAME, "username"),
            (By.NAME, "email"),
            (By.NAME, "identifier"),
            (By.ID, "username"),
            (By.ID, "email"),
            (By.CSS_SELECTOR, "input[placeholder*='user' i]"),
            (By.CSS_SELECTOR, "input[placeholder*='email' i]"),
        ],
    )

    password_input = find_first(
        driver,
        [
            (By.NAME, "password"),
            (By.ID, "password"),
            (By.CSS_SELECTOR, "input[type='password']"),
            (By.CSS_SELECTOR, "input[placeholder*='password' i]"),
            (By.CSS_SELECTOR, "input[name*='pass' i]"),
        ],
    )

    if username_input is None:
        username_input = inputs[0]

    if password_input is None:
        password_candidates = [
            element for element in inputs if element != username_input
        ]
        assert password_candidates, "Could not identify the password input."
        password_input = password_candidates[-1]

    username_input.clear()
    username_input.send_keys(username)

    password_input.clear()
    password_input.send_keys(password)

    submit = find_first(
        driver,
        [
            (By.CSS_SELECTOR, "button[type='submit']"),
            (By.CSS_SELECTOR, "input[type='submit']"),
            (
                By.XPATH,
                "//button[contains(translate(normalize-space(.), "
                "'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz'), 'login')]",
            ),
            (
                By.XPATH,
                "//button[contains(translate(normalize-space(.), "
                "'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz'), 'sign in')]",
            ),
        ],
    )

    if submit is None:
        buttons = [
            element
            for element in driver.find_elements(By.TAG_NAME, "button")
            if element.is_displayed() and element.is_enabled()
        ]
        assert buttons, "Could not find a login button."
        submit = buttons[-1]

    submit.click()

    WebDriverWait(driver, timeout).until(
        lambda d: "/login" not in d.current_url
    )

    return driver.current_url
