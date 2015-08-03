<?php # buy-old.php
// Created by Larry Ullman, www.larryullman.com, @LarryUllman
// Posted as part of the series "Processing Payments with Stripe"
// http://www.larryullman.com/series/processing-payments-with-stripe/
// Last updated April 14, 2015
// The class names are based upon Twitter Bootstrap (http://twitter.github.com/bootstrap/)

// This is an OLDER version of the script, that uses version 1.x of the Stripe library.
// If you're using version 2.x of the Stripe library, installed via Composer (https://getcomposer.org/), use buy.php instead.
// See https://stripe.com/docs/libraries

// This page is used to make a purchase.

// Every page needs the configuration file:
require('includes/config.inc.php');

// Uses sessions to test for duplicate submissions:
session_start();

?><!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<title>Buy This Thing</title>
	<script type="text/javascript" src="https://js.stripe.com/v2/"></script>
</head>
<body><?php

// Check for a form submission:
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

	// Stores errors:
	$errors = array();

	// Need a payment token:
	if (isset($_POST['stripeToken'])) {

		$token = $_POST['stripeToken'];

		// Check for a duplicate submission, just in case:
		// Uses sessions, you could use a cookie instead.
		if (isset($_SESSION['token']) && ($_SESSION['token'] == $token)) {
			$errors['token'] = 'You have apparently resubmitted the form. Please do not do that.';
		} else { // New submission.
			$_SESSION['token'] = $token;
		}

	} else {
		$errors['token'] = 'The order cannot be processed. Please make sure you have JavaScript enabled and try again.';
	}

	// Set the order amount somehow:
	$amount = 2000; // $20, in cents

	// Validate other form data!

	// If no errors, process the order:
	if (empty($errors)) {

		// create the charge on Stripe's servers - this will charge the user's card
		try {

			// Include the Stripe library:
			require_once('includes/stripe/lib/Stripe.php');

			// set your secret key: remember to change this to your live secret key in production
			// see your keys here https://manage.stripe.com/account
			Stripe::setApiKey(STRIPE_PRIVATE_KEY);

			// Charge the order:
			$charge = Stripe_Charge::create(array(
				"amount" => $amount, // amount in cents, again
				"currency" => "usd",
				"source" => $token,
				"description" => $email
				)
			);

			// Check that it was paid:
			if ($charge->paid == true) {

				// Store the order in the database.
				// Send the email.
				// Celebrate!

			} else { // Charge was not paid!
				echo '<div class="alert alert-error"><h4>Payment System Error!</h4>Your payment could NOT be processed (i.e., you have not been charged) because the payment system rejected the transaction. You can try again or use another card.</div>';
			}

		} catch (Stripe_CardError $e) {
		    // Card was declined.
			$e_json = $e->getJsonBody();
			$err = $e_json['error'];
			$errors['stripe'] = $err['message'];
		} catch (Stripe_ApiConnectionError $e) {
		    // Network problem, perhaps try again.
		} catch (Stripe_InvalidRequestError $e) {
		    // You screwed up in your programming. Shouldn't happen!
		} catch (Stripe_ApiError $e) {
		    // Stripe's servers are down!
		} catch (Stripe_CardError $e) {
		    // Something else that's not the customer's fault.
		}

	} // A user form submission error occurred, handled below.

} // Form submission.

// Set the Stripe key:
// Uses STRIPE_PUBLIC_KEY from the config file.
echo '<script type="text/javascript">Stripe.setPublishableKey("' . STRIPE_PUBLIC_KEY . '");</script>';

?>

	<h1>Buy This Thing</h1>

	<form action="" method="POST" id="payment-form">

		<?php // Show PHP errors, if they exist:
		if (isset($errors) && !empty($errors) && is_array($errors)) {
			echo '<div class="alert alert-error"><h4>Error!</h4>The following error(s) occurred:<ul>';
			foreach ($errors as $e) {
				echo "<li>$e</li>";
			}
			echo '</ul></div>';
		}?>

		<div id="payment-errors"></div>

		<span class="help-block">You can pay using: Mastercard, Visa, American Express, JCB, Discover, and Diners Club.</span>

		<div class="alert alert-info"><h4>JavaScript Required!</h4>For security purposes, JavaScript is required in order to complete an order.</div>

		<label>Card Number</label>
		<input type="text" size="20" autocomplete="off" class="card-number input-medium">
		<span class="help-block">Enter the number without spaces or hyphens.</span>
		<label>CVC</label>
		<input type="text" size="4" autocomplete="off" class="card-cvc input-mini">
		<label>Expiration (MM/YYYY)</label>
		<input type="text" size="2" class="card-expiry-month input-mini">
		<span> / </span>
		<input type="text" size="4" class="card-expiry-year input-mini">

		<button type="submit" class="btn" id="submitBtn">Submit Payment</button>

	</form>

	<script src="js/buy.js"></script>

</body>
</html>