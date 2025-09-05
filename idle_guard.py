import os
import time
import threading
from dataclasses import dataclass

# Default = 480s (8 minutes). Set RUNNER_IDLE_TIMEOUT in service env to override.
_DEFAULT_TIMEOUT = int(os.getenv("RUNNER_IDLE_TIMEOUT", "480") or "480")

@dataclass
class IdleGuard:
    timeout_seconds: int = _DEFAULT_TIMEOUT
    last_request_ts: float = time.time()
    _stop_flag: bool = False
    _thread: threading.Thread = None

    def update_request_time(self):
        self.last_request_ts = time.time()

    def seconds_idle(self) -> float:
        return time.time() - self.last_request_ts

    def is_idle(self) -> bool:
        return self.timeout_seconds > 0 and self.seconds_idle() >= self.timeout_seconds

    def start_monitoring(self, check_interval: int = 15):
        """Start a background thread that exits the process when idle too long."""
        if self.timeout_seconds <= 0:
            return
        if self._thread is not None:
            return

        def _loop():
            while not self._stop_flag:
                time.sleep(check_interval)
                if self.is_idle():
                    # Exit the whole runner process; NSSM/you can restart it later
                    os._exit(0)

        self._thread = threading.Thread(target=_loop, daemon=True, name="idle-guard")
        self._thread.start()

    def stop_monitoring(self):
        self._stop_flag = True

_guard = None

def get_idle_guard() -> IdleGuard:
    global _guard
    if _guard is None:
        _guard = IdleGuard()
    return _guard