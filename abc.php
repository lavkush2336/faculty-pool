<?PHP
    session_start();
    echo password_hash("12345678", PASSWORD_DEFAULT);
?>