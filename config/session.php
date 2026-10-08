<?php
// Central session bootstrap - include this at the very top of every page (before any HTML output)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
