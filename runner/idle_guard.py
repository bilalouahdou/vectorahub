import os
import time
import threading
from datetime import datetime, timedelta
import logging

logger = logging.getLogger(__name__)

class IdleGuard:
    """Tracks last request time and auto-shuts down after idle timeout"""
    
    def __init__(self, idle_minutes=8):
        self.idle_minutes = idle_minutes
        self.last_request_time = time.time()
        self._lock = threading.Lock()
        self._shutdown_event = threading.Event()
        self._monitor_thread = None
        self._running = False
        
    def update_request_time(self):
        """Update the last request time"""
        with self._lock:
            self.last_request_time = time.time()
            logger.debug(f"Updated last request time: {datetime.fromtimestamp(self.last_request_time)}")
    
    def start_monitoring(self):
        """Start the idle monitoring thread"""
        if self._running:
            return
            
        self._running = True
        self._monitor_thread = threading.Thread(target=self._monitor_loop, daemon=True)
        self._monitor_thread.start()
        logger.info(f"Idle monitoring started (timeout: {self.idle_minutes} minutes)")
    
    def stop_monitoring(self):
        """Stop the idle monitoring"""
        self._running = False
        self._shutdown_event.set()
        if self._monitor_thread:
            self._monitor_thread.join(timeout=5)
        logger.info("Idle monitoring stopped")
    
    def _monitor_loop(self):
        """Main monitoring loop - checks every 30 seconds"""
        while self._running and not self._shutdown_event.is_set():
            try:
                with self._lock:
                    idle_seconds = time.time() - self.last_request_time
                    idle_minutes = idle_seconds / 60
                    
                    if idle_minutes >= self.idle_minutes:
                        logger.info(f"Idle timeout reached ({idle_minutes:.1f} minutes), shutting down...")
                        # Use os._exit to force shutdown
                        os._exit(0)
                    else:
                        remaining = self.idle_minutes - idle_minutes
                        logger.debug(f"Idle check: {idle_minutes:.1f} minutes idle, {remaining:.1f} minutes remaining")
                
                # Wait 30 seconds before next check
                self._shutdown_event.wait(30)
                
            except Exception as e:
                logger.error(f"Error in idle monitoring: {e}")
                time.sleep(30)

# Global instance
idle_guard = None

def get_idle_guard():
    """Get or create the global idle guard instance"""
    global idle_guard
    if idle_guard is None:
        idle_minutes = int(os.getenv('IDLE_EXIT_MIN', '8'))
        idle_guard = IdleGuard(idle_minutes)
    return idle_guard 