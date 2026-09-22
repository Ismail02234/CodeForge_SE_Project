from config import BASE_URL
from helpers import wait_for_app_ready, body_text


def test_ghost_race_page_loads(logged_in_driver):
    logged_in_driver.get(f"{BASE_URL}/ghost-race")
    wait_for_app_ready(logged_in_driver)

    text = body_text(logged_in_driver).lower()
    assert "ghost" in text or "race" in text


def test_ghost_race_has_race_selection_ui(logged_in_driver):
    logged_in_driver.get(f"{BASE_URL}/ghost-race")
    wait_for_app_ready(logged_in_driver)

    text = body_text(logged_in_driver).lower()

    assert any(
        phrase in text
        for phrase in [
            "race",
            "ghost",
            "session",
            "problem",
            "opponent",
        ]
    )
