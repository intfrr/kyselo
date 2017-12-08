<?php
const KYSELO_PASSWORD_ALG = PASSWORD_DEFAULT;

// famous 1-step registration process

Flight::route('/act/register', function() {
	if (!empty($_SESSION['user']['name'])) {
		Flight::redirect('/' . $_SESSION['user']['name'] . '/friends');
	}

	/** @var Sparrow $db */
	$db = Flight::db();
	$request = Flight::request();
	
	$form = new severak\forms\form(['method'=>'POST', 'class'=>'pure-form pure-form-stacked']);
	$form->field('username', ['label'=>'User name', 'required'=>true]);
	$form->field('email', ['label'=>'E-mail', 'type'=>'email', 'required'=>true]);
	$form->field('password', ['label'=>'Password', 'type'=>'password', 'required'=>true]);
	$form->field('password_again', ['label'=>'and again', 'type'=>'password', 'required'=>true]);
	$form->field('terms_agreement', ['label'=>'I agree with terms of service', 'type'=>'checkbox', 'required'=>true]);
	$form->field('register', ['label'=>'Sing in', 'type'=>'submit']);
	// todo: catchpa - viz http://jecas.cz/recaptcha

	// todo: kontrola formátu username
	
	$form->rule('username', function($name) {
		$db = Flight::db();
		return $db->from('blogs')->where('name', $name)->count() == 0;
	}, 'Username already in use. Choose another.');
	
	// todo: check if email is email

	$form->rule('password_again', function($password, $fields) {
		return $password==$fields['password'];
	}, 'Must match previous password.');

	if ($request->method=='POST') {
		$form->fill($_POST);
		
		if  ($form->validate()) {

			$db->from('users')->insert([
				'blog_id' => 0,
				'email' => $form->values['email'],
				'password' => password_hash($form->values['password'], KYSELO_PASSWORD_ALG),
				'is_active' => 1
			])->execute();

			$userId = $db->insert_id;

			$db->from('blogs')->insert([
				'name' => $form->values['username'],
				'title' => $form->values['username'],
				'about' => '(komencanto)',
				'avatar_url'=> '/st/johnny-automatic-horse-head-50px.png',
				'user_id' => $userId,
				'since' => date('Y-m-d H:i:s')
			])->execute();

			$blogId = $db->insert_id;

			$db->from('users')->update(['blog_id'=>$blogId])->where(['id'=>$userId])->execute();

			// todo: flash msg
			Flight::redirect('/act/login');
		}
	}
	
	Flight::render('header', ['title' => 'registration' ]);
	Flight::render('registration', [
		'form' => $form,
	]);
	Flight::render('footer', []);
});

// login
Flight::route('/act/login', function() {
	if (!empty($_SESSION['user']['name'])) {
		Flight::redirect('/' . $_SESSION['user']['name'] . '/friends');
	}

	/** @var Sparrow $db */
	$db = Flight::db();
	$request = Flight::request();
	
	$form = new severak\forms\form(['method'=>'POST', 'class'=>'pure-form pure-form-stacked']);
	$form->field('username', ['label'=>'User name', 'required'=>true]);
	$form->field('password', ['label'=>'Password', 'type'=>'password', 'required'=>true]);
	$form->field('register', ['label'=>'Login', 'type'=>'submit']);
	
	
	if ($request->method=='POST') {
		$form->fill($_POST);
		
		if ($form->validate()) {
			$blog = $db->from('blogs')->where('name', $_POST['username'])->one();
			if (!empty($blog)) {
				$user = $db->from('users')->where('id',$blog['user_id'])->one();
				if (password_verify($_POST['password'], $user['password'])) {
					$_SESSION['user'] = [
						'name' => $blog['name'],
						'blog_id' => $blog['id'],
						'avatar_url' => $blog['avatar_url'],
						'groups'=> [], // todo - skupiny, kterych jsem clenem
					];
					
					Flight::redirect('/' . $blog['name'] . '/friends');
				}
			}
		}
		$forms->errors['password'] = 'Bad login/password!';
	}
	

	Flight::render('header', ['title' => 'login' ]);
	Flight::render('registration', [
		'form' => $form,
	]);
	Flight::render('footer', []);
});



// logout
Flight::route('/act/logout', function() {
	if (empty($_SESSION['user']['name'])) {
		Flight::redirect('/');
	}
	
	$_SESSION['user'] = false;
	Flight::redirect('/');
});

