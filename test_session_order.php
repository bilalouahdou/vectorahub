<?php
// Test session configuration order
echo "🔍 Session Configuration Order Test\n";
echo "===================================\n\n";

echo "1. Checking session status before any includes...\n";
echo "   Session status: " . session_status() . " (" . (session_status() == PHP_SESSION_NONE ? 'PHP_SESSION_NONE' : 'Session Active') . ")\n";
echo "   Headers sent: " . (headers_sent() ? 'Yes' : 'No') . "\n\n";

// Test config.php include
echo "2. Including config.php...\n";
require_once 'php/config.php';

echo "   Session status after config: " . session_status() . " (" . (session_status() == PHP_SESSION_NONE ? 'PHP_SESSION_NONE' : 'Session Active') . ")\n";
echo "   Headers sent: " . (headers_sent() ? 'Yes' : 'No') . "\n\n";

// Test utils.php include
echo "3. Including utils.php...\n";
require_once 'php/utils.php';

echo "   Session status after utils: " . session_status() . " (" . (session_status() == PHP_SESSION_NONE ? 'PHP_SESSION_NONE' : 'Session Active') . ")\n";
echo "   Headers sent: " . (headers_sent() ? 'Yes' : 'No') . "\n\n";

// Test startSession function
echo "4. Testing startSession() function...\n";
startSession();

echo "   Session status after startSession: " . session_status() . " (" . (session_status() == PHP_SESSION_ACTIVE ? 'PHP_SESSION_ACTIVE' : 'Not Active') . ")\n";
echo "   Headers sent: " . (headers_sent() ? 'Yes' : 'No') . "\n\n";

// Test session configuration after session start
echo "5. Testing session configuration after session start...\n";
if (session_status() == PHP_SESSION_NONE && !headers_sent()) {
    echo "   ✅ Can configure session (session not started, headers not sent)\n";
} else {
    echo "   ❌ Cannot configure session (session started or headers sent)\n";
}

echo "\n🎉 Session order test completed!\n";
echo "If you see warnings, the issue is that session_start() is called before config.php\n";
?> 