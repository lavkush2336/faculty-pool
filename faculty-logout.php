<?PHP
    session_start();
    session_unset();
    header('Location:faculty-login.php');
?>