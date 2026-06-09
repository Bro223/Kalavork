<?php
// Silence — prevent directory listing and direct PHP execution
// This file exists so that if Apache's DirectoryIndex lands here,
// nothing is exposed.
http_response_code(403);
// No output — don't reveal that this file exists
exit;
