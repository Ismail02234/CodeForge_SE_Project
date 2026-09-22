from config import BASE_URL
from helpers import wait_for_app_ready, body_text


def test_rivalry_page_loads(logged_in_driver):
    logged_in_driver.get(f"{BASE_URL}/rivalry")
    wait_for_app_ready(logged_in_driver)

    text = body_text(logged_in_driver).lower()
    assert "rival" in text or "compare" in text


def test_rivalry_page_has_prediction_or_participant_ui(logged_in_driver):
    logged_in_driver.get(f"{BASE_URL}/rivalry")
    wait_for_app_ready(logged_in_driver)

    text = body_text(logged_in_driver).lower()

    assert any(
        phrase in text
        for phrase in [
            "prediction",
            "probability",
            "participant",
            "win",
            "compare",
            "rating",
        ]
    )
