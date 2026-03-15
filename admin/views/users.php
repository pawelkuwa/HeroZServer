<?php
// This file is a fallback if no viewFile is set by the controller.
// Normally the controller sets viewFile directly, but just in case:
if (!defined('IN_ENGINE')) exit;
echo '<div class="alert alert-info">Please use the Users controller actions.</div>';
