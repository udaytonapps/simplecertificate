<?php

$REGISTER_LTI2 = array(
    "name" => "Simple Certificate",
    "FontAwesome" => "fa-star",
    "short_name" => "Simple Certificate",
    "description" => "A simple app to award a user a certificate when they launch the app.",
    "messages" => array("launch"),
    "privacy_level" => "public",  // anonymous, name_only, public
    "license" => "Apache",
    "languages" => array(
        "English",
    ),
    "source_url" => "https://github.com/udaytonapps/simplecertificate",
    // For now Tsugi tools delegate this to /lti/store
    "placements" => array(
        /*
        "course_navigation", "homework_submission",
        "course_home_submission", "editor_button",
        "link_selection", "migration_selection", "resource_selection",
        "tool_configuration", "user_navigation"
        */
    ),
    "screen_shots" => array(
    )
);