<?php
// ============================================================
//  iot_config.php  —  Shared IoT alert threshold
//
//  IMPORTANT: HIGH_TEMP_THRESHOLD_C must match the same-named
//  constant in temperature_sender.ino. The firmware drives the
//  red/yellow LEDs and buzzer locally using its own copy of this
//  value (it cannot read this file), so if you change the limit
//  here, change it there too — otherwise the physical alert and
//  the website's alert banner/popup can disagree.
// ============================================================

if (!defined('HIGH_TEMP_THRESHOLD_C')) {
    define('HIGH_TEMP_THRESHOLD_C', 35.0);
}