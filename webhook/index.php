<?php

/**
 * Implement a chatbot calling Watson API and Facebook messenger conversation
 *
 * @version    1.0.0
 * @package    interactive
 * @subpackage Portal Vocatio
 * @author     Alcindo Schleder <alcindoschleder@gmail.com>
 * @copyright (c) date('Y'), Alcindo Schleder
 */
define('AGENCIEME_TOKEN', 'EAAVcQe01LZC0BAHypIM1dzXBfMZAlvLxBXmtnu767RRZAYuDX3GYUc3CuI8S0n56d2n8ybZCFedXPi2ZCEWiC4wHispSgQe9CZB5CiBv1VyZAF6veeZCAOwodrPBbXwRJVZCzfMe2PZAmk6JB3i65kZCnao6ZBC3ATEWgBaprMtY4oBTCWRmxHBWybGK');
define('ANNIE_TOKEN', 'EAAVcQe01LZC0BAGKPHDh5YixI70cBiSr9Fp6FHlGUa7jZB33c0bmurdbHE85fZCaz2damzZANUe8iMUtiUtZCCB2vgaDG9VwZCElptCLH6D5nEXgZAfxRrIB3xTibiMHEZBg0TZA3WJ15WbyuTpIlyojR2lmZBJpydSxI26AqRwS76OfW2paeiKWgK');
define('VOCATIO_TOKEN', 'EAAVcQe01LZC0BAO7Xd2ZA3d9ALXA5HYlfLrSf9oF4YXOkqN6uPlhT9fIw59YOcq09YZBUOHMIcQnwooGr8ZB3MST65C9KXH1d0TJiXRGhZBG6et2rsiHg9LJkv8lSI61Src1ZBhrN3ArCDZB3sFZBY6LvQaqhg33S8ykdxBU6GB2ZCQgyuaCxxLz3');
define('VERIFY_TOKEN', 'ea014647cb2ef9a68336da6da7eafee11b27b61f');

$hub_verify_token = null;

//-----VEFICA O WEBHOOK-----//
$req = filter_input_array(INPUT_POST);
if (!$req):
    $req = filter_input_array(INPUT_GET);
endif;
if (($req) && (isset($req['hub_challenge']))):
    $challenge = $req['hub_challenge'];
    $hub_verify_token = $req['hub_verify_token'];
endif;
if ($hub_verify_token === VERIFY_TOKEN) {
    echo $challenge;
}
//-----FIM VERIFICAÇÃO-----//