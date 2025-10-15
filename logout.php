<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
session_unset();
session_destroy();
?>

<script>
// Clear chat sessions on logout
function clearChatOnLogout() {
    if (typeof Storage !== "undefined") {
        const keys = Object.keys(localStorage);
        keys.forEach(key => {
            if (key.startsWith('chatSession_')) {
                localStorage.removeItem(key);
            }
        });
    }
}

// Execute immediately
clearChatOnLogout();

// Also try to call the global function if it exists
if (typeof clearAllChatSessions === 'function') {
    clearAllChatSessions();
}
</script>

<?php
header("location: index.php");
