<?php

require __DIR__ . '/../../src/bootstrap.php';

logout_user();
header('Location: /');
exit;
