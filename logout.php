<?PHP
    session_start();
    session_unset();
    header('Location:teacher-login.php');
?>