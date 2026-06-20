<?php

use App\Controllers\AdminController;
use App\Controllers\AuthController;
use App\Controllers\HomeController;
use App\Controllers\InstallController;
use App\Controllers\MessageController;
use App\Controllers\ProfileController;

$router->add('GET', '/', [HomeController::class, 'index']);
$router->add('POST', '/post/create', [HomeController::class, 'createPost']);
$router->add('GET', '/post/edit/{id}', [HomeController::class, 'editPost']);
$router->add('POST', '/post/update/{id}', [HomeController::class, 'updatePost']);
$router->add('POST', '/post/delete/{id}', [HomeController::class, 'deletePost']);
$router->add('GET', '/post/like/{id}', [HomeController::class, 'toggleLike']);
$router->add('POST', '/post/comment/{postId}', [HomeController::class, 'addComment']);

$router->add('GET', '/login', [AuthController::class, 'showLogin']);
$router->add('POST', '/login', [AuthController::class, 'login']);
$router->add('GET', '/register', [AuthController::class, 'showRegister']);
$router->add('POST', '/register', [AuthController::class, 'register']);
$router->add('GET', '/logout', [AuthController::class, 'logout']);
$router->add('GET', '/force-password-change', [AuthController::class, 'showForceChangePassword']);
$router->add('POST', '/force-password-change', [AuthController::class, 'forceChangePassword']);

$router->add('GET', '/profile/edit', [ProfileController::class, 'edit']);
$router->add('POST', '/profile/update', [ProfileController::class, 'update']);
$router->add('GET', '/profile/{username}', [ProfileController::class, 'show']);

$router->add('GET', '/messages', [MessageController::class, 'index']);
$router->add('POST', '/messages/send', [MessageController::class, 'send']);
$router->add('GET', '/api/messages/fetch', [MessageController::class, 'fetchNew']);
$router->add('GET', '/api/messages/counters', [MessageController::class, 'getCounters']);

$router->add('GET', '/admin', [AdminController::class, 'index']);
$router->add('GET', '/admin/user/delete/{id}', [AdminController::class, 'deleteUser']);
$router->add('GET', '/admin/user/role/{id}', [AdminController::class, 'toggleRole']);
$router->add('GET', '/admin/audit', [AdminController::class, 'audit']);

$router->add('GET', '/install', [InstallController::class, 'index']);
$router->add('POST', '/install/run', [InstallController::class, 'run']);

$router->add('POST', '/toggle-theme', [HomeController::class, 'toggleTheme']);