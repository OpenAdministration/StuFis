<?php

// http://people.mozilla.com/~bsterne/content-security-policy/details.html
// https://wiki.mozilla.org/Security/CSP/Specification
header("X-Content-Security-Policy: allow 'self'; options inline-script eval-script");
header("X-Frame-Options: DENY");

