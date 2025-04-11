<?php
class Url {
    public static function redirectUrl($path) {
        header("Location: $path");
        exit;
    }
}
?>
