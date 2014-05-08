<?php
/*
Plugin Name: StopForumSpam
Plugin URI:  http://bbpress.org/forums/topic/new-stop-forum-spam-api-plugin-block-fake-user-registrations/
Description:  API for http://www.stopforumspam.com/ on bbPress
Version: 0.1.0
Author: pnd (http://nanp-info.com)
Author URI: http://reviewtrip.com/
*/

add_action( 'extra_profile_info', 'registration_form',12);
add_action( 'bb_init', 'registration_validation',12);   // registration

$general_questions = array();
$general_questions[] = array("Would you store the value 1.333 in an 'Integer' or a 'Float'?", "Float");
$general_questions[] = array("The class 'Ferrari' extends the class 'Car'. What is the super-class of 'Ferrari'?", "Car");
$general_questions[] = array("x = 4; y = 3; z = x + y; What is the value of z?", "7");
$general_questions[] = array("a = [3, 8, 2, 5]; a[1] = ?", "8");
$general_questions[] = array("Which mobile OS has currently the biggest market share? (Hint: the one from Google)", "Android");
$general_questions[] = array("x = 32; x++; x = ?", "33");
$general_questions[] = array("What was the favorite fruit of Steve Jobs? (Singular)", "Apple");
$general_questions[] = array("Name the third popular operating system besides Linux and OS X:", "Windows");

$sparrow_questions = array();
$sparrow_questions[] = array("What is the root class of (almost) any Objective-C object?", "NSObject");
$sparrow_questions[] = array("In Sparrow, which class is always at the root of the display tree?", "SPStage");
$sparrow_questions[] = array("In Sparrow, which class is used for tweening animations?", "SPTween");
$sparrow_questions[] = array("In Sparrow, which two-letter prefix is part of all class-names?", "SP");
$sparrow_questions[] = array("What's the sister-framework of Sparrow, available for Adobe Flash?", "Starling");
$sparrow_questions[] = array("What's Apple's IDE for Objective-C development?", "Xcode");
$sparrow_questions[] = array("In Sparrow, what's the name of the class that contains texture data?", "SPTexture");
$sparrow_questions[] = array("In Sparrow, what's the base class of all display objects?", "SPDisplayObject");
$sparrow_questions[] = array("In Sparrow, what's the base class of all display object containers?", "SPDisplayObjectContainer");

$starling_questions = array();
$starling_questions[] = array("What's the sister-framework of Starling, available for iOS?", "Sparrow");
$starling_questions[] = array("In Starling, which class is always at the root of the display tree?", "Stage");
$starling_questions[] = array("In Starling, which class is used for tweening animations?", "Tween");
$starling_questions[] = array("In Starling, what's the base class of all display objects?", "DisplayObject");
$starling_questions[] = array("In Starling, what's the base class of all display object containers?", "DisplayObjectContainer");
$starling_questions[] = array("In Starling, what kind of objects are displayed by 'Image' instances? (Enter the class name.)", "Texture");
$starling_questions[] = array("What's the name of Thibault Imbert's book about Starling?", "Introducing Starling");
$starling_questions[] = array("What's the equivalent of the package 'flash.display' in Starling?", "starling.display");
$starling_questions[] = array("In ActionScript 3, what's the modern, typesafe alternative of the 'Array' class?", "Vector");

function registration_validation()
{
	if (!empty($_POST) && isset($_POST["Submit"])) // check this first to save cpu cycles on the next check
	{
		if (script_location() != "register.php") return;  //  only display on register.php and hide on profile page

		$is_spammer_by_ip = is_spammer('ip',$_SERVER['REMOTE_ADDR']);

		if (!is_correct_answer($_POST["question_id"], $_POST["human_test_answer"]) || $is_spammer_by_ip
			// || is_spammer('ip',$_SERVER['REMOTE_ADDR'])
			// || is_spammer('username',$_POST["user_login"])
			// || is_spammer('email',$_POST["user_email"])
			)
		{
			bb_send_headers();
			bb_get_header();

			echo "<p align='center'><font size='+1'>".
				__("Humans only please").". ".__("If you are not a bot").", <br />
				".__("please go back and try again").".
				</font></p><br />";

			if ($is_spammer_by_ip)
			{
				echo "<p>Your IP address was listed as being malicious by " .
					 "<a href='http://www.stopforumspam.com/'>stopforumspam.com</a>. ".
                     "<br/>Just send me a quick mail (to daniel AT my company's domain) ".
                     "if this is happening unjustifiably.</p>";
			}

			bb_get_footer();
			exit;
		}
	}
}

function is_spammer($type, $data)
{
	$data = urlencode(substr(strip_tags(stripslashes($data)),0,50));
	$url = "http://www.stopforumspam.com/api?".$type."=".$data;
	// $content=file_get_contents($url);	// if you don't have curl, try this instead
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_HEADER, 1);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER , TRUE);
	$content = curl_exec($ch);
	curl_close($ch);
	preg_match("/<appears>(.*?)<\/appears>/si", $content, $tmp);
	return ($tmp[1] == "yes") ? true : false;
}

function is_correct_answer($question_id, $given_answer)
{
	$questions = get_questions_array();
	$num_questions = count($questions);
	$real_question_id = intval($question_id) % $num_questions;
	$correct_answer = $questions[$real_question_id][1];

	if (strcasecmp(trim($given_answer), trim($correct_answer)) == 0)
		return true;
	else
		return false;
}

function registration_form()
{
	if (script_location()!="register.php") return;  //  only display on register.php and hide on profile page

	$questions = get_questions_array();
	$num_questions = count($questions);
	$question_id = rand(0, $num_questions-1);
	$fake_question_id = $num_questions * rand(0, 100000) + $question_id;

	echo '<fieldset>';
	echo ' <legend>Please prove that you are worthy!</legend>';
	echo ' <p>As protection against spammers, we need you to prove that you are somewhat proficient in the topic of this forum. Sorry for this inconvenience! <br/>Having problems? <a href="http://gamua.com">Contact us</a> and we will create an account for you.<br/></p>';
	echo ' <p><strong>' . $questions[$question_id][0] . '</strong></p>';
	echo ' <table width="100%">';
	echo '  <tr class="form-field form-required required">';
	echo '   <th scope="row">';
	echo '    <label for="human_test_answer">Answer</label>';
	echo '   </th>';
	echo '   <td>';
	echo '	  <input name="human_test_answer" type="text" id="human_test_answer" size="30" maxlength="100" value="" />';
	echo '	 </td>';
	echo '  </tr>';
	echo ' </table>';
	echo '</fieldset>';
	echo '<input type="hidden" name = "question_id" value = "'. $fake_question_id . '" />';

}

function script_location()
{
	$resource=array($_SERVER['PHP_SELF'], $_SERVER['SCRIPT_FILENAME'], $_SERVER['SCRIPT_NAME']);
	foreach ($resource as $name )
	{
		if (false!==strpos($name, '.php'))
				return bb_find_filename($name);
	}
	return false;
}

function get_questions_array()
{
	return $general_questions;

	/*
	global $sparrow_questions;
	global $starling_questions;

	$domain = $_SERVER['SERVER_NAME']; // -> forum.sparrow-framework.org

	if (strpos($domain, 'sparrow') !== FALSE)
		return $sparrow_questions;
	else
		return $starling_questions;
	*/
}

?>