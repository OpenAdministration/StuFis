<?php

// http://people.mozilla.com/~bsterne/content-security-policy/details.html
// https://wiki.mozilla.org/Security/CSP/Specification
header("X-Content-Security-Policy: allow 'self'; options inline-script eval-script");
header("X-Frame-Options: DENY");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

