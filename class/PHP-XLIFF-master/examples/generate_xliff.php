<?php

require_once '../src/XliffDocument.php';
$htmlstring = '<div class="copy">
	 	<p>There’s a real James Blake head nod on this latest track from Melbourne’s <strong>Life is Better Blonde</strong>. <em>Fires</em> is a glitchy, slickly produced number, sitting somewhere between the uncompromising yet addictive electronica of Blake’s early releases, and the smoother, more straight forward second album from 4AD’s SOHN. Check it out below.</p>
<p><iframe src="https://w.soundcloud.com/player/?url=https%3A//api.soundcloud.com/tracks/310373009&amp;auto_play=false&amp;hide_related=false&amp;show_comments=true&amp;show_user=true&amp;show_reposts=false&amp;visual=true" width="100%" height="450" frameborder="no" scrolling="no"></iframe></p>
	 </div>';
echo "Generating new XLIFF document:" . PHP_EOL;

$dom2 = new DOMDocument();
$dom2->loadHTML($htmlstring);
var_dump($dom2);
$xliff2 = XliffDocument::fromDOM($dom2);

var_dump($xliff2);


