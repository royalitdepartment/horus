<?php
/**
 * Step 1: Require the Horus Framework
 *
 * If you are not using Composer, you need to require the
 * Horus Framework and register its PSR-0 autoloader.
 *
 * If you are using Composer, you can skip this step.
 */
require 'Horus/App.php';
/**
 * Composer autoloader for extra packages
 */
require 'vendor/autoload.php';

\Horus\App::registerAutoloader();
/**
 * Step 2: Instantiate a Horus application
 *
 * This example instantiates a Horus application using
 * its default settings. However, you will usually configure
 * your Horus application now by passing an associative array
 * of setting names and values into the application constructor.
 */
$app = new \Horus\App();
/**
 * Initialise the Horus Auth package
 */
$auth = new \Horus\Auth();

/**
 * Horus's 404 Handler, you can pass in a custom HTML page or text as a function
 */
$app->set404();

/**
 * Step 3: Define the Horus application routes
 *
 * Here we define several Horus application routes that respond
 * to appropriate HTTP request methods. In this example, the second
 * argument for `Horus::get`, `Horus::post`, `Horus::put`, `Horus::patch`, and `Horus::delete`
 * is an anonymous function.
 */

// Home Route with Blade Templating
$app->get('/', function () use($app) {
    $app->blade->configure("app/pages", "app/pages/cache");
    $page = $app->blade->render("index", [
        "title" => "Horus PHP Framework",
        "welcome" => 'Congratulations, you\'re on <span class="green">Horus</span>'
    ]);
    $app->response->renderMarkup($page);
});

// POST route
$app->post('/post', function () use ($app) {
    $app->response->respond($app->request->body());
});

// Example User Login 
$app->get("/login", function() use($app, $auth) {
    // connect to the database
    $auth->connect("localhost", "root", "", "test");

    // sign a user in, in literally 1 line
    // $user = $auth->login("users", ["username" => "mychi", "password" => "test"], "md5");
    $user = $auth->register("users", [
        "username" => "sally",
        "email" => "sally@gmail.com",
        "password" => "test"
    ], ["username", "email"], "md5");

    // return json encoded data
    $app->response->respond(
        !$user ? $auth->errors() : $user
    );
});

// PUT route
$app->put( '/put', function () {
    echo 'This is a PUT route';
});

// PATCH route
$app->patch('/patch', function () {
    echo 'This is a PATCH route';
});

// DELETE route
$app->delete('/delete', function () {
    echo 'This is a DELETE route';
});

/**
 * Step 4: Run the Horus application
 *
 * This method should be called last. This executes the Horus application
 * and returns the HTTP response to the HTTP client.
 */
$app->run();