import os

BASE_URL = os.getenv("CODEFORGE_BASE_URL", "http://localhost:5173").rstrip("/")

# Default CodeForge Selenium demo account.
USERNAME = os.getenv("CODEFORGE_USERNAME", "nafiz")
PASSWORD = os.getenv("CODEFORGE_PASSWORD", "123456")

DEFAULT_TIMEOUT = int(os.getenv("CODEFORGE_TIMEOUT", "15"))
HEADLESS = os.getenv("CODEFORGE_HEADLESS", "0") == "1"
