<?php
function show_flash(){
    $hasSuccess = !empty($_SESSION['success']);
    $hasErrors  = !empty($_SESSION['errors']);

    if (!$hasSuccess && !$hasErrors) {
        return;
    }

    echo "<div class='flash-stack'>";

    if ($hasSuccess) {
        $msg = is_array($_SESSION['success']) ? implode(' ', $_SESSION['success']) : $_SESSION['success'];
        echo "<div class='alert alert-success alert-toast'>" . htmlspecialchars($msg) . "</div>";
        unset($_SESSION['success']);
    }

    if ($hasErrors) {
        $errors = is_array($_SESSION['errors']) ? $_SESSION['errors'] : [$_SESSION['errors']];
        foreach ($errors as $e) {
            echo "<div class='alert alert-error alert-toast'>" . htmlspecialchars($e) . "</div>";
        }
        unset($_SESSION['errors']);
    }

    echo "</div>";
}

function flash_success($msg){ $_SESSION['success'] = $msg; }
function flash_error($msg){ $_SESSION['errors'] = is_array($msg) ? $msg : [$msg]; }
