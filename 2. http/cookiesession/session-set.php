<?php

session_start();

$_SESSION['user_id'] = 15;

$_SESSION['name'] = 'Bojan';

echo 'Session updated!';