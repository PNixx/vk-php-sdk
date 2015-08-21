VKontakte PHP SDK
=================

Require:

* PHP 5.5
* Composer (Optional)

The VK Platform http://vk.com/dev is a set of APIs that make your application more social.

##Install

	composer require pnixx/vk-php-sdk

Usage
-----

To create a new instance of VKontakte and make api calls:

	<?
	//The constructor 
	$vk = new VK($access_token);
	
	//Set user online
	$vk->method('account.setOnline');
	
	//Upload photo
	$photo = $vk->uploadImage('/path/to/file');
	
	//Wall posting
	$response = $vk->method('wall.post', [
		'message' => urlencode('Hello world'),
		'attachments' => $photo
	]);
	