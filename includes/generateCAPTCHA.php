<?php
/* GenerateCAPTCHA.php - Create 50 captcha images.
*/
for ($ndxx=1; $ndxx<=5; $ndxx++) {
    print "<br/>\n".$ndxx;
    #generateCaptchaImage("captcha/", $ndxx);
    showCaptchaImage("captcha/");
    flush();
}
    
function showCaptchaImage ($dir) {    
    $files = scandir($dir);
    #print_r($files);
    $rnd = mt_rand(2, count($files));
    $filename = $dir.$files[$rnd];
    $captcha_word = basename($filename, ".png");
    print "$captcha_word|<img src='$filename'><input type='hidden' name='captchaword' value='".md5($captcha_word)."'>";
    
}


function generateCaptchaImage ($storeLocal, $i, $len=5) {
    $symbols       = "123456789abcdefghjkmnpqrstuwxyz";
    $captcha_word  = '';
    for ($i = 0; $i <= $len; $i++) { $captcha_word .= substr($symbols, rand(0, strlen($symbols)), 1); }    
    $filename      = $storeLocal.$captcha_word.".png";
    $captcha_image = imagecreate(200, 40);
    $captcha_image_bgcolor  = imagecolorallocate($captcha_image, 44, 250, 0);
    $captcha_image_lcolor[] = imagecolorallocate($captcha_image, 255, rand(230, 240), rand(230, 240));
    $captcha_image_lcolor[] = imagecolorallocate($captcha_image, 255, rand(230, 240), rand(230, 240));
    $captcha_image_lcolor[] = imagecolorallocate($captcha_image, 255, rand(160, 220), rand(160, 220));
    $captcha_image_dcolor[] = imagecolorallocate($captcha_image, rand(50, 100), rand(0, 50), rand(0, 50));
    $captcha_image_dcolor[] = imagecolorallocate($captcha_image, rand(50, 100), rand(0, 50), rand(0, 50));
    $captcha_image_dcolor[] = imagecolorallocate($captcha_image, rand(50, 100), rand(0, 50), rand(0, 50));
    for ($i = 0; $i <= 10; $i++) {
        imagefilledrectangle($captcha_image, $i*20+rand(4, 26), rand(0, 39), $i*20-rand(4, 26), rand(0, 39),    
            $captcha_image_dcolor[rand(0, 2)]); 
    }
    # add the letters and numbers to the image.
    for ($i = 0; $i <= $len; $i++) {
        imagettftext($captcha_image, rand(24, 28), rand(-20, 20), $i*rand(30, 36)+rand(2,4), rand(32, 36),  
            $captcha_image_lcolor[rand(0, 2)], rand(1, 3).'.ttf', $captcha_word{$i}); }
    # image built. Now save it as a file and then display it.
    imagepng($captcha_image, $filename);
    imagedestroy($captcha_image);       # cleanup...
}


?>
