<?php

/*
	Routing table
	----------------------------------- */

	namespace CRD\Core;

	// Start router
	$app->router = new Router($app);

	// Home
	$app->router->add('home', '/', array('view' => 'home'), function($view)
	{
		$view->template = new Template($view->app, 'page', 'page-home');
	});

	// Contact Us
	$app->router->add('contact', '/contact/', array('view' => 'contact'), function($view)
	{
		$view->template = new Template($view->app, 'page', 'page-contact');
	});

	// 404 route
	$app->router->add(':404:', null, array('view' => 'error-404'), function($view)
	{
		$view->template = new Template($view->app, 'page', 'page-error');
	});

	// Check request matches a route
	$app->router->check();
?>