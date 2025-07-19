<?php
session_start();
session_destroy();
header("Location: https://ayomaju.my.id/cms/Login-user");
exit;
