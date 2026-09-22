from config import BASE_URL
from helpers import wait_for_app_ready, body_text


def test_performance_profile_loads(logged_in_driver):
    logged_in_driver.get(f"{BASE_URL}/performance-profile")
    wait_for_app_ready(logged_in_driver)

    text = body_text(logged_in_driver).lower()
    assert "performance" in text or "profile" in text


def test_profile_contains_skill_or_topic_information(logged_in_driver):
    logged_in_driver.get(f"{BASE_URL}/performance-profile")
    wait_for_app_ready(logged_in_driver)

    text = body_text(logged_in_driver).lower()

    assert any(
        phrase in text
        for phrase in [
            "topic",
            "skill",
            "accuracy",
            "consistency",
            "versatility",
            "challenge",
            "problem solving",
        ]
    )
